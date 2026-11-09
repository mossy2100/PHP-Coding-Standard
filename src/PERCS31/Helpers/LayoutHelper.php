<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;

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
}
