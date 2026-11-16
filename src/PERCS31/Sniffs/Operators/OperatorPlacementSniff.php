<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Operators;

use PERCS31\Helpers\CompoundTypeHelper;
use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\OperatorHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\Operators;
use PHPCSUtils\Utils\Parentheses;
use SlevomatCodingStandard\Helpers\TernaryOperatorHelper;

/**
 * Disallows a binary operator at the end of a line, when a statement wraps at it, and requires one at the start of a
 * line to be indented once.
 *
 * PER Coding Style 3.1, section 6.4, says that when a statement is split at an operator, the operator MUST be placed
 * at the beginning of the new line, and each subsequent line is indented once. Section 7.1 says the same of an arrow
 * function's `=>`. This sniff reports:
 * - An operator that's the last thing on its line. The fix moves it to the start of the next line.
 * - An operator at the start of a line that isn't indented once past the line its expression starts on. The
 *   expression starts after the nearest `;`, `,`, opening bracket, or full ternary `?` or `:`. The fix corrects the
 *   indent.
 *
 * The same applies wherever an expression is split in a list item, such as a call argument, array element or `match`
 * arm. The line is indented once past the line its expression starts on, so lining it up with the item is reported.
 *
 * In grouping parentheses, as in `$x = $a && ($b || $c);` split over lines, every line is indented once past the line
 * with the opening parenthesis. So when the group's first value is on the line after the `(`, a wrapped operator lines
 * up with it, as a line in a condition's parentheses does. An arrow function's `=>` and body are the exception: they're
 * indented once past the line with their own `fn`, in a group or not, so a nested arrow function's `=>` is indented
 * once more than the outer one's.
 *
 * A control structure's condition follows section 5 instead: each line is indented at least once past the line with
 * the opening parenthesis. Set requireExactConditionIndent to require exactly once for a line starting with a boolean
 * operator, and exactly once past the line its operand starts on for any other operator, as the Jumbo standard does.
 * An expression nested in the condition, such as a call's argument, follows the general rule. A multi-line `case`
 * condition isn't checked, as section 5.2's example lines its operators up with the first condition.
 *
 * It covers assignment, arithmetic, comparison, bitwise and logical operators, `??`, `instanceof`, the short ternary
 * `?:`, the arrow function's `=>`, the pipe operator `|>` (PHP 8.5, PER Coding Style 3.1), and `.` for the indent only.
 * Two cases are left to inherited sniffs: the placement of `.`, checked by Universal.Operators.ConcatPosition, and of
 * `&&` or `||` in a control structure's condition, checked by PSR12.ControlStructures.BooleanOperatorPlacement. The
 * full ternary has its own check, described below, and section 6.2 doesn't list `=>`, so
 * one in an array or `match` arm isn't checked. An assignment operator or arrow function's `=>` before a heredoc or
 * nowdoc is left to PERCS31.Strings.HeredocLayout, as section 10 says it must begin on the same line as the
 * variable it's set against. An assignment operator before a multi-line array's `[` on the next line is left to
 * PERCS31.Arrays.MultiLineArrayLayout, which joins the `[` to the operator's line.
 *
 * PhpCollective.Operators.OperatorPosition covers some of this, but only about 14 operators, and doesn't check the
 * indent. This sniff doesn't extend it, as only the report and fix for an operator at the end of a line could be left
 * to it, about 30 lines. The exceptions before that, and the indent check, would stay here, and the codes would be
 * the parent's Incorrect and IncorrectWithComment.
 *
 * A short ternary with something between its `?` and `:`, as in `$a ? : $b`, is left to
 * PERCS31.Operators.OperatorSpacing, which joins them. Its placement is checked here once it's written `?:`.
 *
 * A full ternary: section 6.4 says a multi-line ternary MUST occupy 3 lines, never 2. So one split at its `?` or `:`
 * takes three lines, with `?` and `:` each starting a line, indented once, reported as TernaryLayout.
 * SlevomatCodingStandard.ControlStructures.DisallowTrailingMultiLineTernaryOperator only reports a `?` at the end of a
 * line, so it isn't used. This also reports a ternary on two lines, a trailing `:`, and `?` or `:` indented wrongly,
 * and fixes each to the three-line layout.
 * - A ternary is split if there's a line break right before or after its `?` or `:`. A value or condition split over
 *   lines doesn't split it, as in `$a = $b ? [` over several lines to `] : [];`, so it's left alone.
 * - A ternary nested in another's middle operand is checked as its own ternary, indented once past the line it starts
 *   on, as in `? $b` then `? 1` and `: 2` indented once more.
 * - Finding where a ternary starts uses Slevomat's TernaryOperatorHelper, which is marked internal, so check this
 *   sniff after a Slevomat upgrade. It only stops at some tokens, such as `=` and `return`, so it can walk back past
 *   the start of the ternary's statement or `match` arm to an earlier line, which would give the wrong indent. This
 *   sniff also stops at anything else that comes before a ternary's expression, including an enclosing ternary's `?`
 *   or `:`. It finds the `:` itself, as the helper takes the first one, which is a nested ternary's.
 * - PHP_CodeSniffer reads the `?` of a nullable type in a `set` hook's parameter, as in `set(?int $value)`, as a
 *   ternary's. The helper would crash on it, as it has no `:`, so it's skipped.
 */
class OperatorPlacementSniff implements Sniff
{
    public const string CODE_AT_END_OF_LINE = 'AtEndOfLine';

    public const string CODE_INDENT = 'Indent';

    public const string CODE_TERNARY_LAYOUT = 'TernaryLayout';

    /**
     * Whether a line in a control structure's condition must be indented exactly as described, rather than at least
     * once past the line with the opening parenthesis. Section 5 only says "at least once", so it's off by default.
     */
    public bool $requireExactConditionIndent = false;

    /**
     * Control structures whose condition PSR12.ControlStructures.BooleanOperatorPlacement checks.
     *
     * @var list<int|string>
     */
    private const array CONDITION_OWNERS = [T_IF, T_ELSEIF, T_WHILE, T_SWITCH, T_MATCH];

    /**
     * Control structures whose condition can be indented more than once, so its indent isn't checked.
     *
     * @var list<int|string>
     */
    private const array INDENT_EXEMPT_OWNERS = [T_IF, T_ELSEIF, T_WHILE, T_SWITCH, T_MATCH, T_FOR, T_FOREACH];

    /**
     * Tokens that end the expression before an operator, when walking back from it.
     *
     * @var list<int|string>
     */
    private const array EXPRESSION_BOUNDARIES = [
        T_SEMICOLON,
        T_COMMA,
        T_OPEN_PARENTHESIS,
        T_OPEN_SHORT_ARRAY,
        T_OPEN_SQUARE_BRACKET,
        T_OPEN_CURLY_BRACKET,
        T_INLINE_THEN,
        T_INLINE_ELSE,
        T_OPEN_TAG,
        T_OPEN_TAG_WITH_ECHO,
        T_CLOSE_TAG,
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        $assignments = Tokens::ASSIGNMENT_TOKENS;
        unset($assignments[T_DOUBLE_ARROW]);

        return [...OperatorHelper::binaryOperators(), ...array_values($assignments), T_FN_ARROW, T_INLINE_THEN];
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

        // The operator's last token: `?:` is two tokens, and only the short ternary is checked here.
        $lastPtr = $stackPtr;
        if ($code === T_INLINE_THEN) {
            if (!Operators::isShortTernary($phpcsFile, $stackPtr)) {
                $this->processTernary($phpcsFile, $stackPtr);
                return;
            }

            $lastPtr = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
            assert(is_int($lastPtr));
            // A `?` and `:` with something between them are joined by PERCS31.Operators.OperatorSpacing first.
            if ($lastPtr !== ($stackPtr + 1)) {
                return;
            }
        }

        if ($this->isUnaryOrReference($phpcsFile, $stackPtr)) {
            return;
        }

        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($lastPtr + 1), null, true);
        if (
            $next !== false
            && in_array($tokens[$next]['code'], [T_START_HEREDOC, T_START_NOWDOC], true)
            && (isset(Tokens::ASSIGNMENT_TOKENS[$code]) || $code === T_FN_ARROW)
        ) {
            return;
        }

        if ($next === false || $tokens[$next]['line'] === $tokens[$lastPtr]['line']) {
            $this->checkIndent($phpcsFile, $stackPtr, $lastPtr);
            return;
        }

        // A multi-line array's `[` on the next line is joined to the assignment's line by
        // PERCS31.Arrays.MultiLineArrayLayout, giving `$a = [`. Moving the operator down too would give `= [`.
        $closer = $tokens[$next]['bracket_closer'] ?? null;
        if (
            isset(Tokens::ASSIGNMENT_TOKENS[$code])
            && $tokens[$next]['code'] === T_OPEN_SHORT_ARRAY
            && is_int($closer)
            && $tokens[$closer]['line'] !== $tokens[$next]['line']
        ) {
            return;
        }

        if ($code === T_STRING_CONCAT || $this->isLeftToOtherSniffs($phpcsFile, $stackPtr)) {
            return;
        }

        $operator = $phpcsFile->getTokensAsString($stackPtr, ($lastPtr - $stackPtr + 1));
        $error = 'Operator "%s" must be at the start of the next line, not the end of this one';

        // A comment after the operator would be moved with it, or separated from it, so leave that to a person.
        $afterOperator = $phpcsFile->findNext(T_WHITESPACE, ($lastPtr + 1), null, true);
        if ($afterOperator !== $next) {
            $phpcsFile->addError($error, $stackPtr, self::CODE_AT_END_OF_LINE, [$operator]);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, self::CODE_AT_END_OF_LINE, [$operator]);
        if (!$fix) {
            return;
        }

        $before = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        assert(is_int($before));
        $phpcsFile->fixer->beginChangeset();
        for ($i = ($before + 1); $i <= $lastPtr; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContentBefore($next, $operator . ' ');
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Report an operator at the start of a line that isn't indented once past the line its expression starts on.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @param int $lastPtr The position of the operator's last token.
     */
    private function checkIndent(File $phpcsFile, int $stackPtr, int $lastPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($prev === false || $tokens[$prev]['line'] === $tokens[$stackPtr]['line']) {
            return;
        }

        $openers = array_values(array_filter(array_keys($tokens[$stackPtr]['nested_parenthesis'] ?? []), is_int(...)));
        foreach ($openers as $opener) {
            // A multi-line case condition's parentheses have no owner, and its indent isn't checked.
            $beforeOpener = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($opener - 1), null, true);
            if ($beforeOpener !== false && $tokens[$beforeOpener]['code'] === T_CASE) {
                return;
            }
        }

        // An operator directly in a control structure's condition follows section 5, not section 6.4.
        $innermost = Parentheses::getLastOpener($phpcsFile, $stackPtr);
        if ($innermost !== false && Parentheses::isOwnerIn($phpcsFile, $innermost, self::INDENT_EXEMPT_OWNERS)) {
            $this->checkConditionIndent($phpcsFile, $stackPtr, $lastPtr, $innermost);
            return;
        }

        $boundary = $this->findExpressionBoundary($phpcsFile, $stackPtr);
        $start = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($boundary + 1), null, true);
        if ($start === false) {
            return;
        }

        // In grouping parentheses, every line is indented once past the line with the opening parenthesis, so an
        // operator lines up with the group's first line when that's on a line of its own.
        // An arrow function's `=>` and body are indented once past the line its `fn` is on, in a group or not. In a
        // nested arrow function, that's the innermost `fn`.
        $fnPtr = $this->findArrowFunction($phpcsFile, $stackPtr, $boundary);
        $isGroup = $fnPtr === null && $this->isGroupingParenthesis($phpcsFile, $boundary);
        $base = $fnPtr ?? ($isGroup ? $boundary : $start);
        $expected = LayoutHelper::lineIndent($phpcsFile, $base) + LayoutHelper::INDENT;
        $found = $tokens[$stackPtr]['column'] - 1;
        if ($found === $expected) {
            return;
        }

        $error = match (true) {
            $fnPtr !== null => 'A line starting with operator "%s" in an arrow function must be indented once past the '
                . 'line with its "fn"; expected %s spaces, found %s',
            $isGroup => 'A line starting with operator "%s" in parentheses must be indented once past the line with '
                . 'the opening parenthesis; expected %s spaces, found %s',
            default => 'A line starting with operator "%s" must be indented once past the line its expression starts '
                . 'on; expected %s spaces, found %s',
        };
        $this->reportIndent($phpcsFile, $stackPtr, $lastPtr, $error, $expected, $found);
    }

    /**
     * Find the innermost arrow function, starting after a boundary, whose `=>` an operator is, or whose body it's in.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @param int $boundary The position of the token before the operator's expression.
     * @return int|null The position of the arrow function's `fn`, or null if there's none.
     */
    private function findArrowFunction(File $phpcsFile, int $stackPtr, int $boundary): ?int
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = ($stackPtr - 1); $i > $boundary; $i--) {
            $closer = $tokens[$i]['scope_closer'] ?? null;
            if ($tokens[$i]['code'] === T_FN && is_int($closer) && $closer >= $stackPtr) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Check whether a token is a parenthesis that only groups an expression, as in `$a && ($b || $c)`. One that
     * holds a call's arguments, a declaration's parameters or a control structure's condition isn't.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the token, or -1.
     * @return bool True if it is.
     */
    private function isGroupingParenthesis(File $phpcsFile, int $ptr): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($ptr < 0 || $tokens[$ptr]['code'] !== T_OPEN_PARENTHESIS || isset($tokens[$ptr]['parenthesis_owner'])) {
            return false;
        }

        // Parentheses after something that can be called, or that takes arguments, hold arguments.
        return !TokenHelper::isArgumentListOpener($phpcsFile, $ptr);
    }

    /**
     * Report an operator at the start of a line in a control structure's condition that isn't indented as section 5
     * says: at least once past the line with the opening parenthesis, or exactly, if requireExactConditionIndent is
     * set.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @param int $lastPtr The position of the operator's last token.
     * @param int $opener The position of the condition's opening parenthesis.
     */
    private function checkConditionIndent(File $phpcsFile, int $stackPtr, int $lastPtr, int $opener): void
    {
        $tokens = $phpcsFile->getTokens();
        $found = $tokens[$stackPtr]['column'] - 1;
        $minimum = LayoutHelper::lineIndent($phpcsFile, $opener) + LayoutHelper::INDENT;
        if (!$this->requireExactConditionIndent) {
            if ($found < $minimum) {
                $error = 'A line starting with operator "%s" in a condition must be indented at least once past the '
                    . 'line with the opening parenthesis; expected at least %s spaces, found %s';
                $this->reportIndent($phpcsFile, $stackPtr, $lastPtr, $error, $minimum, $found);
            }

            return;
        }

        if (isset(Tokens::BOOLEAN_OPERATORS[$tokens[$stackPtr]['code']])) {
            if ($found !== $minimum) {
                $error = 'A line starting with operator "%s" in a condition must be indented once past the line with '
                    . 'the opening parenthesis; expected %s spaces, found %s';
                $this->reportIndent($phpcsFile, $stackPtr, $lastPtr, $error, $minimum, $found);
            }

            return;
        }

        // Any other operator is indented once past the line its operand starts on, after a boolean operator or the (.
        $start = $this->findOperandStart($phpcsFile, $stackPtr, $opener);
        $expected = LayoutHelper::lineIndent($phpcsFile, $start) + LayoutHelper::INDENT;
        if ($found !== $expected) {
            $error = 'A line starting with operator "%s" must be indented once past the line its expression starts on; '
                . 'expected %s spaces, found %s';
            $this->reportIndent($phpcsFile, $stackPtr, $lastPtr, $error, $expected, $found);
        }
    }

    /**
     * Find the first token of the operand an operator in a condition belongs to: the code after the nearest boolean
     * operator before it, or after the condition's opening parenthesis.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @param int $opener The position of the condition's opening parenthesis.
     * @return int The position of the operand's first token.
     */
    private function findOperandStart(File $phpcsFile, int $stackPtr, int $opener): int
    {
        $tokens = $phpcsFile->getTokens();
        $i = $stackPtr - 1;
        while ($i > $opener && !isset(Tokens::BOOLEAN_OPERATORS[$tokens[$i]['code']])) {
            $i = (TokenHelper::findOpener($tokens[$i]) ?? $i) - 1;
        }

        $start = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), $stackPtr, true);

        return $start === false ? $stackPtr : $start;
    }

    /**
     * Report an operator at the start of a line with the wrong indentation, and fix it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @param int $lastPtr The position of the operator's last token.
     * @param string $error The error message, with placeholders for the operator, and the expected and found indents.
     * @param int $expected The expected number of spaces.
     * @param int $found The number of spaces found.
     */
    private function reportIndent(
        File $phpcsFile,
        int $stackPtr,
        int $lastPtr,
        string $error,
        int $expected,
        int $found,
    ): void {
        $operator = $phpcsFile->getTokensAsString($stackPtr, ($lastPtr - $stackPtr + 1));
        $data = [$operator, $expected, $found];
        FixerHelper::reportIndent($phpcsFile, $stackPtr, $expected, $error, self::CODE_INDENT, $data);
    }

    /**
     * Find the token before the expression an operator belongs to.
     *
     * Walks back over brackets and closure, anonymous class and `match` bodies, stopping at a boundary such as a `;`,
     * a `,`, an opening bracket, a full ternary's `?` or `:`, a `case` label's `:`, or the end of a block.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @return int The position of the boundary, or -1 if the expression starts the file.
     */
    private function findExpressionBoundary(File $phpcsFile, int $stackPtr): int
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = ($stackPtr - 1); $i >= 0; $i--) {
            $code = $tokens[$i]['code'];
            if ($code === T_CLOSE_PARENTHESIS && isset($tokens[$i]['parenthesis_opener'])) {
                $i = $tokens[$i]['parenthesis_opener'];
                continue;
            }

            $isBracketCloser = $code === T_CLOSE_SQUARE_BRACKET || $code === T_CLOSE_SHORT_ARRAY;
            if ($isBracketCloser && isset($tokens[$i]['bracket_opener'])) {
                $i = $tokens[$i]['bracket_opener'];
                continue;
            }

            if ($code === T_CLOSE_CURLY_BRACKET) {
                // The end of a block ends the expression, but a body within it, such as a closure's, doesn't.
                if (OperatorHelper::closesStatementBlock($phpcsFile, $i)) {
                    break;
                }

                $i = $tokens[$i]['bracket_opener'] ?? $i;
                continue;
            }

            if ($code === T_COLON && isset($tokens[$i]['scope_condition'])) {
                // A `case` label, or a control structure in alternative syntax.
                break;
            }

            if (in_array($code, self::EXPRESSION_BOUNDARIES, true) && !Operators::isShortTernary($phpcsFile, $i)) {
                break;
            }
        }

        return max($i, -1);
    }

    /**
     * Check whether an operator is checked by another sniff in the standard.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @return bool True for `&&` or `||` in a control structure's condition.
     */
    private function isLeftToOtherSniffs(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] !== T_BOOLEAN_AND && $tokens[$stackPtr]['code'] !== T_BOOLEAN_OR) {
            return false;
        }

        return Parentheses::getFirstOwner($phpcsFile, $stackPtr, self::CONDITION_OWNERS) !== false;
    }

    /**
     * Check whether a `+`, `-` or `&` is unary or a reference, or a `|` or `&` is in a `set` hook's parameter type, not
     * a binary operator.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @return bool True if it isn't a binary operator.
     */
    private function isUnaryOrReference(File $phpcsFile, int $stackPtr): bool
    {
        if (
            PropertyHookHelper::isHookParameterType($phpcsFile, $stackPtr)
            || CompoundTypeHelper::isCatchSeparator($phpcsFile, $stackPtr)
        ) {
            return true;
        }

        // OperatorHelper::isUnary() finds a reference `&`, including a by-reference hook's.
        $code = $phpcsFile->getTokens()[$stackPtr]['code'];

        return ($code === T_PLUS || $code === T_MINUS || $code === T_BITWISE_AND)
            && OperatorHelper::isUnary($phpcsFile, $stackPtr);
    }

    /**
     * Check a full ternary split at its `?` or `:`: it takes three lines, with `?` and `:` each starting a line,
     * indented once past the line the ternary starts on.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $inlineThenPointer The position of the `?`.
     */
    private function processTernary(File $phpcsFile, int $inlineThenPointer): void
    {
        if (PropertyHookHelper::isHookParameterType($phpcsFile, $inlineThenPointer)) {
            return;
        }

        // A short ternary, which PER treats as a binary operator.
        if (Operators::isShortTernary($phpcsFile, $inlineThenPointer)) {
            return;
        }

        $inlineElsePointer = $this->findElse($phpcsFile, $inlineThenPointer);
        if ($inlineElsePointer === null) {
            return;
        }

        $startPointer = $this->findStart(
            $phpcsFile,
            $inlineThenPointer,
            TernaryOperatorHelper::getStartPointer($phpcsFile, $inlineThenPointer),
        );
        if (
            !$this->isSplitAtOperator($phpcsFile, $inlineThenPointer)
            && !$this->isSplitAtOperator($phpcsFile, $inlineElsePointer)
        ) {
            return;
        }

        // One level past the indentation of the line the ternary starts on. A level is always 4 spaces, as PER Coding
        // Style 3.1, section 2.4, requires, so ScopeIndent's indent property isn't used.
        $indentation = str_repeat(' ', LayoutHelper::lineIndent($phpcsFile, $startPointer) + LayoutHelper::INDENT);

        if (
            $this->startsLineWithIndentation($phpcsFile, $inlineThenPointer, $indentation)
            && $this->startsLineWithIndentation($phpcsFile, $inlineElsePointer, $indentation)
        ) {
            return;
        }

        $error = 'A ternary split at "?" or ":" must take three lines, with each starting a line, indented once';
        $operators = [$inlineThenPointer, $inlineElsePointer];
        $isFixable = !$this->hasCommentAround($phpcsFile, $operators);
        $code = self::CODE_TERNARY_LAYOUT;
        if (!FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $inlineThenPointer, $code)) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        foreach ($operators as $operator) {
            // A new line and the indentation before the operator, and one space after it.
            $before = $phpcsFile->findPrevious(T_WHITESPACE, ($operator - 1), null, true);
            $after = $phpcsFile->findNext(T_WHITESPACE, ($operator + 1), null, true);
            assert(is_int($before) && is_int($after));
            FixerHelper::blankBetween($phpcsFile, $before, $operator);

            FixerHelper::blankBetween($phpcsFile, $operator, $after);

            $phpcsFile->fixer->addContentBefore($operator, $phpcsFile->eolChar . $indentation);
            $phpcsFile->fixer->addContent($operator, ' ');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check whether there's a line break right before or after an operator, ignoring comments.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $operator The position of the `?` or `:`.
     * @return bool True if the code before or after the operator is on another line.
     */
    private function isSplitAtOperator(File $phpcsFile, int $operator): bool
    {
        $tokens = $phpcsFile->getTokens();
        $before = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($operator - 1), null, true);
        $after = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($operator + 1), null, true);

        return ($before !== false && $tokens[$before]['line'] !== $tokens[$operator]['line'])
            || ($after !== false && $tokens[$after]['line'] !== $tokens[$operator]['line']);
    }

    /**
     * Find where a ternary starts: no earlier than the token that comes before its expression.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $inlineThenPointer The position of the `?`.
     * @param int $startPointer The position Slevomat's helper found.
     * @return int The position of the ternary's first token.
     */
    private function findStart(File $phpcsFile, int $inlineThenPointer, int $startPointer): int
    {
        $tokens = $phpcsFile->getTokens();
        $boundaries = [
            ...array_values(Tokens::ASSIGNMENT_TOKENS),
            T_SEMICOLON,
            T_OPEN_CURLY_BRACKET,
            T_CLOSE_CURLY_BRACKET,
            T_COMMA,
            T_MATCH_ARROW,
            T_ECHO,
            T_PRINT,
            T_YIELD,
            T_YIELD_FROM,
        ];

        // A ternary nested in another's branch starts after the enclosing ternary's `?` or `:`.
        $closers = [T_CLOSE_PARENTHESIS, T_CLOSE_SQUARE_BRACKET, T_CLOSE_SHORT_ARRAY];
        for ($i = ($inlineThenPointer - 1); $i >= $startPointer; $i--) {
            // Brackets before the `?` are part of the condition, whatever's inside them.
            $code = $tokens[$i]['code'];
            $isDynamicName = $code === T_CLOSE_CURLY_BRACKET && !isset($tokens[$i]['scope_condition']);
            if ($isDynamicName || in_array($code, $closers, true)) {
                $jumpTo = TokenHelper::findOpener($tokens[$i]);
                if (is_int($jumpTo)) {
                    $i = $jumpTo;
                    continue;
                }
            }

            $isTernary = isset(Collections::ternaryOperators()[$code]);
            $isEnclosingTernary = $isTernary && !Operators::isShortTernary($phpcsFile, $i);
            if ($isEnclosingTernary || in_array($code, $boundaries, true)) {
                $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), $inlineThenPointer, true);

                return $first === false ? $startPointer : $first;
            }
        }

        return $startPointer;
    }

    /**
     * Find a ternary's `:`, skipping any ternary nested in its middle operand, and anything in brackets or a body.
     *
     * Slevomat's TernaryOperatorHelper::getElsePointer() takes the first `:`, which is a nested ternary's.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $inlineThenPointer The position of the `?`.
     * @return int|null The position of the `:`, or null if there isn't one.
     */
    private function findElse(File $phpcsFile, int $inlineThenPointer): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $depth = 0;
        for ($i = ($inlineThenPointer + 1); $i < $phpcsFile->numTokens; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_SEMICOLON || in_array($code, [T_CLOSE_PARENTHESIS, T_CLOSE_SQUARE_BRACKET], true)) {
                return null;
            }

            $closer = TokenHelper::findCloser($tokens[$i]);
            if (is_int($closer) && $closer > $i) {
                $i = $closer;
                continue;
            }

            if (($code !== T_INLINE_THEN && $code !== T_INLINE_ELSE) || Operators::isShortTernary($phpcsFile, $i)) {
                continue;
            }

            if ($code === T_INLINE_THEN) {
                $depth++;
                continue;
            }

            if ($depth === 0) {
                return $i;
            }

            $depth--;
        }

        return null;
    }

    /**
     * Check whether an operator is the first token on its line, with the given indentation.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $operator The position of the `?` or `:`.
     * @param string $indentation The expected indentation.
     * @return bool True if the operator starts its line with that indentation.
     */
    private function startsLineWithIndentation(File $phpcsFile, int $operator, string $indentation): bool
    {
        return LayoutHelper::startsLine($phpcsFile, $operator)
            && $phpcsFile->getTokens()[$operator]['column'] - 1 === strlen($indentation);
    }

    /**
     * Check whether a comment is next to any of the operators, so moving them could move it to the wrong place.
     *
     * @param File $phpcsFile The file being scanned.
     * @param list<int> $operators The positions of the `?` and `:`.
     * @return bool True if a comment comes right before or after an operator.
     */
    private function hasCommentAround(File $phpcsFile, array $operators): bool
    {
        $tokens = $phpcsFile->getTokens();
        foreach ($operators as $operator) {
            $before = $phpcsFile->findPrevious(T_WHITESPACE, ($operator - 1), null, true);
            $after = $phpcsFile->findNext(T_WHITESPACE, ($operator + 1), null, true);
            foreach ([$before, $after] as $pointer) {
                if ($pointer !== false && isset(Tokens::COMMENT_TOKENS[$tokens[$pointer]['code']])) {
                    return true;
                }
            }
        }

        return false;
    }
}
