<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\TypeHints;

use PERCS31\Helpers\CompoundTypeHelper;
use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires `null` to be the last type in a union, and recommends `?T` for a union of one type with `null`, wherever a
 * type can appear.
 *
 * PER Coding Style 3.1, section 2.5, says that if one of the ORed conditions is `null`, it MUST be the last item, and
 * that a union of a single simple type with `null` SHOULD be written `?T`.
 *
 * SlevomatCodingStandard.TypeHints.DNFTypeHintFormat checks both, but misses some cases:
 * - It skips the `null` position in any type with an intersection, such as `null|(A&B)`.
 * - For `null|int`, it only reports the `?T` warning, so the MUST is lost below warning level.
 * - It doesn't check a typed constant (PHP 8.3), or a `set` hook's parameter, which PHP_CodeSniffer reads as an
 *   expression.
 *
 * So this sniff checks the `null` position in every type, and the PERCS31 ruleset excludes Slevomat's
 * NullTypeHintNotOnLastPosition code. It only checks `?T` in a typed constant and a `set` hook's parameter, where
 * Slevomat's RequiredShortNullable doesn't.
 *
 * A type split across lines, or with a comment in it, is reported but not fixed.
 */
class NullTypeSniff implements Sniff
{
    public const string CODE_NULL_NOT_LAST = 'NullNotLast';

    public const string CODE_SHORT_NULLABLE = 'ShortNullable';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_TYPE_UNION, T_TYPE_INTERSECTION, T_BITWISE_OR, T_BITWISE_AND];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $code = $tokens[$stackPtr]['code'];
        $isHook = $code === T_BITWISE_OR || $code === T_BITWISE_AND;
        if ($isHook && !PropertyHookHelper::isHookParameterType($phpcsFile, $stackPtr)) {
            return;
        }

        // Check each type once, from its first symbol.
        if (CompoundTypeHelper::findSymbols($phpcsFile, $stackPtr, $isHook)[0] !== $stackPtr) {
            return;
        }

        [$start, $end] = CompoundTypeHelper::findTypeBounds($phpcsFile, $stackPtr, $isHook);
        $members = $this->findMembers($phpcsFile, $start, $end);
        $nullIndex = $this->findNull($phpcsFile, $members);
        if ($nullIndex === null || count($members) < 2) {
            return;
        }

        $fixable = $this->isFixable($phpcsFile, $start, $end);
        $type = $this->typeText($phpcsFile, $start, $end);
        $isNullLast = $nullIndex === (count($members) - 1);
        if (!$isNullLast) {
            $this->reportNullNotLast($phpcsFile, $members, $nullIndex, $type, $fixable);
        }

        $before = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($start - 1), null, true);
        $isConstant = $before !== false && $tokens[$before]['code'] === T_CONST;
        if (($isHook || $isConstant) && count($members) === 2) {
            $other = $members[(1 - $nullIndex)];
            if ($other[0] === $other[1]) {
                // Fix it once null is last, so the two fixes don't clash.
                $this->reportShortNullable($phpcsFile, $start, $end, $other[0], $type, $fixable && $isNullLast);
            }
        }
    }

    /**
     * Split a type into the members of its union, outside any DNF parentheses.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the type's first token.
     * @param int $end The position of the type's last token.
     * @return list<array{int, int}> The first and last tokens of each member.
     */
    private function findMembers(File $phpcsFile, int $start, int $end): array
    {
        $tokens = $phpcsFile->getTokens();
        $members = [];
        $depth = 0;
        $first = null;
        $last = null;
        for ($i = $start; $i <= $end; $i++) {
            $code = $tokens[$i]['code'];
            if (isset(Tokens::EMPTY_TOKENS[$code])) {
                continue;
            }

            if ($depth === 0 && ($code === T_TYPE_UNION || $code === T_BITWISE_OR)) {
                if ($first !== null && $last !== null) {
                    $members[] = [$first, $last];
                }

                $first = null;
                continue;
            }

            if ($code === T_TYPE_OPEN_PARENTHESIS || $code === T_OPEN_PARENTHESIS) {
                $depth++;
            } elseif ($code === T_TYPE_CLOSE_PARENTHESIS || $code === T_CLOSE_PARENTHESIS) {
                $depth--;
            }

            $first ??= $i;
            $last = $i;
        }

        if ($first !== null && $last !== null) {
            $members[] = [$first, $last];
        }

        return $members;
    }

    /**
     * Find which member of a union is `null`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param list<array{int, int}> $members The first and last tokens of each member.
     * @return int|null The member's index, or null if no member is `null`.
     */
    private function findNull(File $phpcsFile, array $members): ?int
    {
        $tokens = $phpcsFile->getTokens();
        foreach ($members as $index => [$first, $last]) {
            if ($first === $last && strtolower($tokens[$first]['content']) === 'null') {
                return $index;
            }
        }

        return null;
    }

    /**
     * Check whether a type can be fixed: it's on one line, with no comment in it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the type's first token.
     * @param int $end The position of the type's last token.
     * @return bool True if it can.
     */
    private function isFixable(File $phpcsFile, int $start, int $end): bool
    {
        $tokens = $phpcsFile->getTokens();

        return $tokens[$start]['line'] === $tokens[$end]['line']
            && $phpcsFile->findNext(Tokens::COMMENT_TOKENS, $start, ($end + 1)) === false;
    }

    /**
     * Get a type's text for a message, without whitespace.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the type's first token.
     * @param int $end The position of the type's last token.
     * @return string The type.
     */
    private function typeText(File $phpcsFile, int $start, int $end): string
    {
        return (string) preg_replace('/\s+/', '', $phpcsFile->getTokensAsString($start, ($end - $start + 1)));
    }

    /**
     * Report a `null` that isn't the last member, and fix it by moving it to the end.
     *
     * @param File $phpcsFile The file being scanned.
     * @param list<array{int, int}> $members The first and last tokens of each member.
     * @param int $nullIndex The index of the `null` member.
     * @param string $type The type's text, for the message.
     * @param bool $fixable Whether the type can be fixed.
     */
    private function reportNullNotLast(
        File $phpcsFile,
        array $members,
        int $nullIndex,
        string $type,
        bool $fixable,
    ): void {
        $error = 'null must be the last type in a union; found "%s"';
        $nullPtr = $members[$nullIndex][0];
        if (!$fixable) {
            $phpcsFile->addError($error, $nullPtr, self::CODE_NULL_NOT_LAST, [$type]);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $nullPtr, self::CODE_NULL_NOT_LAST, [$type]);
        if (!$fix) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $content = $tokens[$nullPtr]['content'];
        $lastEnd = $members[(count($members) - 1)][1];

        $phpcsFile->fixer->beginChangeset();
        if ($nullIndex === 0) {
            // Remove `null|`, up to the next member.
            $phpcsFile->fixer->replaceToken($nullPtr, '');
            FixerHelper::blankBetween($phpcsFile, $nullPtr, $members[1][0]);
        } else {
            // Remove `|null`, from the end of the member before it, which is just before the `|`.
            $symbol = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($nullPtr - 1), null, true);
            $previousEnd = $symbol === false
                ? false
                : $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($symbol - 1), null, true);
            if ($previousEnd === false) {
                $phpcsFile->fixer->rollbackChangeset();
                return;
            }

            FixerHelper::blankBetween($phpcsFile, $previousEnd, $nullPtr);
            $phpcsFile->fixer->replaceToken($nullPtr, '');
        }

        $phpcsFile->fixer->addContent($lastEnd, '|' . $content);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Report a union of one simple type with `null`, and fix it by writing it as `?T`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the type's first token.
     * @param int $end The position of the type's last token.
     * @param int $otherPtr The position of the type that isn't `null`.
     * @param string $type The type's text, for the message.
     * @param bool $fixable Whether to fix it.
     */
    private function reportShortNullable(
        File $phpcsFile,
        int $start,
        int $end,
        int $otherPtr,
        string $type,
        bool $fixable,
    ): void {
        $shortForm = '?' . $phpcsFile->getTokens()[$otherPtr]['content'];
        $warning = 'A union of one type and null should be written as ?T; found "%s", use "%s"';
        $data = [$type, $shortForm];
        if (!$fixable) {
            $phpcsFile->addWarning($warning, $otherPtr, self::CODE_SHORT_NULLABLE, $data);
            return;
        }

        $fix = $phpcsFile->addFixableWarning($warning, $otherPtr, self::CODE_SHORT_NULLABLE, $data);
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($start, $shortForm);
        FixerHelper::blankBetween($phpcsFile, $start, ($end + 1));
        $phpcsFile->fixer->endChangeset();
    }
}
