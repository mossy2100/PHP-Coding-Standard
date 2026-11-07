<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\NamingConventions;

use PERCS31\Helpers\NamingHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\TextStrings;
use PHPCSUtils\Utils\UseStatements;

/**
 * Warns about an acronym in capitals in a name.
 *
 * PER Coding Style 3.1, section 2.7, RECOMMENDS treating abbreviations, acronyms and initialisms like regular words, so
 * `getHttpClient`, not `getHTTPClient`. This sniff checks a namespace, function, method, property, parameter, variable,
 * global constant, import alias or trait alias. NamingHelper splits each name into words. A word of two or more
 * capitals is reported, and so is a single capital directly before another word, so `parseURL`, `getID`, `getIDs`,
 * `getURLs` and `whenXIs` are all reported. A single capital that ends the name, or that's followed by a digit, as in
 * `getX` or `getX2Value`, isn't.
 *
 * It doesn't check:
 * - A class, interface, trait, enum or enum case. PascalCase is required of these, and a run of capitals isn't
 *   PascalCase, so ValidClassName and ValidEnumCaseName report it as an error, with no exceptions.
 * - An enum constant, as PascalCase or UPPER_CASE is required, so UpperCaseConstantName reports it.
 * - A class constant, as UPPER_CASE is required.
 * - A global constant or an alias of one with no lower-case letters, as UPPER_CASE is allowed for it.
 * - PHP's reserved variables, such as `$_SERVER`, which aren't ours to name.
 * - A property where it's used, such as `$this->userID` or `self::$userID`, as it may belong to a class we don't
 *   control. It's checked where it's declared.
 *
 * Set excludedNames to allow whole names, and excludedAcronyms to allow acronyms in any name it checks, as
 * comma-separated lists. Each part of a namespace is checked as a name of its own. An acronym must match a whole
 * upper-case word. So excluding `HTTP` allows `getHTTPClient`, but not `getHTTPSClient` or `getHTTPs`, which ends in
 * `HTT` and `Ps`. Excluding `T` allows a prefix, as in `getTType`. Listing several lets them combine, as in
 * `getXMLHTTPRequest`.
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
        return [T_NAMESPACE, T_FUNCTION, T_VARIABLE, T_AS, T_CONST, T_STRING, T_NAME_FULLY_QUALIFIED];
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
        if ($tokens[$namePtr]['code'] === T_CONSTANT_ENCAPSED_STRING) {
            // A name given to `define()` can have a namespace, which isn't part of the constant's name.
            $parts = explode('\\', TextStrings::stripQuotes($name));
            $name = end($parts);
        }

        if ($this->isConstant($phpcsFile, $stackPtr, $namePtr) && strtoupper($name) === $name) {
            return;
        }

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

        if ($code === T_AS) {
            return $this->findAlias($phpcsFile, $stackPtr);
        }

        if ($code === T_CONST) {
            return $this->findGlobalConstantName($phpcsFile, $stackPtr);
        }

        if ($code === T_STRING || $code === T_NAME_FULLY_QUALIFIED) {
            return $this->findDefinedConstantName($phpcsFile, $stackPtr);
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
     * Find the alias after an `as` in an import or a trait's `use`. A trait's `as` might only change a method's
     * visibility, and give no alias.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of `as`.
     * @return int|false The alias's position, or false if there's nothing to check.
     */
    private function findAlias(File $phpcsFile, int $stackPtr): int|false
    {
        $tokens = $phpcsFile->getTokens();

        // A `foreach` is the only place an `as` is in parentheses.
        if (isset($tokens[$stackPtr]['nested_parenthesis'])) {
            return false;
        }

        $skip = Tokens::EMPTY_TOKENS + Tokens::METHOD_MODIFIERS;
        $namePtr = $phpcsFile->findNext($skip, ($stackPtr + 1), null, true);
        if ($namePtr === false || $tokens[$namePtr]['code'] !== T_STRING) {
            return false;
        }

        return $namePtr;
    }

    /**
     * Find the name in a global `const` statement.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of `const`.
     * @return int|false The name's position, or false if there's nothing to check.
     */
    private function findGlobalConstantName(File $phpcsFile, int $stackPtr): int|false
    {
        $tokens = $phpcsFile->getTokens();
        $conditions = $tokens[$stackPtr]['conditions'];
        if ($conditions !== [] && isset(Tokens::OO_SCOPE_TOKENS[end($conditions)])) {
            return false;
        }

        $equals = $phpcsFile->findNext(T_EQUAL, ($stackPtr + 1));

        if ($equals === false) {
            return false;
        }

        return $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($equals - 1), $stackPtr, true);
    }

    /**
     * Find the name given to a call to `define()`, as Generic.NamingConventions.UpperCaseConstantName does.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of a name that could be `define`.
     * @return int|false The name's position, which holds a quoted string, or false if there's nothing to check.
     */
    private function findDefinedConstantName(File $phpcsFile, int $stackPtr): int|false
    {
        $tokens = $phpcsFile->getTokens();
        if (strtolower(ltrim($tokens[$stackPtr]['content'], '\\')) !== 'define') {
            return false;
        }

        $notCalls = [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW, T_FUNCTION];
        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($stackPtr - 1), null, true);
        if ($previous !== false && in_array($tokens[$previous]['code'], $notCalls, true)) {
            return false;
        }

        $open = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if ($open === false || $tokens[$open]['code'] !== T_OPEN_PARENTHESIS) {
            return false;
        }

        $namePtr = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($open + 1), null, true);

        return $namePtr !== false && $tokens[$namePtr]['code'] === T_CONSTANT_ENCAPSED_STRING ? $namePtr : false;
    }

    /**
     * Check whether a name being checked is a global constant's, or the alias of one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token the sniff was called for.
     * @param int $namePtr The position of the name.
     * @return bool True if it is.
     */
    private function isConstant(File $phpcsFile, int $stackPtr, int $namePtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $code = $tokens[$stackPtr]['code'];
        if ($code !== T_AS) {
            return $code === T_CONST || $code === T_STRING || $code === T_NAME_FULLY_QUALIFIED;
        }

        $usePtr = $phpcsFile->findPrevious(T_USE, ($stackPtr - 1));
        if ($usePtr === false || !UseStatements::isImportUse($phpcsFile, $usePtr)) {
            return false;
        }

        $imports = UseStatements::splitImportUseStatement($phpcsFile, $usePtr);

        return isset($imports['const'][$tokens[$namePtr]['content']]);
    }
}
