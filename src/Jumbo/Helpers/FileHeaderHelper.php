<?php

declare(strict_types=1);

namespace Jumbo\Helpers;

use PHP_CodeSniffer\Files\File;

/**
 * Helper class for the file header.
 */
class FileHeaderHelper
{
    /**
     * Check whether a file starts with a declare statement between its own PHP tags on the first line.
     *
     * PER Coding Style 3.1, section 3, says a file containing markup outside PHP tags declares strict types this way,
     * e.g. `<?php declare(strict_types=1) ?>`, with or without a semicolon.
     *
     * @param File $phpcsFile The file being scanned.
     * @return bool True if the first line is an opening tag, a declare statement and a closing tag.
     */
    public static function hasInlineDeclare(File $phpcsFile): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($phpcsFile->numTokens === 0 || $tokens[0]['code'] !== T_OPEN_TAG) {
            return false;
        }

        $declare = $phpcsFile->findNext(T_WHITESPACE, 1, null, true);
        if ($declare === false || $tokens[$declare]['code'] !== T_DECLARE || $tokens[$declare]['line'] !== 1) {
            return false;
        }

        $closer = $tokens[$declare]['parenthesis_closer'] ?? null;
        if (!is_int($closer)) {
            return false;
        }

        $next = $phpcsFile->findNext(T_WHITESPACE, ($closer + 1), null, true);
        if ($next !== false && $tokens[$next]['code'] === T_SEMICOLON) {
            $next = $phpcsFile->findNext(T_WHITESPACE, ($next + 1), null, true);
        }

        return $next !== false && $tokens[$next]['code'] === T_CLOSE_TAG && $tokens[$next]['line'] === 1;
    }
}
