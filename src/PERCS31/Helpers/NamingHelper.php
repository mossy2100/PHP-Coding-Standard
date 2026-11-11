<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Context;
use PHPCSUtils\Utils\UseStatements;

/**
 * Helper class for checking the case of names.
 */
class NamingHelper
{
    /**
     * Check whether a name is in PascalCase.
     *
     * Section 2.1 of PER Coding Style 3.1 says the first letter of every word is capitalized. If a word could be one
     * letter, any name in capitals would qualify, so a run of capitals isn't PascalCase, even if it's an acronym.
     *
     * @param string $name The name.
     * @return bool True if it starts with a capital, has only letters and digits, and has no run of capitals.
     */
    public static function isPascalCase(string $name): bool
    {
        return preg_match('/^[A-Z][A-Za-z0-9]*$/', $name) === 1 && !self::hasAcronym($name);
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
     * Find the names in a `const` statement: each token before an `=` at the statement's own level, not one inside a
     * value. A statement can declare several, as in `const A = 1, B = 2;`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $constPtr The position of `const`.
     * @return list<int> The positions of the names, or none if the statement is incomplete.
     */
    public static function findConstantNames(File $phpcsFile, int $constPtr): array
    {
        $tokens = $phpcsFile->getTokens();
        $names = [];
        for ($i = ($constPtr + 1); $i < $phpcsFile->numTokens; $i++) {
            if ($tokens[$i]['code'] === T_SEMICOLON) {
                return $names;
            }

            // Skip anything in brackets, such as an array, a call's arguments or the body of a closure (PHP 8.5), so
            // only the statement's own `=` and `;` count.
            $closer = TokenHelper::findCloser($tokens[$i]);
            if ($closer !== null && $closer > $i) {
                $i = $closer;
                continue;
            }

            if ($tokens[$i]['code'] !== T_EQUAL) {
                continue;
            }

            $namePtr = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($i - 1), $constPtr, true);
            if ($namePtr !== false) {
                $names[] = $namePtr;
            }
        }

        return [];
    }

    /**
     * Find the alias of a constant after an `as`, as in `use const Foo\BAR as BAZ;`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $asPtr The position of `as`.
     * @return int|false The alias's position, or false if this `as` doesn't give a constant an alias.
     */
    public static function findConstantAlias(File $phpcsFile, int $asPtr): int|false
    {
        $tokens = $phpcsFile->getTokens();

        if (Context::inForeachCondition($phpcsFile, $asPtr) === 'as') {
            return false;
        }

        $usePtr = $phpcsFile->findPrevious(T_USE, ($asPtr - 1));
        if ($usePtr === false || !UseStatements::isImportUse($phpcsFile, $usePtr)) {
            return false;
        }

        $namePtr = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($asPtr + 1), null, true);
        if ($namePtr === false || $tokens[$namePtr]['code'] !== T_STRING) {
            return false;
        }

        $imports = UseStatements::splitImportUseStatement($phpcsFile, $usePtr);

        return isset($imports['const'][$tokens[$namePtr]['content']]) ? $namePtr : false;
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
