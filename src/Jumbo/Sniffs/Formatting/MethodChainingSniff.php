<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Formatting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires the first method of a method chain split across lines to be on a line of its own.
 *
 * PER Coding Style 3.1, section 4.7, says method chaining MAY be put on separate lines, each indented once, and that
 * when it is, the first method MUST be on the next line. PEAR.WhiteSpace.ObjectOperatorIndent checks the indentation,
 * but lets the first method stay on the line with the object. This sniff reports that, and the fix moves it to the next
 * line, indented once.
 *
 * A chain is every `->` and `?->` in a statement at the same parenthesis depth, as in the PEAR sniff. It's split if any
 * of them starts a line. Property accesses before the first method, as in `$this->query`, can stay on the first line.
 */
class MethodChainingSniff implements Sniff
{
    public const string CODE_FIRST_METHOD_ON_SAME_LINE = 'FirstMethodOnSameLine';

    /**
     * The number of spaces in one indent.
     */
    private const int INDENT = 4;

    /**
     * The object operators that link a chain.
     */
    private const array OPERATORS = [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return self::OPERATORS;
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $chain = $this->findChain($phpcsFile, $stackPtr);

        // Check each chain once, from its first operator.
        if ($chain[0] !== $stackPtr || !$this->isSplit($phpcsFile, $chain)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        foreach ($chain as $operator) {
            $name = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($operator + 1), null, true);
            if ($name === false) {
                return;
            }

            $paren = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($name + 1), null, true);
            if ($paren === false || $tokens[$paren]['code'] !== T_OPEN_PARENTHESIS) {
                // A property access, not a method call.
                continue;
            }

            if (!$this->startsLine($phpcsFile, $operator)) {
                $this->report($phpcsFile, $operator);
            }

            return;
        }
    }

    /**
     * Find the object operators in the same statement, and at the same parenthesis depth, as the given one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of an object operator.
     * @return non-empty-list<int> The positions of the operators, in order.
     */
    private function findChain(File $phpcsFile, int $stackPtr): array
    {
        $tokens = $phpcsFile->getTokens();
        $start = $phpcsFile->findStartOfStatement($stackPtr);
        $end = $phpcsFile->findEndOfStatement($stackPtr);
        $brackets = $tokens[$stackPtr]['nested_parenthesis'] ?? null;
        $conditions = $tokens[$stackPtr]['conditions'];

        $chain = [];
        for ($i = $start; $i <= $end; $i++) {
            if (
                in_array($tokens[$i]['code'], self::OPERATORS, true)
                && ($tokens[$i]['nested_parenthesis'] ?? null) === $brackets
                && $tokens[$i]['conditions'] === $conditions
            ) {
                $chain[] = $i;
            }
        }

        return $chain === [] ? [$stackPtr] : $chain;
    }

    /**
     * Check if any operator in a chain starts a line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param list<int> $chain The positions of the chain's operators.
     */
    private function isSplit(File $phpcsFile, array $chain): bool
    {
        foreach ($chain as $operator) {
            if ($this->startsLine($phpcsFile, $operator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a token is the first non-whitespace token on its line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token.
     */
    private function startsLine(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);

        return $prev === false || $tokens[$prev]['line'] !== $tokens[$stackPtr]['line'];
    }

    /**
     * Report a first method on the same line as the object, and fix it by moving it to the next line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $operator The position of the first method's object operator.
     */
    private function report(File $phpcsFile, int $operator): void
    {
        $fix = $phpcsFile->addFixableError(
            'The first method of a chain split across lines must be on the next line',
            $operator,
            self::CODE_FIRST_METHOD_ON_SAME_LINE,
        );
        if (!$fix) {
            return;
        }

        // Indent once past the line the statement starts on, as PEAR.WhiteSpace.ObjectOperatorIndent expects.
        $tokens = $phpcsFile->getTokens();
        $start = $phpcsFile->findStartOfStatement($operator);
        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $start, true);
        $column = $firstOnLine === false ? 1 : $tokens[$firstOnLine]['column'];
        $break = $phpcsFile->eolChar . str_repeat(' ', ($column - 1 + self::INDENT));

        if ($tokens[($operator - 1)]['code'] === T_WHITESPACE) {
            $phpcsFile->fixer->replaceToken(($operator - 1), $break);
        } else {
            $phpcsFile->fixer->addContentBefore($operator, $break);
        }
    }
}
