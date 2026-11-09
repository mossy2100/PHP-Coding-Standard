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
}
