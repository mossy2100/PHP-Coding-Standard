<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Operators;

use PERCS31\Helpers\OperatorHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

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
 * PER says nothing about an operator wrapped inside a list item, such as a call argument, array element or `match`
 * arm, as that isn't a statement. So there the line may also be lined up with the item, unless requireListItemIndent
 * is set. An arrow function's `=>` is always indented once, as section 7.1 says. Grouping parentheses, as in
 * `$x = ($a && $b);` split over lines, aren't a list, and PER says nothing about them either, so like a condition,
 * their indent isn't checked, unless requireGroupingLineUp is set. Then a line starting with an operator lines up with
 * the group's first line, or if that's on the `(` line, is indented once past it. An arrow function's body keeps its
 * indent, as it continues the arrow function, not the group.
 *
 * It covers assignment, arithmetic, comparison, bitwise and logical operators, `??`, `instanceof`, the short ternary
 * `?:`, the arrow function's `=>`, the pipe operator `|>` (PHP 8.5, PER Coding Style 3.1), and `.` for the indent only.
 * Two cases are left to inherited sniffs: the placement of `.`, checked by Universal.Operators.ConcatPosition, and of
 * `&&` or `||` in a control structure's condition, checked by PSR12.ControlStructures.BooleanOperatorPlacement. The
 * indent anywhere in a control structure's condition, or a multi-line case condition, isn't checked, as section 5
 * says it's indented at least once, and section 5.2's case example lines the operators up with the first condition. A
 * full ternary is checked by PERCS31.ControlStructures.MultiLineTernaryOperator, and section 6.2 doesn't list `=>`, so
 * one in an array or `match` arm isn't checked. An assignment operator or arrow function's `=>` before a heredoc or
 * nowdoc is left to PERCS31.Strings.HeredocIndentation, as section 10 says it must begin on the same line as the
 * variable it's set against.
 */
class OperatorPlacementSniff implements Sniff
{
    public const string CODE_AT_END_OF_LINE = 'AtEndOfLine';

    public const string CODE_INDENT = 'Indent';

    /**
     * Whether a wrapped line in a list item, such as a call argument, must be indented once past the item. PER says
     * nothing about it, so by default the line may also be lined up with the item.
     */
    public bool $requireListItemIndent = false;

    /**
     * Whether a wrapped line in grouping parentheses must line up with the group's first line, with no extra indent.
     * PER says nothing about it, so by default its indent isn't checked.
     */
    public bool $requireGroupingLineUp = false;

    /**
     * The number of spaces in one indent.
     */
    private const int INDENT = 4;

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
     * Tokens other than a function name that can come before a call's `(`, as in `$f()`, `$a->b()()` or `clone($a)`.
     *
     * @var list<int|string>
     */
    private const array CALLABLE_ENDS = [
        T_VARIABLE,
        T_CLOSE_PARENTHESIS,
        T_CLOSE_SQUARE_BRACKET,
        T_CLOSE_CURLY_BRACKET,
        T_LIST,
        T_CLONE,
    ];

    /**
     * Constructs whose body can be part of an expression.
     *
     * @var list<int|string>
     */
    private const array EXPRESSION_BODIES = [T_CLOSURE, T_ANON_CLASS, T_MATCH];

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
        $tokens = Tokens::ASSIGNMENT_TOKENS + Tokens::COMPARISON_TOKENS + Tokens::OPERATORS
            + Tokens::BOOLEAN_OPERATORS;
        unset($tokens[T_DOUBLE_ARROW]);

        $targets = [...array_values($tokens), T_INSTANCEOF, T_FN_ARROW, T_INLINE_THEN, T_STRING_CONCAT];
        if (defined('T_PIPE')) {
            $targets[] = T_PIPE;
        }

        return $targets;
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
            if ($tokens[($stackPtr + 1)]['code'] !== T_INLINE_ELSE) {
                return;
            }

            $lastPtr = $stackPtr + 1;
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

        foreach (array_keys($tokens[$stackPtr]['nested_parenthesis'] ?? []) as $opener) {
            if (!is_int($opener)) {
                continue;
            }

            $owner = $tokens[$opener]['parenthesis_owner'] ?? null;
            if ($owner !== null && in_array($tokens[$owner]['code'], self::INDENT_EXEMPT_OWNERS, true)) {
                return;
            }

            // A multi-line case condition's parentheses have no owner, but the condition is exempt too.
            $beforeOpener = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($opener - 1), null, true);
            if ($beforeOpener !== false && $tokens[$beforeOpener]['code'] === T_CASE) {
                return;
            }
        }

        $boundary = $this->findExpressionBoundary($phpcsFile, $stackPtr);
        $start = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($boundary + 1), null, true);
        if ($start === false) {
            return;
        }

        // An arrow function's => is indented once, as section 7.1 says, wherever the arrow function is.
        $isListItem = false;
        $isGroup = false;
        if ($tokens[$stackPtr]['code'] !== T_FN_ARROW) {
            $opener = $this->findEnclosingOpener($phpcsFile, $boundary);
            if ($opener !== null && $this->isGroupingOpener($phpcsFile, $opener)) {
                if (!$this->requireGroupingLineUp) {
                    return;
                }

                $isGroup = !$this->isInArrowFunction($phpcsFile, $opener, $stackPtr);
            } else {
                $isListItem = $opener !== null;
            }
        }

        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $start, true);
        $itemIndent = ($firstOnLine === false ? 1 : $tokens[$firstOnLine]['column']) - 1;
        $mustLineUp = $isGroup && $firstOnLine === $start;
        $expected = $itemIndent + ($mustLineUp ? 0 : self::INDENT);
        $found = $tokens[$stackPtr]['column'] - 1;

        // A list item that starts its own line may have its wrapped lines lined up with it, unless the standard
        // requires them indented.
        $allowLinedUp = $isListItem && !$this->requireListItemIndent && $firstOnLine === $start;
        if ($found === $expected || ($allowLinedUp && $found === $itemIndent)) {
            return;
        }

        $operator = $phpcsFile->getTokensAsString($stackPtr, ($lastPtr - $stackPtr + 1));
        if ($allowLinedUp) {
            $error = 'A line starting with operator "%s" must be lined up with its list item, or indented once past '
                . 'it; expected %s or %s spaces, found %s';
            $data = [$operator, $itemIndent, $expected, $found];
        } elseif ($mustLineUp) {
            $error = 'A line starting with operator "%s" must be lined up with the first line in its parentheses; '
                . 'expected %s spaces, found %s';
            $data = [$operator, $expected, $found];
        } else {
            $error = 'A line starting with operator "%s" must be indented once past the line its expression starts '
                . 'on; expected %s spaces, found %s';
            $data = [$operator, $expected, $found];
        }

        if (!$phpcsFile->addFixableError($error, $stackPtr, self::CODE_INDENT, $data)) {
            return;
        }

        if ($found === 0) {
            $phpcsFile->fixer->addContentBefore($stackPtr, str_repeat(' ', $expected));
        } else {
            $phpcsFile->fixer->replaceToken(($stackPtr - 1), str_repeat(' ', $expected));
        }
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
                $condition = $tokens[$i]['scope_condition'] ?? null;
                if ($condition !== null && !in_array($tokens[$condition]['code'], self::EXPRESSION_BODIES, true)) {
                    break;
                }

                $i = $tokens[$i]['bracket_opener'] ?? $i;
                continue;
            }

            if ($code === T_COLON && isset($tokens[$i]['scope_condition'])) {
                // A `case` label, or a control structure in alternative syntax.
                break;
            }

            if (in_array($code, self::EXPRESSION_BOUNDARIES, true) && !$this->isShortTernary($phpcsFile, $i)) {
                break;
            }
        }

        return max($i, -1);
    }

    /**
     * Find the bracket that opens the list or group an expression is in, from the expression's boundary.
     *
     * A comma boundary means the expression is a later item in a list, so the walk continues back to the list's
     * opener.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $boundary The position of the token before the expression, from findExpressionBoundary().
     * @return int|null The position of a `(`, `[` or `match` body's `{`, or null if the expression is a statement.
     */
    private function findEnclosingOpener(File $phpcsFile, int $boundary): ?int
    {
        $tokens = $phpcsFile->getTokens();
        while ($boundary >= 0 && $tokens[$boundary]['code'] === T_COMMA) {
            $boundary = $this->findExpressionBoundary($phpcsFile, $boundary);
        }

        if ($boundary < 0) {
            return null;
        }

        $code = $tokens[$boundary]['code'];
        if (in_array($code, [T_OPEN_PARENTHESIS, T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET], true)) {
            return $boundary;
        }

        $condition = $tokens[$boundary]['scope_condition'] ?? null;
        $isMatchBody = $code === T_OPEN_CURLY_BRACKET && $condition !== null && $tokens[$condition]['code'] === T_MATCH;

        return $isMatchBody ? $boundary : null;
    }

    /**
     * Check whether a bracket only groups an expression, so isn't a list that can end in a comma.
     *
     * That's a `(` that doesn't belong to a call, declaration or construct, as in `$x = ($a && $b);`, or an array
     * index's `[`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the bracket.
     * @return bool True if it's a grouping bracket.
     */
    private function isGroupingOpener(File $phpcsFile, int $opener): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$opener]['code'] === T_OPEN_SQUARE_BRACKET) {
            return true;
        }

        if ($tokens[$opener]['code'] !== T_OPEN_PARENTHESIS || isset($tokens[$opener]['parenthesis_owner'])) {
            return false;
        }

        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($opener - 1), null, true);
        if ($previous === false) {
            return true;
        }

        $code = $tokens[$previous]['code'];

        return !isset(Tokens::FUNCTION_NAME_TOKENS[$code]) && !in_array($code, self::CALLABLE_ENDS, true);
    }

    /**
     * Check whether an operator is in the body of an arrow function inside a group.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the group's opening bracket.
     * @param int $stackPtr The position of the operator.
     * @return bool True if it is.
     */
    private function isInArrowFunction(File $phpcsFile, int $opener, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $arrowFunction = $phpcsFile->findNext(T_FN, ($opener + 1), $stackPtr);
        while ($arrowFunction !== false) {
            if (($tokens[$arrowFunction]['scope_closer'] ?? 0) >= $stackPtr) {
                return true;
            }

            $arrowFunction = $phpcsFile->findNext(T_FN, ($arrowFunction + 1), $stackPtr);
        }

        return false;
    }

    /**
     * Check whether a token is part of a short ternary `?:`, which, unlike a full ternary, doesn't start an expression.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token.
     */
    private function isShortTernary(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $code = $tokens[$stackPtr]['code'];

        return ($code === T_INLINE_THEN && $tokens[($stackPtr + 1)]['code'] === T_INLINE_ELSE)
            || ($code === T_INLINE_ELSE && $tokens[($stackPtr - 1)]['code'] === T_INLINE_THEN);
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

        foreach (array_keys($tokens[$stackPtr]['nested_parenthesis'] ?? []) as $opener) {
            $owner = $tokens[$opener]['parenthesis_owner'] ?? null;
            if ($owner !== null && in_array($tokens[$owner]['code'], self::CONDITION_OWNERS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether a `+`, `-` or `&` is unary, or a reference, not a binary operator.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @return bool True if it isn't a binary operator.
     */
    private function isUnaryOrReference(File $phpcsFile, int $stackPtr): bool
    {
        if (PropertyHookHelper::isHookReference($phpcsFile, $stackPtr)) {
            return true;
        }

        $code = $phpcsFile->getTokens()[$stackPtr]['code'];
        if ($code === T_BITWISE_AND && $phpcsFile->isReference($stackPtr)) {
            return true;
        }

        return ($code === T_PLUS || $code === T_MINUS || $code === T_BITWISE_AND)
            && OperatorHelper::isUnary($phpcsFile, $stackPtr);
    }
}
