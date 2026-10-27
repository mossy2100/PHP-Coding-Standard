<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\Arrays;

use Mossy2100\Helpers\SingleLineHelper;
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
 * The collapsed array has single spaces between tokens, no space inside brackets or parentheses or around `->`, `?->`
 * and `::`, and no trailing commas, so the fixed line needs no further fixes from the other array sniffs. See
 * SingleLineHelper for the arrays that are never collapsed, such as those containing a comment.
 *
 * Arrays are checked outermost first. If an enclosing array fits on one line, only that array is reported, as fixing it
 * also collapses the nested ones.
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

        $collapsed = SingleLineHelper::collapse($phpcsFile, $stackPtr, $closePtr, $this->maxLineLength);
        if ($collapsed === null) {
            return;
        }

        // Leave it to an enclosing array that fits, since fixing that collapses this one too.
        $outerPtr = $this->findEnclosingArray($phpcsFile, $stackPtr);
        while ($outerPtr !== null) {
            $outerClosePtr = $tokens[$outerPtr]['bracket_closer'];
            if (SingleLineHelper::collapse($phpcsFile, $outerPtr, $outerClosePtr, $this->maxLineLength) !== null) {
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

        SingleLineHelper::replace($phpcsFile, $stackPtr, $closePtr, $collapsed);
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
