<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\WhiteSpace;

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
        return [...parent::register(), T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO];
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

        $expected = $this->findExpectedFromAnchor($phpcsFile, $next, $anchor);
        $found = strlen($tokens[$stackPtr]['content']);
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

        $operators = Tokens::OPERATORS + Tokens::COMPARISON_TOKENS + Tokens::ASSIGNMENT_TOKENS
            + Tokens::BOOLEAN_OPERATORS;

        return isset($operators[$code])
            || in_array($code, [T_INSTANCEOF, T_FN_ARROW, T_COALESCE, T_STRING_CONCAT], true)
            || (defined('T_PIPE') && $code === T_PIPE);
    }
}
