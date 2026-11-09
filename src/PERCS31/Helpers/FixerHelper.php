<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;

/**
 * Helper class for common fixes.
 */
class FixerHelper
{
    /**
     * Empty every token between two tokens, not including them.
     *
     * It doesn't start a changeset, so it can be used inside one. PHP_CodeSniffer's beginChangeset() drops the changes
     * of a changeset that's already open.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $from The token before the gap.
     * @param int $to The token after the gap.
     */
    public static function blankBetween(File $phpcsFile, int $from, int $to): void
    {
        for ($i = ($from + 1); $i < $to; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }
    }

    /**
     * Replace everything between two tokens with the given content, in a changeset of its own.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $from The token before the gap.
     * @param int $to The token after the gap.
     * @param string $content The content for the gap, such as whitespace.
     */
    public static function replaceBetween(File $phpcsFile, int $from, int $to, string $content): void
    {
        $phpcsFile->fixer->beginChangeset();
        self::blankBetween($phpcsFile, $from, $to);
        $phpcsFile->fixer->addContent($from, $content);
        $phpcsFile->fixer->endChangeset();
    }
}
