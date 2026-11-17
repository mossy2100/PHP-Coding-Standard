<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Helper class for `match` expressions.
 */
class MatchHelper
{
    /**
     * Find the arms of a `match` expression.
     *
     * An arm starts with its first condition, or `default`, and its value starts after its `=>`. It ends at the comma
     * after its value, except a last arm with no trailing comma. A comma or `=>` inside brackets belongs to something
     * else, such as a call in a condition or a nested `match`, so it's skipped.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the match body's opening brace.
     * @param int $closer The position of the match body's closing brace.
     * @return list<array{start: int, arrow: int, end: int|null}> Each arm's first token, `=>`, and the comma that ends
     *     it, if there is one.
     */
    public static function findArms(File $phpcsFile, int $opener, int $closer): array
    {
        $tokens = $phpcsFile->getTokens();
        $arms = [];
        $start = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($opener + 1), $closer, true);
        $arrow = null;
        for ($i = ($opener + 1); $i < $closer; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_MATCH_ARROW && $arrow === null) {
                $arrow = $i;
            } elseif ($code === T_COMMA && $arrow !== null) {
                if ($start !== false) {
                    $arms[] = ['start' => $start, 'arrow' => $arrow, 'end' => $i];
                }

                $start = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), $closer, true);
                $arrow = null;
            }

            $jumpTo = TokenHelper::findCloser($tokens[$i]);
            if (is_int($jumpTo) && $jumpTo > $i && $jumpTo < $closer) {
                $i = $jumpTo;
            }
        }

        if ($start !== false && $arrow !== null) {
            $arms[] = ['start' => $start, 'arrow' => $arrow, 'end' => null];
        }

        return $arms;
    }

    /**
     * Find the first token of each condition of a `match` arm. A comma inside brackets belongs to something else, such
     * as a call in a condition, so it's skipped. A trailing comma before the `=>` doesn't start another condition.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array{start: int, arrow: int, end: int|null} $arm The arm, as findArms() returns it.
     * @return list<int> The position of each condition's first token, in order.
     */
    public static function findConditions(File $phpcsFile, array $arm): array
    {
        $tokens = $phpcsFile->getTokens();
        $conditions = [$arm['start']];
        for ($i = $arm['start']; $i < $arm['arrow']; $i++) {
            if ($tokens[$i]['code'] === T_COMMA) {
                $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), $arm['arrow'], true);
                if ($next !== false) {
                    $conditions[] = $next;
                }
            }

            $jumpTo = TokenHelper::findCloser($tokens[$i]);
            if (is_int($jumpTo) && $jumpTo > $i && $jumpTo < $arm['arrow']) {
                $i = $jumpTo;
            }
        }

        return $conditions;
    }

    /**
     * Check whether a `match` arm's list of conditions is split over lines: it doesn't start and end on the same line.
     * That includes a list with a condition that spans lines, such as an array. A line break before the `=>` isn't in
     * the list, so it doesn't count.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array{start: int, arrow: int, end: int|null} $arm The arm, as findArms() returns it.
     * @return bool True if the list is split.
     */
    public static function hasSplitConditions(File $phpcsFile, array $arm): bool
    {
        $tokens = $phpcsFile->getTokens();
        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($arm['arrow'] - 1), $arm['start'], true);

        return $last !== false && $tokens[$last]['line'] !== $tokens[$arm['start']]['line'];
    }
}
