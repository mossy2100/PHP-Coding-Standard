<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\NamingConventions;

use PERCS31\Helpers\NamingHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Context;
use PHPCSUtils\Utils\PassedParameters;
use PHPCSUtils\Utils\Scopes;
use PHPCSUtils\Utils\TextStrings;
use PHPCSUtils\Utils\Variables;

/**
 * Warns about an acronym in capitals in a name.
 *
 * PER Coding Style 3.1, section 2.7, RECOMMENDS treating abbreviations, acronyms and initialisms like regular words, so
 * `getHttpClient`, not `getHTTPClient`. This sniff checks a class, interface, trait, enum, enum case, enum constant,
 * namespace, function, method, property, parameter, variable, global constant, import alias or trait alias.
 *
 * NamingHelper splits each name into words. An acronym is a word of two or more capitals, so `HTTPClient`, `parseURL`,
 * `getID`, `getURLs` and `IHeartAI` are all reported. A single capital is an initial, not an acronym, so `ClassA`,
 * `EDayOfWeek`, `getX`, `getIDs` and `whenXIs` aren't.
 *
 * It doesn't check:
 * - A class, interface, trait, enum, enum case or constant with no lower-case letter, such as `FOO` or `HTTP_OK`. It's
 *   in UPPER_CASE, which is allowed for a global or enum constant, and which ValidClassName and ValidEnumCaseName
 *   report for the others.
 * - A class constant outside an enum, as UPPER_CASE is required.
 * - PHP's reserved variables, such as `$_SERVER`, which aren't ours to name.
 * - A property where it's used, such as `$this->userID` or `self::$userID`, as it may belong to a class we don't
 *   control. It's checked where it's declared.
 * - A class where it's used, such as `new HTTPClient()`, for the same reason.
 *
 * Set excludedNames to allow whole names, and excludedAcronyms to allow acronyms in any name it checks, as
 * comma-separated lists. Each part of a namespace is checked as a name of its own. An acronym must match a whole
 * upper-case word. So excluding `HTTP` allows `getHTTPClient`, but not `getHTTPSClient` or `getHTTPs`, which ends in
 * `HTT` and `Ps`. Listing several lets them combine, as in `getXMLHTTPRequest`.
 *
 * The warning isn't fixable, as references to the name elsewhere wouldn't change.
 */
class AcronymCasingSniff implements Sniff
{
    public const string CODE_ACRONYM_NOT_LIKE_WORD = 'AcronymNotLikeWord';

    /**
     * The tokens that declare a class, interface, trait, enum or enum case.
     *
     * @var list<int|string>
     */
    private const array CLASS_LIKE_TOKENS = [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_ENUM_CASE];

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
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [
            ...self::CLASS_LIKE_TOKENS,
            T_NAMESPACE,
            T_FUNCTION,
            T_VARIABLE,
            T_AS,
            T_CONST,
            T_STRING,
            T_NAME_FULLY_QUALIFIED,
        ];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        foreach ($this->findNames($phpcsFile, $stackPtr) as $namePtr) {
            $this->checkName($phpcsFile, $stackPtr, $namePtr);
        }
    }

    /**
     * Check a name for an acronym in capitals.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token the sniff was called for.
     * @param int $namePtr The position of the name.
     */
    private function checkName(File $phpcsFile, int $stackPtr, int $namePtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $name = $tokens[$namePtr]['content'];
        assert(is_string($name));
        if ($tokens[$namePtr]['code'] === T_CONSTANT_ENCAPSED_STRING) {
            // A name given to `define()` can have a namespace, which isn't part of the constant's name.
            $parts = explode('\\', TextStrings::stripQuotes($name));
            $name = end($parts);
        }

        // A name with no lower-case letter is in UPPER_CASE, so it has no acronym to write like a word. UPPER_CASE is
        // allowed for a constant, and ValidClassName and ValidEnumCaseName report it for a class or an enum case.
        $isClassLike = in_array($tokens[$stackPtr]['code'], self::CLASS_LIKE_TOKENS, true);
        if (($isClassLike || $this->isConstant($phpcsFile, $stackPtr)) && strtoupper($name) === $name) {
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
     * Find the tokens holding the names to check. A global `const` statement can declare several.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the keyword or variable.
     * @return list<int> The names' positions.
     */
    private function findNames(File $phpcsFile, int $stackPtr): array
    {
        if ($phpcsFile->getTokens()[$stackPtr]['code'] === T_CONST) {
            // A class constant must be in UPPER_CASE, which PERCS31.NamingConventions.UpperCaseConstantName checks.
            // Only an enum's can be in PascalCase.
            $isClassConstant = Scopes::isOOConstant($phpcsFile, $stackPtr);
            if ($isClassConstant && Scopes::validDirectScope($phpcsFile, $stackPtr, T_ENUM) === false) {
                return [];
            }

            return NamingHelper::findConstantNames($phpcsFile, $stackPtr);
        }

        $namePtr = $this->findName($phpcsFile, $stackPtr);

        return $namePtr === false ? [] : [$namePtr];
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

            return $isStaticAccess || Variables::isPHPReservedVarName($tokens[$stackPtr]['content'])
                ? false
                : $stackPtr;
        }

        if ($code === T_AS) {
            return $this->findAlias($phpcsFile, $stackPtr);
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

        if (Context::inForeachCondition($phpcsFile, $stackPtr) === 'as') {
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

        $name = PassedParameters::getParameter($phpcsFile, $stackPtr, 1, 'constant_name');
        if ($name === false || !is_int($name['start']) || !is_int($name['end'])) {
            return false;
        }

        $namePtr = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, $name['start'], ($name['end'] + 1), true);

        return $namePtr !== false && $tokens[$namePtr]['code'] === T_CONSTANT_ENCAPSED_STRING ? $namePtr : false;
    }

    /**
     * Check whether a name being checked is a global constant's, or the alias of one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token the sniff was called for.
     * @return bool True if it is.
     */
    private function isConstant(File $phpcsFile, int $stackPtr): bool
    {
        $code = $phpcsFile->getTokens()[$stackPtr]['code'];
        if ($code === T_AS) {
            return NamingHelper::findConstantAlias($phpcsFile, $stackPtr) !== false;
        }

        return $code === T_CONST || $code === T_STRING || $code === T_NAME_FULLY_QUALIFIED;
    }
}
