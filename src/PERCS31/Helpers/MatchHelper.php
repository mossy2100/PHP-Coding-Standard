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
}
