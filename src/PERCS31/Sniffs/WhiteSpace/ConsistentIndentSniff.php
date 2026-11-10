<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\WhiteSpace;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\OperatorHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PhpCollective\Sniffs\WhiteSpace\ConsistentIndentSniff as PhpCollectiveSniff;

/**
 * Reports a line indented further than its scope, after a complete statement.
 *
 * PER Coding Style 3.1, section 2.4, sets an indent of 4 spaces for each level, and section 5 says a structure's body
 * is indented once. PERCS31.WhiteSpace.ScopeIndent reports too little indentation, and too much on a body's first line.
 * PhpCollective.WhiteSpace.ConsistentIndent reports a later line indented too far, when the line before it ends a
 * statement, including inside a property hook. It skips continuation lines, arrays, closures and switch cases, which it
 * can't judge safely.
 *
 * This subclass only extends the operators that may start a continuation line, which are skipped, to every binary
 * operator section 6.4 allows a statement to be split at. The parent's list predates the pipe operator `|>`, so it
 * reported section 6.4's own example, and also missed `%`, `**`, `instanceof`, bitwise, comparison and assignment
 * operators, and an arrow function's `=>`.
 *
 * The parent skips the body of a closure, anonymous class or switch, as it counts levels from column 0, which is wrong
 * for one inside a split call. This sniff checks them, measuring from the line each one opens on.
 *
 * It also skips the lines under a PHP opening tag that isn't at the start of its line, as in a template. The parent
 * measures from column 1, so it would report PHP indented to match the markup around it.
 *
 * Three more lines are checked, each indented exactly as section 2.4 sets: a value wrapped after `=>` in an array, once
 * past the key's line; a group use's items, once past the `use` line, as section 3's example shows; and dead code after
 * a `break` or other statement that ends a switch case, with no case to measure from, level with that statement.
 */
class ConsistentIndentSniff extends PhpCollectiveSniff
{
    /**
     * Whether the last PHP opening tag was after the start of its line, as in a template.
     */
    private bool $isAfterIndentedTag = false;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [...parent::register(), T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_DOUBLE_ARROW, T_OPEN_USE_GROUP];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    // phpcs:ignore Jumbo.TypeHints.ParameterTypeHint.MissingNativeTypeHint -- The parent has no type, so int is a fatal.
    public function process(File $phpcsFile, $stackPtr): void
    {
        $token = $phpcsFile->getTokens()[$stackPtr];
        if ($token['code'] === T_DOUBLE_ARROW || $token['code'] === T_OPEN_USE_GROUP) {
            if (!$this->isAfterIndentedTag && $token['code'] === T_DOUBLE_ARROW) {
                $this->checkWrappedArrayValue($phpcsFile, $stackPtr);
            } elseif (!$this->isAfterIndentedTag) {
                $this->checkGroupUseItems($phpcsFile, $stackPtr);
            }

            return;
        }

        if ($token['code'] !== T_WHITESPACE) {
            $this->isAfterIndentedTag = $token['column'] > 1;
            return;
        }

        if ($this->isAfterIndentedTag) {
            return;
        }

        // The parent skips these, as it assumes a fixed depth from column 0.
        $anchor = $this->findAnchor($phpcsFile, $stackPtr);
        if ($anchor !== null) {
            $this->checkFromAnchor($phpcsFile, $stackPtr, $anchor);
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * Find the innermost closure, anonymous class or switch around a line's first code.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the whitespace at the start of a line.
     * @return int|null The position of its keyword, or null if there isn't one.
     */
    private function findAnchor(File $phpcsFile, int $stackPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $next = $stackPtr + 1;
        if (!isset($tokens[$next]) || $tokens[$next]['line'] !== $tokens[$stackPtr]['line']) {
            return null;
        }

        foreach (array_reverse($tokens[$next]['conditions'], true) as $ptr => $code) {
            if (is_int($ptr) && in_array($code, [T_CLOSURE, T_ANON_CLASS, T_SWITCH], true)) {
                return $ptr;
            }
        }

        return null;
    }

    /**
     * Report a statement indented too far, measured from the line a closure, anonymous class or switch opens on.
     *
     * Only a line starting a statement after a complete one is checked: a body's first line is left to
     * PERCS31.WhiteSpace.ScopeIndent, as is too little indent.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the whitespace at the start of a line.
     * @param int $anchor The position of the closure, anonymous class or switch keyword.
     */
    private function checkFromAnchor(File $phpcsFile, int $stackPtr, int $anchor): void
    {
        $tokens = $phpcsFile->getTokens();
        $next = $stackPtr + 1;
        if (
            $tokens[$stackPtr]['column'] !== 1
            || isset(Tokens::EMPTY_TOKENS[$tokens[$next]['code']])
            || $tokens[$next]['code'] === T_CLOSE_CURLY_BRACKET
        ) {
            return;
        }

        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($stackPtr - 1), null, true);
        if ($previous === false || !$this->endsStatement($phpcsFile, $previous)) {
            return;
        }

        // Dead code after a `break` is directly in the switch, with no `case` to measure from, so it's level with the
        // statement before it, exactly.
        $found = strlen($tokens[$stackPtr]['content']);
        if ($this->isDeadCodeInSwitch($phpcsFile, $next)) {
            $expected = LayoutHelper::lineIndent($phpcsFile, $this->findStatementStart($phpcsFile, $previous));
            if ($found !== $expected) {
                $this->reportIndent($phpcsFile, $next, $expected, $found);
            }

            return;
        }

        $expected = $this->findExpectedFromAnchor($phpcsFile, $next, $anchor);
        if ($expected === null || $found <= $expected) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Line indented incorrectly; expected %s spaces, found %s spaces',
            $stackPtr,
            'Incorrect',
            [$expected, $found],
        );
        if ($fix) {
            $phpcsFile->fixer->replaceToken($stackPtr, str_repeat(' ', $expected));
        }
    }

    /**
     * Check whether a token ends a statement: a `;`, or the closing brace of a block that isn't part of an expression.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the token.
     * @return bool True if the next code starts a statement.
     */
    private function endsStatement(File $phpcsFile, int $ptr): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$ptr]['code'] === T_SEMICOLON) {
            return !isset($tokens[$ptr]['nested_parenthesis']) || $this->isOutsideOwnParentheses($phpcsFile, $ptr);
        }

        $condition = $tokens[$ptr]['scope_condition'] ?? null;

        return $tokens[$ptr]['code'] === T_CLOSE_CURLY_BRACKET
            && is_int($condition)
            && !in_array($tokens[$condition]['code'], [T_CLOSURE, T_ANON_CLASS, T_MATCH], true);
    }

    /**
     * Check whether a `;` is in a closure or anonymous class inside parentheses, not a `for` header.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the `;`.
     * @return bool True if no parenthesis opens inside the innermost scope around it.
     */
    private function isOutsideOwnParentheses(File $phpcsFile, int $ptr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $conditions = $tokens[$ptr]['conditions'];
        $scope = array_key_last($conditions);
        if (!is_int($scope) || !isset($tokens[$scope]['scope_opener'])) {
            return false;
        }

        return array_key_last($tokens[$ptr]['nested_parenthesis']) < $tokens[$scope]['scope_opener'];
    }

    /**
     * Find the expected indent of a line: the indent of the line the anchor opens on, plus a level for each scope
     * inside it, including the anchor, and for each hook list.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the line's first code.
     * @param int $anchor The position of the closure, anonymous class or switch keyword.
     * @return int|null The number of spaces, or null if it can't be worked out.
     */
    private function findExpectedFromAnchor(File $phpcsFile, int $ptr, int $anchor): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$anchor]['scope_opener'] ?? null;
        $firstOnLine = is_int($opener) ? $phpcsFile->findFirstOnLine(T_WHITESPACE, $opener, true) : false;
        if ($firstOnLine === false) {
            return null;
        }

        $levels = 0;
        foreach (array_keys($tokens[$ptr]['conditions']) as $condition) {
            if ($condition >= $anchor) {
                $levels++;
            }
        }

        // A `case` isn't among the conditions of the `break` that closes it, though it's in its body.
        $condition = $tokens[$ptr]['scope_condition'] ?? null;
        $isCaseCloser = is_int($condition)
            && $tokens[$ptr]['scope_closer'] === $ptr
            && in_array($tokens[$condition]['code'], [T_CASE, T_DEFAULT], true);
        if ($isCaseCloser) {
            $levels++;
        }

        // Dead code after a `break` is directly in the switch, with no `case` to measure from.
        $conditions = $tokens[$ptr]['conditions'];
        $isCaseLabel = in_array($tokens[$ptr]['code'], [T_CASE, T_DEFAULT], true);
        if (!$isCaseCloser && !$isCaseLabel && end($conditions) === T_SWITCH) {
            return null;
        }

        foreach ($this->getUnscopedBraceScopes($phpcsFile, $tokens) as $range) {
            if ($range[0] > $anchor && $ptr > $range[0] && $ptr < $range[1]) {
                $levels++;
            }
        }

        return $tokens[$firstOnLine]['column'] - 1 + ($levels * 4);
    }

    /**
     * Check whether a line starts with an operator that continues the previous line.
     *
     * @param int $nextToken The position of the line's first token.
     * @param array<int, array<string, mixed>> $tokens The file's tokens.
     * @return bool True if the line is a continuation line.
     */
    protected function startsWithContinuationOperator(int $nextToken, array $tokens): bool
    {
        if (parent::startsWithContinuationOperator($nextToken, $tokens)) {
            return true;
        }

        $code = $tokens[$nextToken]['code'];
        if (!is_int($code) && !is_string($code)) {
            return false;
        }

        $operators = [...OperatorHelper::binaryOperators(), ...array_values(Tokens::ASSIGNMENT_TOKENS), T_FN_ARROW];

        return in_array($code, $operators, true);
    }

    /**
     * Check whether a line's first code is dead code directly in a switch, after the statement that ends a case.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the line's first code.
     * @return bool True if it is.
     */
    private function isDeadCodeInSwitch(File $phpcsFile, int $ptr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $conditions = $tokens[$ptr]['conditions'];
        if (end($conditions) !== T_SWITCH || in_array($tokens[$ptr]['code'], [T_CASE, T_DEFAULT], true)) {
            return false;
        }

        // A `case` isn't among the conditions of the `break` that closes it, though it's in its body.
        $condition = $tokens[$ptr]['scope_condition'] ?? null;

        return !is_int($condition) || $tokens[$ptr]['scope_closer'] !== $ptr;
    }

    /**
     * Find the first token of the statement a `;` ends, as in `break;`. PHP_CodeSniffer's findStartOfStatement() goes
     * back to a `case` label from the statement that closes it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $endPtr The position of the `;`.
     * @return int The position of the statement's first token.
     */
    private function findStatementStart(File $phpcsFile, int $endPtr): int
    {
        $tokens = $phpcsFile->getTokens();
        $boundaries = [T_SEMICOLON, T_OPEN_CURLY_BRACKET, T_CLOSE_CURLY_BRACKET, T_COLON, T_OPEN_TAG];
        for ($i = ($endPtr - 1); $i > 0; $i--) {
            if (in_array($tokens[$i]['code'], $boundaries, true)) {
                break;
            }

            // Skip back over anything in brackets, such as a call's arguments split over lines.
            $i = TokenHelper::findOpener($tokens[$i]) ?? $i;
        }

        $start = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), $endPtr, true);

        return $start === false ? $endPtr : $start;
    }

    /**
     * Check a value wrapped onto the line after an array's `=>`, which is indented once past the key's line.
     *
     * A heredoc or nowdoc is left to PERCS31.Strings.HeredocIndentation.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $arrowPtr The position of the `=>`.
     */
    private function checkWrappedArrayValue(File $phpcsFile, int $arrowPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $value = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($arrowPtr + 1), null, true);
        if (
            $value === false
            || $tokens[$value]['line'] === $tokens[$arrowPtr]['line']
            || in_array($tokens[$value]['code'], [T_START_HEREDOC, T_START_NOWDOC], true)
            || $phpcsFile->findFirstOnLine(T_WHITESPACE, $value, true) !== $value
            || !$this->isInArray($phpcsFile, $arrowPtr)
        ) {
            return;
        }

        $expected = LayoutHelper::lineIndent($phpcsFile, $arrowPtr) + LayoutHelper::INDENT;
        $found = $tokens[$value]['column'] - 1;
        if ($found !== $expected) {
            $this->reportIndent($phpcsFile, $value, $expected, $found);
        }
    }

    /**
     * Check whether a `=>` is between an array's keys and values, not in a `foreach` or after `yield`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $arrowPtr The position of the `=>`.
     * @return bool True if it is.
     */
    private function isInArray(File $phpcsFile, int $arrowPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = ($arrowPtr - 1); $i >= 0; $i--) {
            $code = $tokens[$i]['code'];
            if ($code === T_OPEN_SHORT_ARRAY) {
                return true;
            }

            if ($code === T_OPEN_PARENTHESIS) {
                $owner = $tokens[$i]['parenthesis_owner'] ?? null;

                return is_int($owner) && $tokens[$owner]['code'] === T_ARRAY;
            }

            if (in_array($code, [T_OPEN_SQUARE_BRACKET, T_OPEN_CURLY_BRACKET, T_SEMICOLON, T_YIELD], true)) {
                return false;
            }

            // Skip back over anything nested in the key, such as a call's arguments.
            $i = TokenHelper::findOpener($tokens[$i]) ?? $i;
        }

        return false;
    }

    /**
     * Check each line of a group use's items, which is indented once past the `use` line, as section 3's example
     * shows.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openerPtr The position of the group's `{`.
     */
    private function checkGroupUseItems(File $phpcsFile, int $openerPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $phpcsFile->findNext(T_CLOSE_USE_GROUP, ($openerPtr + 1));
        if ($closer === false) {
            return;
        }

        $expected = LayoutHelper::lineIndent($phpcsFile, $openerPtr) + LayoutHelper::INDENT;
        for ($i = ($openerPtr + 1); $i < $closer; $i++) {
            $isLineStart = $tokens[$i]['line'] !== $tokens[($i - 1)]['line'] || $tokens[($i - 1)]['column'] === 1
                && $tokens[($i - 1)]['code'] === T_WHITESPACE && $tokens[($i - 1)]['line'] === $tokens[$i]['line'];
            if (!$isLineStart || isset(Tokens::EMPTY_TOKENS[$tokens[$i]['code']])) {
                continue;
            }

            $found = $tokens[$i]['column'] - 1;
            if ($found !== $expected) {
                $this->reportIndent($phpcsFile, $i, $expected, $found);
            }
        }
    }

    /**
     * Report a line's first code indented wrongly, and fix the indentation.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the line's first code.
     * @param int $expected The expected number of spaces.
     * @param int $found The number of spaces found.
     */
    private function reportIndent(File $phpcsFile, int $ptr, int $expected, int $found): void
    {
        $fix = $phpcsFile->addFixableError(
            'Line indented incorrectly; expected %s spaces, found %s spaces',
            $ptr,
            'Incorrect',
            [$expected, $found],
        );
        if (!$fix) {
            return;
        }

        FixerHelper::setIndent($phpcsFile, $ptr, $expected);
    }
}
