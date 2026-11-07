<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\NamingConventions;

use PERCS31\Helpers\NamingHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Warns about an acronym in capitals in a name.
 *
 * PER Coding Style 3.1, section 2.7, RECOMMENDS treating abbreviations, acronyms and initialisms like regular words, so
 * `HttpClient`, not `HTTPClient`. It doesn't limit that to some names, so this sniff checks a namespace, class,
 * interface, trait, enum, function, method, property, parameter or variable. NamingHelper splits each name into words.
 * A word of two or more capitals is reported, and so is a single capital directly before another word, so `parseURL`,
 * `getID`, `getIDs`, `getURLs`, `AClass` and `whenXIs` are all reported. A single capital that ends the name, or that's
 * followed by a digit, as in `getX` or `X509Certificate`, isn't.
 *
 * It doesn't check:
 * - PHP's reserved variables, such as `$_SERVER`, which aren't ours to name.
 * - An enum case or enum constant. PascalCase is required of a case, and recommended for a constant, so
 *   ValidEnumCaseName and UpperCaseConstantName report them, using this sniff's lists.
 * - A constant outside an enum, as UPPER_CASE is required.
 * - A property where it's used, such as `$this->userID` or `self::$userID`, as it may belong to a class we don't
 *   control. It's checked where it's declared.
 *
 * Set excludedNames to allow whole names, and excludedAcronyms to allow acronyms in any name, as comma-separated lists.
 * Each part of a namespace is checked as a name of its own. An acronym must match a whole upper-case word. So excluding
 * `HTTP` allows `HTTPClient`, but not `HTTPSClient` or `HTTPs`, which is `HTT` and `Ps`. Excluding `T` allows a prefix,
 * as in `TType`. Listing several lets them combine, as in `XMLHTTPRequest`.
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
        return [T_NAMESPACE, T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION, T_VARIABLE];
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
    public function hasAcronym(string $name): bool
    {
        foreach ($this->splitList($this->excludedNames) as $excluded) {
            if (ltrim($excluded, '$') === ltrim($name, '$')) {
                return false;
            }
        }

        return NamingHelper::hasAcronym($name, $this->splitList($this->excludedAcronyms));
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
}
