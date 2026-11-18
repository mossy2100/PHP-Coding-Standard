<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\TypeHints;

use PERCS31\Helpers\CompoundTypeHelper;
use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\GetTokensAsString;

/**
 * Checks a compound type: its layout, and where `null` goes in a union.
 *
 * The layout: no space around its `|` and `&`, and when it's split across lines, one type, or one union segment, per
 * line, with the symbol at the start of each line.
 *
 * PER Coding Style 3.1, section 2.5, says the union symbol `|` and intersection symbol `&` MUST NOT have a leading or
 * trailing space, and that if a compound type is split across lines:
 * - If it contains only unions or only intersections, each line MUST have a single type.
 * - If it contains both, each line MUST have a single union segment, and all intersections in a segment MUST be on the
 *   same line.
 * - The symbol it's split on MUST be at the start of each line.
 *
 * The spacing: a space before or after a symbol, on its line, is reported as SpaceAroundOperator, and removed. A line
 * break before a symbol is allowed. A symbol at the end of a line is reported as OperatorAtEndOfLine, and the fix moves
 * it to the start of the next line. SlevomatCodingStandard.TypeHints.DNFTypeHintFormat and
 * Universal.Operators.TypeSeparatorSpacing count that line break as a space, so they reject every split type, and
 * aren't used for this.
 *
 * The lines: a type is split if any `|` or `&` outside parentheses starts a line. Then every one of them must start a
 * line, and the fix breaks the line before each that doesn't. A `&` inside a DNF type's parentheses must never start a
 * line, and the fix joins it to the line before.
 *
 * Section 2.5 doesn't say how a split type's lines are indented. Its examples, "correct ways to format compound types",
 * are in parameter lists, where the type starts its line, and each `|` line is level with it. So that's required where
 * a type starts its line, as in a parameter list or a multi-catch. Where other code comes before the type on its line,
 * as in `public A`, `public const A` or `): A`, each `|` line continues that line, so it's indented once past it, as
 * section 6.4 indents any line that continues another. The fix sets the indent.
 *
 * It also covers the `|` between a multi-catch's types, e.g. `catch (A|B $e)`, which is read as a union type's. It
 * replaces PhpCollective.WhiteSpace.CatchTypeSeparatorSpacing there, which reports a type split before the `|`, and
 * can't fix one split after it.
 *
 * It also covers a `set` hook's parameter type, e.g. `set(int|string $value)`. PHP_CodeSniffer doesn't know about
 * hooks, so it gives those symbols and parentheses other tokens, and Slevomat's sniff doesn't see them. So this sniff
 * also checks the space inside a DNF type's parentheses there, as SpaceInsideParentheses, which Slevomat's sniff
 * checks everywhere else.
 *
 * `null`: section 2.5 also says that if one of the ORed conditions is `null`, it MUST be the last item, and that a
 * union of a single simple type with `null` SHOULD be written `?T`.
 *
 * SlevomatCodingStandard.TypeHints.DNFTypeHintFormat checks both, but gets some cases wrong:
 * - It skips the `null` position in any type with an intersection, such as `null|(A&B)`.
 * - For `null|int`, it only reports the missing `?T`, not the position. `?T` is a SHOULD, so with that code set to a
 *   warning, the MUST is lost below warning level.
 * - It doesn't check a typed constant (PHP 8.3), or a `set` hook's parameter, which PHP_CodeSniffer reads as an
 *   expression.
 * - Its `?T` fix deletes `static` from a return type, so `static|null` becomes `?`, which is a parse error.
 *
 * So this sniff makes both checks in every type, and the PERCS31 ruleset doesn't use Slevomat's
 * NullTypeHintNotOnLastPosition or RequiredShortNullable codes.
 *
 * A type split across lines, or with a comment in it, is reported but not fixed.
 */
class CompoundTypeSniff implements Sniff
{
    public const string CODE_MULTIPLE_TYPES_ON_LINE = 'MultipleTypesOnLine';

    public const string CODE_SPLIT_INTERSECTION = 'SplitIntersection';

    public const string CODE_INDENT = 'Indent';

    public const string CODE_SPACE_AROUND_OPERATOR = 'SpaceAroundOperator';

    public const string CODE_OPERATOR_AT_END_OF_LINE = 'OperatorAtEndOfLine';

    public const string CODE_SPACE_INSIDE_PARENTHESES = 'SpaceInsideParentheses';

    public const string CODE_NULL_NOT_LAST = 'NullNotLast';

    public const string CODE_SHORT_NULLABLE = 'ShortNullable';

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

        $isHook = $code === T_BITWISE_OR || $code === T_BITWISE_AND;
        if (!CompoundTypeHelper::isTypeSymbol($phpcsFile, $stackPtr)) {
            return;
        }

        $this->checkSpacing($phpcsFile, $stackPtr);
        $symbols = CompoundTypeHelper::findSymbols($phpcsFile, $stackPtr, $isHook);

        // Check each type once, from its first symbol.
        if ($symbols[0] !== $stackPtr) {
            return;
        }

        [$typeStart, $typeEnd] = CompoundTypeHelper::findTypeBounds($phpcsFile, $stackPtr, $isHook);
        $this->checkLines($phpcsFile, $typeStart, $typeEnd);
        $this->checkNull($phpcsFile, $typeStart, $typeEnd);
    }

    /**
     * Check the lines of a compound type split across lines: one type, or one union segment, on each, and each
     * indented as the type's first line requires.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $typeStart The position of the type's first token.
     * @param int $typeEnd The position of the type's last token.
     */
    private function checkLines(File $phpcsFile, int $typeStart, int $typeEnd): void
    {
        $tokens = $phpcsFile->getTokens();
        [$topLevel, $grouped] = CompoundTypeHelper::splitSymbols($phpcsFile, $typeStart, $typeEnd);

        foreach ($grouped as $symbol) {
            if (LayoutHelper::startsLine($phpcsFile, $symbol)) {
                $this->reportSplitIntersection($phpcsFile, $symbol);
            }
        }

        $isSplit = false;
        foreach ($topLevel as $symbol) {
            $isSplit = $isSplit || LayoutHelper::startsLine($phpcsFile, $symbol);
        }

        if (!$isSplit) {
            return;
        }

        $error = $grouped === []
            ? 'A compound type split across lines must have a single type on each line'
            : 'A compound type split across lines must have a single union segment on each line';
        // Each line is level with the type's first line when the type starts that line, as in a parameter list. When
        // other code comes first, as in `public A` or `): A`, each line continues that one, so it's indented once more.
        $indent = LayoutHelper::lineIndent($phpcsFile, $typeStart);
        if (!LayoutHelper::startsLine($phpcsFile, $typeStart)) {
            $indent += LayoutHelper::INDENT;
        }
        foreach ($topLevel as $symbol) {
            if (!LayoutHelper::startsLine($phpcsFile, $symbol)) {
                $this->reportMultipleTypesOnLine($phpcsFile, $symbol, $error, $indent);
            } elseif (($tokens[$symbol]['column'] - 1) !== $indent) {
                $this->reportIndent($phpcsFile, $symbol, $indent);
            }
        }
    }

    /**
     * Check that `null` is the last type in a union, and that a union of one simple type with `null` is written `?T`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the type's first token.
     * @param int $end The position of the type's last token.
     */
    private function checkNull(File $phpcsFile, int $start, int $end): void
    {
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

        if (count($members) === 2) {
            $other = $members[(1 - $nullIndex)];
            if ($other[0] === $other[1]) {
                // Fix it once null is last, so the two fixes don't clash.
                $this->reportShortNullable($phpcsFile, $start, $end, $other[0], $type, $fixable && $isNullLast);
            }
        }
    }

    /**
     * Report a line of a split type that isn't indented as the type's first line requires, and fix its indent.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $symbol The position of the symbol that starts the line.
     * @param int $indent The number of spaces the line should be indented by.
     */
    private function reportIndent(File $phpcsFile, int $symbol, int $indent): void
    {
        $error = 'A line of a compound type split across lines is indented incorrectly; '
            . 'expected %s spaces but found %s';
        $found = $phpcsFile->getTokens()[$symbol]['column'] - 1;
        FixerHelper::reportIndent($phpcsFile, $symbol, $indent, $error, self::CODE_INDENT, [$indent, $found]);
    }

    /**
     * Report a `&` inside parentheses that starts a line, and fix it by joining it to the line before.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $symbol The position of the symbol.
     */
    private function reportSplitIntersection(File $phpcsFile, int $symbol): void
    {
        $error = 'All intersections in a union segment must be on the same line';
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($symbol - 1), null, true);
        $prevContent = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($symbol - 1), null, true);
        if ($prev === false || $prev !== $prevContent) {
            // A comment before the symbol would end up inside the type's line, so leave that to a person.
            $phpcsFile->addError($error, $symbol, self::CODE_SPLIT_INTERSECTION);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $symbol, self::CODE_SPLIT_INTERSECTION);
        if (!$fix) {
            return;
        }

        FixerHelper::replaceBetween($phpcsFile, $prev, $symbol, '');
    }

    /**
     * Report a `|` or `&` outside parentheses that doesn't start a line, and fix it by breaking the line before it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $symbol The position of the symbol.
     * @param string $error The message.
     * @param int $indent The number of spaces to indent the new line by.
     */
    private function reportMultipleTypesOnLine(File $phpcsFile, int $symbol, string $error, int $indent): void
    {
        $fix = $phpcsFile->addFixableError($error, $symbol, self::CODE_MULTIPLE_TYPES_ON_LINE);
        if (!$fix) {
            return;
        }

        FixerHelper::breakLineBefore($phpcsFile, $symbol, $indent);
    }

    /**
     * Check the spacing around a type's `|` or `&`: no space either side on its line, and not at the end of a line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the symbol.
     */
    private function checkSpacing(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
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
        [$topLevel] = CompoundTypeHelper::splitSymbols($phpcsFile, $start, $end);
        $unions = array_filter(
            $topLevel,
            static fn(int $symbol): bool => in_array($tokens[$symbol]['code'], [T_TYPE_UNION, T_BITWISE_OR], true),
        );

        $members = [];
        $from = $start;
        foreach ([...$unions, ($end + 1)] as $to) {
            $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, $from, $to, true);
            $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($to - 1), $from, true);
            if ($first !== false && $last !== false) {
                $members[] = [$first, $last];
            }

            $from = $to + 1;
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
     * Get a type's text for a message, without whitespace or comments.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the type's first token.
     * @param int $end The position of the type's last token.
     * @return string The type.
     */
    private function typeText(File $phpcsFile, int $start, int $end): string
    {
        return GetTokensAsString::noEmpties($phpcsFile, $start, $end);
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
