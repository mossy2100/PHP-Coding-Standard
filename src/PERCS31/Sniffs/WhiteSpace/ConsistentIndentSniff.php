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

        if (!$this->isAfterIndentedTag) {
            parent::process($phpcsFile, $stackPtr);
        }
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
