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
     * Section 2.1 of PER Coding Style 3.1 says the first letter of every word is capitalized. PascalCase mixes upper
     * and lower case, so a name with no lower-case letter, such as `FOO`, `A` or `B2B`, isn't in it. An acronym in
     * capitals in a PascalCase name, as in `HTTPClient`, is a separate matter, which hasAcronym() checks.
     *
     * @param string $name The name.
     * @return bool True if it starts with a capital, has only letters and digits, and has a lower-case letter.
     */
    public static function isPascalCase(string $name): bool
    {
        return preg_match('/^[A-Z][A-Za-z0-9]*$/', $name) === 1 && preg_match('/[a-z]/', $name) === 1;
    }

    /**
     * Check whether a name has an acronym in capitals.
     *
     * An acronym is a word of two or more capitals, as splitWords() finds them, so `AICodingStandard` and `IHeartAI`
     * each have one. A single capital is an initial, not an acronym, as in `ClassA`, `EDayOfWeek` or `B2B`.
     *
     * @param string $name The name, which can include underscores or a variable's `$`.
     * @return bool True if it has one.
     */
    public static function hasAcronym(string $name): bool
    {
        foreach (explode('_', ltrim($name, '$')) as $part) {
            foreach (self::splitWords($part) as $word) {
                $letters = rtrim($word, '0123456789');
                if (strlen($letters) >= 2 && ctype_upper($letters)) {
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
}
