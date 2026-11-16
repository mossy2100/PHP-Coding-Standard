<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;
use PHPCSUtils\Utils\FileInfo;

/**
 * Helper class for the file header.
 */
class FileHeaderHelper
{
    /**
     * Find the PHP opening tag that starts a file: the one on its first line, or on its second line after a shebang
     * line.
     *
     * PHP skips a shebang line, so the line after one is the first line it reads.
     *
     * @param File $phpcsFile The file being scanned.
     * @return int|null The position of the opening tag, or null if the file doesn't start with one.
     */
    public static function findFirstLineTag(File $phpcsFile): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $tag = FileInfo::hasSheBang($phpcsFile) ? 1 : 0;

        return $phpcsFile->numTokens > $tag && $tokens[$tag]['code'] === T_OPEN_TAG ? $tag : null;
    }

    /**
     * Check whether a file starts with a declare statement between its own PHP tags on the first line, or on the second
     * line after a shebang line.
     *
     * PER Coding Style 3.1, section 3, says a file containing markup outside PHP tags declares strict types this way,
     * e.g. `<?php declare(strict_types=1) ?>`, with or without a semicolon.
     *
     * @param File $phpcsFile The file being scanned.
     * @return bool True if the line is an opening tag, a declare statement and a closing tag.
     */
    public static function hasInlineDeclare(File $phpcsFile): bool
    {
        $tokens = $phpcsFile->getTokens();
        $tag = self::findFirstLineTag($phpcsFile);
        if ($tag === null) {
            return false;
        }

        $line = $tokens[$tag]['line'];
        $declare = $phpcsFile->findNext(T_WHITESPACE, ($tag + 1), null, true);
        if ($declare === false || $tokens[$declare]['code'] !== T_DECLARE || $tokens[$declare]['line'] !== $line) {
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

        return $next !== false && $tokens[$next]['code'] === T_CLOSE_TAG && $tokens[$next]['line'] === $line;
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

            if ($i === 0 && FileInfo::hasSheBang($phpcsFile)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Check whether a PHP opening tag shares its line with anything else, such as a statement or a comment.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $tagPtr The position of the opening tag.
     * @return bool True if it does.
     */
    public static function tagSharesLine(File $phpcsFile, int $tagPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $next = $phpcsFile->findNext(T_WHITESPACE, ($tagPtr + 1), null, true);

        return $next !== false && $tokens[$next]['line'] === $tokens[$tagPtr]['line'];
    }

    /**
     * Find the file's first declare statement, if it sets strict_types.
     *
     * @param File $phpcsFile The file being scanned.
     * @param bool $onOnly True to only find a statement that sets strict_types to 1, and not one that sets it to 0.
     * @return int|null The position of `declare`, or null if the first declare statement doesn't set strict_types.
     */
    public static function findStrictTypesDeclare(File $phpcsFile, bool $onOnly = false): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $declare = $phpcsFile->findNext(T_DECLARE, 0);
        $closer = $declare === false ? null : ($tokens[$declare]['parenthesis_closer'] ?? null);
        if ($declare === false || !is_int($closer)) {
            return null;
        }

        $content = $phpcsFile->getTokensAsString($declare, ($closer - $declare + 1));

        $pattern = $onOnly ? '/strict_types\s*=\s*1\b/' : '/strict_types\s*=\s*[01]\b/';

        return preg_match($pattern, $content) === 1 ? $declare : null;
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
        if (FileInfo::hasSheBang($phpcsFile)) {
            $newline = strpos($content, "\n");
            $content = $newline === false ? '' : substr($content, ($newline + 1));
        }

        return trim($content) !== '';
    }
}
