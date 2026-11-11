<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Helper class for indentation and line layout.
 */
class LayoutHelper
{
    /**
     * The number of spaces in one indent level. PER Coding Style 3.1, section 2.4, sets 4.
     */
    public const int INDENT = 4;

    /**
     * Find the indentation of the line a token is on.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the token.
     * @return int The number of spaces before the line's first code.
     */
    public static function lineIndent(File $phpcsFile, int $ptr): int
    {
        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $ptr, true);

        return ($firstOnLine === false ? 1 : $phpcsFile->getTokens()[$firstOnLine]['column']) - 1;
    }

    /**
     * Check whether a token is the first code on its line, with only whitespace before it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the token.
     * @return bool True if it is.
     */
    public static function startsLine(File $phpcsFile, int $ptr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($ptr - 1), null, true);

        return $previous === false || $tokens[$previous]['line'] !== $tokens[$ptr]['line'];
    }

    /**
     * Check whether a list in brackets is split across lines: its first item starts on a line after the opening
     * bracket, or its closing bracket is on a line after the last item.
     *
     * A list on one line isn't split. Nor is one whose only line breaks are inside an item, as in `foo([` ... `]);`, as
     * PER Coding Style 3.1, section 4.7, says a single argument split across lines doesn't split the argument list.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the opening bracket.
     * @param int $closer The position of the closing bracket.
     * @return bool True if the list is split.
     */
    public static function isSplitList(File $phpcsFile, int $opener, int $closer): bool
    {
        $tokens = $phpcsFile->getTokens();
        $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($opener + 1), $closer, true);
        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($closer - 1), $opener, true);
        if ($first === false || $last === false) {
            return false;
        }

        return $tokens[$first]['line'] !== $tokens[$opener]['line']
            || $tokens[$last]['line'] !== $tokens[$closer]['line'];
    }
}
