<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires a multi-line array to be written on one line if it fits.
 *
 * The rest of the array layout is left to stock sniffs (see the ruleset). This sniff fills the one gap they leave: none
 * of them collapses a multi-line array that would fit within the line length.
 *
 * An array fits if the whole resulting line does: the text before the opening bracket, the collapsed array, and the
 * text after the closing bracket on its line. This means the fix can never create a line-length error.
 *
 * The collapsed array has single spaces between tokens, no space inside brackets or parentheses, and no trailing
 * commas, so the fixed line needs no further fixes from the other array sniffs.
 *
 * Arrays are checked outermost first. If an enclosing array fits on one line, only that array is reported, as fixing it
 * also collapses the nested ones.
 *
 * An array is never collapsed if it contains:
 * - A comment, which would be lost or would comment out the rest of the line.
 * - A heredoc, nowdoc, or multi-line string, whose line breaks are part of the value.
 * - A brace body, such as a closure, anonymous class or match expression, which is harder to read on one line.
 */
class ArrayDeclarationSniff implements Sniff
{
    public const string CODE_SHOULD_BE_SINGLE_LINE = 'ShouldBeSingleLine';

    /**
     * Maximum line length for a collapsed array.
     */
    public int $maxLineLength = 120;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_OPEN_SHORT_ARRAY];
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
        if (!isset($tokens[$stackPtr]['bracket_closer'])) {
            return;
        }

        $closePtr = $tokens[$stackPtr]['bracket_closer'];
        if ($tokens[$stackPtr]['line'] === $tokens[$closePtr]['line']) {
            return;
        }

        $collapsed = $this->collapse($phpcsFile, $stackPtr);
        if ($collapsed === null) {
            return;
        }

        // Leave it to an enclosing array that fits, since fixing that collapses this one too.
        $outerPtr = $this->findEnclosingArray($phpcsFile, $stackPtr);
        while ($outerPtr !== null) {
            if ($this->collapse($phpcsFile, $outerPtr) !== null) {
                return;
            }
            $outerPtr = $this->findEnclosingArray($phpcsFile, $outerPtr);
        }

        $fix = $phpcsFile->addFixableError(
            'Multi-line array fits within %s characters, so it must be written on one line.',
            $stackPtr,
            self::CODE_SHOULD_BE_SINGLE_LINE,
            [$this->maxLineLength]
        );
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($stackPtr, $collapsed);
        for ($i = $stackPtr + 1; $i <= $closePtr; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Get an array written on one line, if it can be collapsed and the resulting line fits.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openPtr The position of the opening bracket.
     * @return ?string The collapsed array, or null if it can't be collapsed or doesn't fit.
     */
    private function collapse(File $phpcsFile, int $openPtr): ?string
    {
        $tokens = $phpcsFile->getTokens();
        $closePtr = $tokens[$openPtr]['bracket_closer'];
        $openers = [T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET, T_OPEN_PARENTHESIS, T_ATTRIBUTE];
        $closers = [T_CLOSE_SHORT_ARRAY, T_CLOSE_SQUARE_BRACKET, T_CLOSE_PARENTHESIS];
        $blocked = [
            T_COMMENT,
            T_DOC_COMMENT_OPEN_TAG,
            T_PHPCS_DISABLE,
            T_PHPCS_ENABLE,
            T_PHPCS_IGNORE,
            T_PHPCS_IGNORE_FILE,
            T_PHPCS_SET,
            T_START_HEREDOC,
            T_START_NOWDOC,
            T_OPEN_CURLY_BRACKET,
        ];

        $result = '';
        $pendingSpace = false;
        for ($i = $openPtr; $i <= $closePtr; $i++) {
            $code = $tokens[$i]['code'];
            if (in_array($code, $blocked, true)) {
                return null;
            }

            if ($code === T_WHITESPACE) {
                $pendingSpace = true;
                continue;
            }

            // A line break inside any other token, such as a multi-line string, is part of the value.
            if (str_contains($tokens[$i]['content'], "\n")) {
                return null;
            }

            // Drop trailing commas.
            if ($code === T_COMMA) {
                $nextPtr = $phpcsFile->findNext(T_WHITESPACE, $i + 1, $closePtr + 1, true);
                if ($nextPtr !== false && in_array($tokens[$nextPtr]['code'], $closers, true)) {
                    continue;
                }
            }

            // One space between tokens, but none just inside brackets and parentheses.
            if ($pendingSpace && $result !== '') {
                $prevCode = $tokens[$phpcsFile->findPrevious(T_WHITESPACE, $i - 1, $openPtr, true)]['code'];
                if (!in_array($prevCode, $openers, true) && !in_array($code, $closers, true)) {
                    $result .= ' ';
                }
            }
            $pendingSpace = false;
            $result .= $tokens[$i]['content'];
        }

        // Measure the whole resulting line.
        $after = '';
        for ($i = $closePtr + 1; isset($tokens[$i]) && !str_contains($tokens[$i]['content'], "\n"); $i++) {
            $after .= $tokens[$i]['content'];
        }
        $lineLength = $tokens[$openPtr]['column'] - 1 + mb_strlen($result) + mb_strlen(rtrim($after));

        return $lineLength <= $this->maxLineLength ? $result : null;
    }

    /**
     * Find the nearest short array that contains the given one, within the same statement.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openPtr The position of the opening bracket.
     * @return ?int The position of the enclosing array's opening bracket, or null if there is none.
     */
    private function findEnclosingArray(File $phpcsFile, int $openPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = $openPtr - 1; $i >= 0; $i--) {
            $code = $tokens[$i]['code'];

            // Skip over complete bracketed groups before this array, such as earlier elements.
            if ($code === T_CLOSE_SHORT_ARRAY || $code === T_CLOSE_SQUARE_BRACKET) {
                $i = $tokens[$i]['bracket_opener'];
                continue;
            }
            if ($code === T_CLOSE_PARENTHESIS) {
                $i = $tokens[$i]['parenthesis_opener'];
                continue;
            }
            if ($code === T_CLOSE_CURLY_BRACKET && isset($tokens[$i]['bracket_opener'])) {
                $i = $tokens[$i]['bracket_opener'];
                continue;
            }

            if ($code === T_OPEN_SHORT_ARRAY) {
                return $i;
            }

            // Stop at the start of the statement or block.
            if ($code === T_SEMICOLON || $code === T_OPEN_CURLY_BRACKET || $code === T_OPEN_TAG) {
                return null;
            }
        }

        return null;
    }
}
