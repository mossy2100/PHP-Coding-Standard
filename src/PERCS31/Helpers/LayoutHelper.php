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
     * Count the blank lines between two tokens. A line with a comment on it isn't blank.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $from The position of the first token.
     * @param int $to The position of the second token.
     * @return int The number of lines between them with only whitespace on.
     */
    public static function countBlankLinesBetween(File $phpcsFile, int $from, int $to): int
    {
        $tokens = $phpcsFile->getTokens();
        $usedLines = [];
        for ($i = ($from + 1); $i < $to; $i++) {
            if ($tokens[$i]['code'] !== T_WHITESPACE && $tokens[$i]['line'] > $tokens[$from]['line']) {
                $usedLines[$tokens[$i]['line']] = true;
            }
        }

        // Tokens on the same line have no lines between them.
        $fromLine = $tokens[$from]['line'];
        $toLine = $tokens[$to]['line'];
        assert(is_int($fromLine) && is_int($toLine));

        return max(0, ($toLine - $fromLine - 1 - count($usedLines)));
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

    /**
     * Check whether a token is a blank line: whitespace that starts a line and ends it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the token.
     * @return bool True if the token is a line with nothing on it.
     */
    public static function isBlankLine(File $phpcsFile, int $ptr): bool
    {
        $tokens = $phpcsFile->getTokens();

        return $tokens[$ptr]['code'] === T_WHITESPACE
            && $tokens[$ptr]['column'] === 1
            && isset($tokens[($ptr + 1)])
            && $tokens[$ptr]['line'] !== $tokens[($ptr + 1)]['line'];
    }

    /**
     * Find the blank lines at the start and the end of a list: straight after its opening bracket, and straight before
     * its closing one.
     *
     * PER Coding Style 3.1, section 2.3, allows blank lines "except where explicitly forbidden", so one between two
     * items of a list is fine, and isn't looked for. But the first item goes on the line after the opening bracket,
     * and the closing bracket on the line after the last, so a blank line at either end is reported.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the opening bracket.
     * @param int $closer The position of the closing bracket.
     * @return list<int> The position of each blank line's whitespace token, in order.
     */
    public static function findBlankLinesAtStartAndEnd(File $phpcsFile, int $opener, int $closer): array
    {
        $tokens = $phpcsFile->getTokens();
        $blankLines = [];
        for ($i = ($opener + 1); $i < $closer && $tokens[$i]['code'] === T_WHITESPACE; $i++) {
            if (self::isBlankLine($phpcsFile, $i)) {
                $blankLines[$i] = $i;
            }
        }

        for ($i = ($closer - 1); $i > $opener && $tokens[$i]['code'] === T_WHITESPACE; $i--) {
            if (self::isBlankLine($phpcsFile, $i)) {
                $blankLines[$i] = $i;
            }
        }

        sort($blankLines);

        return $blankLines;
    }
}
