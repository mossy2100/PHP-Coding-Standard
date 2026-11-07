<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PERCS31\Sniffs\NamingConventions\AcronymCasingSniff;
use PHP_CodeSniffer\Files\File;

/**
 * Helper class for checking the case of names.
 */
class NamingHelper
{
    /**
     * Check whether a name is in PascalCase, using the acronyms and names approved in AcronymCasing, if it's loaded.
     *
     * @param File $phpcsFile The file being scanned.
     * @param string $name The name.
     * @return bool True if it's in PascalCase.
     */
    public static function isPascalCaseInFile(File $phpcsFile, string $name): bool
    {
        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $name) !== 1) {
            return false;
        }

        $sniff = $phpcsFile->ruleset->sniffs[AcronymCasingSniff::class] ?? null;

        return $sniff instanceof AcronymCasingSniff ? !$sniff->hasAcronym($name) : !self::hasAcronym($name);
    }

    /**
     * Check whether a name has an upper-case word that isn't made up of approved acronyms.
     *
     * Words of two or more capitals need approval, as does a single capital directly before another word, as in
     * `AClass`. A single capital that ends the name, or that's followed by a digit, doesn't, as in `getX` or `B2B`.
     *
     * @param string $name The name, which can include underscores or a variable's `$`.
     * @param list<string> $approved The approved acronyms. Matching is case-sensitive.
     * @return bool True if it has one.
     */
    public static function hasAcronym(string $name, array $approved = []): bool
    {
        foreach (explode('_', ltrim($name, '$')) as $part) {
            $words = self::splitWords($part);
            foreach ($words as $index => $word) {
                $letters = rtrim($word, '0123456789');
                if (!ctype_upper($letters)) {
                    continue;
                }

                $isLoneCapital = strlen($letters) === 1 && ($word !== $letters || $index === array_key_last($words));
                if (!$isLoneCapital && !self::isMadeOfAcronyms($letters, $approved)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Split a name with no underscores into words, working back from its last character.
     *
     * If the last letter is lower-case, the word goes back to the first capital, which is included. If it's upper-case,
     * the word goes back to the first character that isn't a capital, which isn't included. A digit stays with the word
     * on its left, and separates capitals. So `HTTPClient` is `HTTP` and `Client`, `getIDs` is `get`, `I` and `Ds`, and
     * `B2B` is `B2` and `B`.
     *
     * @param string $name The name.
     * @return list<string> The words, in order.
     */
    public static function splitWords(string $name): array
    {
        $words = [];
        $end = strlen($name);
        while ($end > 0) {
            $start = $end;
            while ($start > 0 && ctype_digit($name[($start - 1)])) {
                $start--;
            }

            if ($start > 0 && ctype_upper($name[($start - 1)])) {
                while ($start > 0 && ctype_upper($name[($start - 1)])) {
                    $start--;
                }
            } else {
                while ($start > 0 && !ctype_upper($name[($start - 1)])) {
                    $start--;
                }

                $start = max(0, ($start - 1));
            }

            array_unshift($words, substr($name, $start, ($end - $start)));
            $end = $start;
        }

        return $words;
    }

    /**
     * Check whether a run of capitals is made up entirely of approved acronyms.
     *
     * @param string $run The run of capitals.
     * @param list<string> $approved The approved acronyms.
     * @return bool True if it is.
     */
    private static function isMadeOfAcronyms(string $run, array $approved): bool
    {
        if ($approved === []) {
            return false;
        }

        $pattern = implode('|', array_map(static fn(string $acronym): string => preg_quote($acronym, '/'), $approved));

        return preg_match('/^(?:' . $pattern . ')+$/', $run) === 1;
    }
}
