<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\TypeHints;

use PERCS31\Helpers\CompoundTypeHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Disallows spaces around the `|` and `&` of a compound type, but allows the type to be split before one.
 *
 * PER Coding Style 3.1, section 2.5, says the union symbol `|` and intersection symbol `&` MUST NOT have a leading or
 * trailing space, and that a compound type MAY be split across lines, with the symbol at the start of each line.
 * SlevomatCodingStandard.TypeHints.DNFTypeHintFormat counts that line break as a space, so it rejects every split
 * type. Universal.Operators.TypeSeparatorSpacing does the same. The PERCS31 ruleset excludes Slevomat's
 * DisallowedWhitespaceAroundOperator code, and this sniff checks the spacing instead:
 * - A space before or after the symbol, on the same line, is reported, and removed by the fix.
 * - A line break before the symbol is allowed.
 * - A line break after the symbol, which leaves it at the end of the line, is reported, and the fix moves the symbol
 *   to the start of the next line.
 *
 * Which types go on which line is checked by PERCS31.TypeHints.CompoundTypeLayout.
 *
 * It also covers the `|` between a multi-catch's types, e.g. `catch (A|B $e)`, which is read as a union type's. It
 * replaces PhpCollective.WhiteSpace.CatchTypeSeparatorSpacing there, which reports a type split before the `|`, and
 * can't fix one split after it.
 *
 * It also covers a `set` hook's parameter type, e.g. `set(int|string $value)`. PHP_CodeSniffer doesn't know about
 * hooks, so it gives those symbols operator tokens, and Slevomat's sniff doesn't see them. So this sniff also checks
 * the parentheses of a DNF type there, which Slevomat's sniff checks everywhere else.
 */
class CompoundTypeOperatorSpacingSniff implements Sniff
{
    public const string CODE_SPACE_AROUND_OPERATOR = 'SpaceAroundOperator';

    public const string CODE_OPERATOR_AT_END_OF_LINE = 'OperatorAtEndOfLine';

    public const string CODE_SPACE_INSIDE_PARENTHESES = 'SpaceInsideParentheses';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_TYPE_UNION, T_TYPE_INTERSECTION, T_BITWISE_OR, T_BITWISE_AND, T_OPEN_PARENTHESIS];
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
        if ($code === T_OPEN_PARENTHESIS) {
            if (PropertyHookHelper::isInHookParameterList($phpcsFile, $stackPtr)) {
                $this->checkHookTypeParentheses($phpcsFile, $stackPtr);
            }

            return;
        }

        if (!CompoundTypeHelper::isTypeSymbol($phpcsFile, $stackPtr)) {
            return;
        }

        $before = $tokens[($stackPtr - 1)];
        $after = $tokens[($stackPtr + 1)];
        // An indent before the symbol, at the start of its line, isn't a space before it.
        $hasSpaceBefore = $before['code'] === T_WHITESPACE
            && $tokens[($stackPtr - 2)]['line'] === $tokens[$stackPtr]['line'];
        $isLineEnd = $after['code'] === T_WHITESPACE && str_contains($after['content'], $phpcsFile->eolChar);
        $hasSpaceAfter = $after['code'] === T_WHITESPACE && !$isLineEnd;
        $data = [$tokens[$stackPtr]['content']];

        if ($isLineEnd) {
            $this->reportLineEnd($phpcsFile, $stackPtr, $hasSpaceBefore);
            return;
        }

        if (!$hasSpaceBefore && !$hasSpaceAfter) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'There must be no space around the "%s" in a compound type',
            $stackPtr,
            self::CODE_SPACE_AROUND_OPERATOR,
            $data,
        );
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        if ($hasSpaceBefore) {
            $phpcsFile->fixer->replaceToken(($stackPtr - 1), '');
        }

        if ($hasSpaceAfter) {
            $phpcsFile->fixer->replaceToken(($stackPtr + 1), '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Report whitespace just inside the parentheses of a DNF type in a `set` hook's parameter, and fix it by removing
     * it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the opening parenthesis.
     */
    private function checkHookTypeParentheses(File $phpcsFile, int $opener): void
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$opener]['parenthesis_closer'] ?? null;
        if (
            !is_int($closer)
            || ($tokens[($opener + 1)]['code'] !== T_WHITESPACE && $tokens[($closer - 1)]['code'] !== T_WHITESPACE)
        ) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'There must be no space just inside the parentheses of a compound type',
            $opener,
            self::CODE_SPACE_INSIDE_PARENTHESES,
        );
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($opener + 1); $tokens[$i]['code'] === T_WHITESPACE; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        for ($i = ($closer - 1); $tokens[$i]['code'] === T_WHITESPACE; $i--) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Report a symbol at the end of a line, and fix it by moving it to the start of the next line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the symbol.
     * @param bool $hasSpaceBefore Whether a space, on the same line, comes before the symbol.
     */
    private function reportLineEnd(File $phpcsFile, int $stackPtr, bool $hasSpaceBefore): void
    {
        $tokens = $phpcsFile->getTokens();
        $fix = $phpcsFile->addFixableError(
            'A compound type split across lines must have the "%s" at the start of the line, not the end',
            $stackPtr,
            self::CODE_OPERATOR_AT_END_OF_LINE,
            [$tokens[$stackPtr]['content']],
        );
        if (!$fix) {
            return;
        }

        // The symbol moves to just before the first type on the next line, after its indent.
        $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($next === false) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        if ($hasSpaceBefore) {
            $phpcsFile->fixer->replaceToken(($stackPtr - 1), '');
        }

        $phpcsFile->fixer->replaceToken($stackPtr, '');
        $phpcsFile->fixer->addContentBefore($next, $tokens[$stackPtr]['content']);
        $phpcsFile->fixer->endChangeset();
    }
}
