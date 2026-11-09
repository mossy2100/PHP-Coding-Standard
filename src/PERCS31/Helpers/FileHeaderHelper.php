<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

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

    /**
     * Check whether a file has markup: anything other than whitespace outside PHP tags, apart from a shebang line.
     *
     * @param File $phpcsFile The file being scanned.
     * @return bool True if it has.
     */
    public static function hasMarkup(File $phpcsFile): bool
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = 0; $i < $phpcsFile->numTokens; $i++) {
            if ($tokens[$i]['code'] !== T_INLINE_HTML || trim($tokens[$i]['content']) === '') {
                continue;
            }

            if ($i === 0 && str_starts_with($tokens[$i]['content'], '#!')) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Find the file's first declare statement, if it sets strict_types to 1.
     *
     * @param File $phpcsFile The file being scanned.
     * @return int|null The position of `declare`, or null if the first declare statement doesn't set strict_types to 1.
     */
    public static function findStrictTypesDeclare(File $phpcsFile): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $declare = $phpcsFile->findNext(T_DECLARE, 0);
        $closer = $declare === false ? null : ($tokens[$declare]['parenthesis_closer'] ?? null);
        if ($declare === false || !is_int($closer)) {
            return null;
        }

        $content = $phpcsFile->getTokensAsString($declare, ($closer - $declare + 1));

        return preg_match('/strict_types\s*=\s*1\b/', $content) === 1 ? $declare : null;
    }

    /**
     * Check whether a file starts with markup before its first PHP opening tag, apart from a shebang line.
     *
     * @param File $phpcsFile The file being scanned.
     * @return bool True if it does.
     */
    public static function hasMarkupBeforeFirstTag(File $phpcsFile): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($phpcsFile->numTokens === 0 || $tokens[0]['code'] !== T_INLINE_HTML) {
            return false;
        }

        $content = $tokens[0]['content'];
        if (str_starts_with($content, '#!')) {
            $newline = strpos($content, "\n");
            $content = $newline === false ? '' : substr($content, ($newline + 1));
        }

        return trim($content) !== '';
    }
}
