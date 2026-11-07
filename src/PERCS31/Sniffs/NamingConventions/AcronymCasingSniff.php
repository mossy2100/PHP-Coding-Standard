<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Warns about an acronym in capitals in a name.
 *
 * PER Coding Style 3.1, section 2.7, RECOMMENDS treating abbreviations, acronyms and initialisms like regular words, so
 * `HttpClient`, not `HTTPClient`. It doesn't limit that to some names, so this sniff checks a namespace, class,
 * interface, trait, enum, enum case, enum constant, function, method, property, parameter or variable. It reports two
 * or more capitals in a row. Single letters could be words of their own, so `parseURL` could be read as `parse`, `U`,
 * `R` and `L`, so this sniff doesn't allow a one-letter word before a capital. So `getID`, `getIDs`, `AClass` and
 * `whenXIs` are all reported. A single capital that isn't followed by another, as in `getX` or `X509Certificate`,
 * isn't.
 *
 * It doesn't check:
 * - PHP's reserved variables, such as `$_SERVER`, which aren't ours to name.
 * - A constant outside an enum, or one in UPPER_CASE in an enum, as UPPER_CASE is required, or has its own warning.
 * - A property where it's used, such as `$this->userID` or `self::$userID`, as it may belong to a class we don't
 *   control. It's checked where it's declared.
 *
 * Set excludedNames to allow whole names, and excludedAcronyms to allow acronyms in any name, as comma-separated lists.
 * Each part of a namespace is checked as a name of its own. An acronym must match a whole run of capitals, not counting
 * a last capital that starts a word. So excluding `HTTP` allows `HTTPClient` and `HTTPs`, but not `HTTPSClient`, and
 * excluding `T` allows a prefix, as in `TType`. Listing several lets them combine, as in `XMLHTTPRequest`.
 *
 * The warning isn't fixable, as references to the name elsewhere wouldn't change.
 */
class AcronymCasingSniff implements Sniff
{
    public const string CODE_ACRONYM_NOT_LIKE_WORD = 'AcronymNotLikeWord';

    /**
     * Names to allow, as a comma-separated list, e.g. `PERCS31,legacyID`. Matching is case-sensitive, and a variable's
     * `$` is optional. It's null if a `phpcs:set` comment clears it.
     */
    public ?string $excludedNames = '';

    /**
     * Acronyms to allow in any name, as a comma-separated list, e.g. `HTTP,URL`. Matching is case-sensitive. It's null
     * if a `phpcs:set` comment clears it.
     */
    public ?string $excludedAcronyms = '';

    /**
     * PHP's reserved variables, which may be in capitals.
     *
     * @var list<string>
     */
    private const array RESERVED_VARIABLES = [
        '$GLOBALS',
        '$HTTP_RAW_POST_DATA',
        '$_COOKIE',
        '$_ENV',
        '$_FILES',
        '$_GET',
        '$_POST',
        '$_REQUEST',
        '$_SERVER',
        '$_SESSION',
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_NAMESPACE, T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_ENUM_CASE, T_CONST, T_FUNCTION, T_VARIABLE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $namePtr = $this->findName($phpcsFile, $stackPtr);
        if ($namePtr === false) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $name = $tokens[$namePtr]['content'];
        assert(is_string($name));
        $parts = $tokens[$stackPtr]['code'] === T_NAMESPACE ? explode('\\', $name) : [$name];
        $hasAcronym = false;
        foreach ($parts as $part) {
            $hasAcronym = $hasAcronym || $this->hasAcronym($part);
        }

        if (!$hasAcronym) {
            return;
        }

        $phpcsFile->addWarning(
            'An acronym in a name should be written like a word, e.g. Http, not HTTP; found %s',
            $namePtr,
            self::CODE_ACRONYM_NOT_LIKE_WORD,
            [$name],
        );
    }

    /**
     * Check whether a name has an acronym in capitals that isn't excluded.
     *
     * @param string $name The name.
     * @return bool True if it has one.
     */
    private function hasAcronym(string $name): bool
    {
        foreach ($this->splitList($this->excludedNames) as $excluded) {
            if (ltrim($excluded, '$') === ltrim($name, '$')) {
                return false;
            }
        }

        preg_match_all('/[A-Z]{2,}/', $name, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as [$run, $offset]) {
            // The last capital starts a word if a lower-case letter follows it, as in `HTTPClient`.
            $next = $name[($offset + strlen($run))] ?? '';
            $acronym = ctype_lower($next) ? substr($run, 0, -1) : $run;
            if (!$this->isExcludedAcronym($run) && !$this->isExcludedAcronym($acronym)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether a run of capitals is made up entirely of excluded acronyms.
     *
     * @param string $run The run of capitals.
     * @return bool True if it is.
     */
    private function isExcludedAcronym(string $run): bool
    {
        $acronyms = $this->splitList($this->excludedAcronyms);
        if ($acronyms === []) {
            return false;
        }

        $pattern = implode('|', array_map(static fn(string $acronym): string => preg_quote($acronym, '/'), $acronyms));

        return preg_match('/^(?:' . $pattern . ')+$/', $run) === 1;
    }

    /**
     * Split a comma-separated property into its values.
     *
     * @param ?string $list The list.
     * @return list<string> The values, without surrounding space.
     */
    private function splitList(?string $list): array
    {
        $values = array_map(trim(...), explode(',', $list ?? ''));

        return array_values(array_filter($values, static fn(string $value): bool => $value !== ''));
    }

    /**
     * Find the token holding the name to check.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the keyword or variable.
     * @return int|false The name's position, or false if there's nothing to check.
     */
    private function findName(File $phpcsFile, int $stackPtr): int|false
    {
        $tokens = $phpcsFile->getTokens();
        $code = $tokens[$stackPtr]['code'];
        if ($code === T_VARIABLE) {
            $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($stackPtr - 1), null, true);
            $isStaticAccess = $previous !== false && $tokens[$previous]['code'] === T_DOUBLE_COLON;

            return $isStaticAccess || in_array($tokens[$stackPtr]['content'], self::RESERVED_VARIABLES, true)
                ? false
                : $stackPtr;
        }

        if ($code === T_CONST) {
            return $this->findEnumConstantName($phpcsFile, $stackPtr);
        }

        $namePtr = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);

        // A function that returns by reference has a `&` before its name.
        if ($namePtr !== false && $tokens[$namePtr]['code'] === T_BITWISE_AND) {
            $namePtr = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($namePtr + 1), null, true);
        }

        if ($namePtr === false) {
            return false;
        }

        $nameCode = $tokens[$namePtr]['code'];
        $isName = $nameCode === T_STRING || ($code === T_NAMESPACE && $nameCode === T_NAME_QUALIFIED);

        return $isName ? $namePtr : false;
    }

    /**
     * Find the name of a constant in an enum, unless it's in UPPER_CASE, which has its own warning.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of `const`.
     * @return int|false The name's position, or false if there's nothing to check.
     */
    private function findEnumConstantName(File $phpcsFile, int $stackPtr): int|false
    {
        $tokens = $phpcsFile->getTokens();
        $conditions = $tokens[$stackPtr]['conditions'];
        if ($conditions === [] || end($conditions) !== T_ENUM) {
            return false;
        }

        $equals = $phpcsFile->findNext(T_EQUAL, ($stackPtr + 1));
        if ($equals === false) {
            return false;
        }

        // A typed constant has its type before its name, so look back from the `=`.
        $namePtr = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($equals - 1), $stackPtr, true);
        if ($namePtr === false) {
            return false;
        }

        $name = $tokens[$namePtr]['content'];

        return strtoupper($name) === $name ? false : $namePtr;
    }
}
