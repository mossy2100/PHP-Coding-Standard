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

/**
 * Checks the layout of a compound type: no space around its `|` and `&`, and when it's split across lines, one type,
 * or one union segment, per line, with the symbol at the start of each line.
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
 */
class CompoundTypeLayoutSniff implements Sniff
{
    public const string CODE_MULTIPLE_TYPES_ON_LINE = 'MultipleTypesOnLine';

    public const string CODE_SPLIT_INTERSECTION = 'SplitIntersection';

    public const string CODE_INDENT = 'Indent';

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
}
