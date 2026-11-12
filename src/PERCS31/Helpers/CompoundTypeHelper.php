<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\Constants;
use PHPCSUtils\Utils\FunctionDeclarations;
use PHPCSUtils\Utils\Parentheses;
use PHPCSUtils\Utils\Variables;
use RuntimeException;

/**
 * Helper class for finding the parts of a compound type, such as `int|string` or `(A&B)|null`.
 *
 * It also covers a `set` hook's parameter type, e.g. `set(int|string $value)`. PHP_CodeSniffer doesn't know about
 * hooks, so it reads that type as an expression, and gives its symbols and parentheses other tokens.
 */
class CompoundTypeHelper
{
    /**
     * The tokens that can make up a type, besides whitespace and comments.
     *
     * @var list<int|string>
     */
    private const array TYPE_TOKENS = [
        T_STRING,
        T_NAME_QUALIFIED,
        T_NAME_FULLY_QUALIFIED,
        T_NAME_RELATIVE,
        T_NULL,
        T_FALSE,
        T_TRUE,
        T_SELF,
        T_STATIC,
        T_PARENT,
        T_CALLABLE,
        T_TYPE_UNION,
        T_TYPE_INTERSECTION,
        T_TYPE_OPEN_PARENTHESIS,
        T_TYPE_CLOSE_PARENTHESIS,
    ];

    /**
     * The tokens PHP_CodeSniffer gives a type's parts in a `set` hook's parameter, which it reads as an expression.
     *
     * @var list<int|string>
     */
    private const array HOOK_TYPE_TOKENS = [
        T_BITWISE_OR,
        T_BITWISE_AND,
        T_OPEN_PARENTHESIS,
        T_CLOSE_PARENTHESIS,
        T_ARRAY,
    ];

    /**
     * Check whether a token can be part of a type.
     *
     * @param int|string $code The token's code.
     * @param bool $isHook Whether the type is a `set` hook's parameter type.
     * @return bool True if it can.
     */
    public static function isTypeToken(int|string $code, bool $isHook): bool
    {
        return in_array($code, self::TYPE_TOKENS, true)
            || isset(Tokens::EMPTY_TOKENS[$code])
            || ($isHook && in_array($code, self::HOOK_TYPE_TOKENS, true));
    }

    /**
     * Check whether a token is a `|` or `&` in a type. The caller must have ruled out any bitwise operator that isn't.
     *
     * @param int|string $code The token's code.
     * @return bool True if it is.
     */
    public static function isSymbol(int|string $code): bool
    {
        return in_array($code, [T_TYPE_UNION, T_TYPE_INTERSECTION, T_BITWISE_OR, T_BITWISE_AND], true);
    }

    /**
     * Find the `|` and `&` symbols of the type a symbol belongs to.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of a symbol.
     * @param bool $isHook Whether the symbol is in a `set` hook's parameter type.
     * @return non-empty-list<int> The positions of the symbols, in order.
     */
    public static function findSymbols(File $phpcsFile, int $stackPtr, bool $isHook): array
    {
        $tokens = $phpcsFile->getTokens();
        [$start, $end] = self::findTypeBounds($phpcsFile, $stackPtr, $isHook);
        $symbols = [];
        for ($i = $start; $i <= $end; $i++) {
            if (self::isSymbol($tokens[$i]['code'])) {
                $symbols[] = $i;
            }
        }

        return $symbols === [] ? [$stackPtr] : $symbols;
    }

    /**
     * Find the first and last tokens of the type a symbol belongs to, leaving out whitespace and comments around it.
     *
     * PHPCSUtils finds the type of a parameter, property, typed constant or return type. A `set` hook's parameter type
     * is found by walking out from the symbol, as PHP_CodeSniffer reads it as an expression.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of a symbol.
     * @param bool $isHook Whether the symbol is in a `set` hook's parameter type.
     * @return array{int, int} The positions of the type's first and last tokens.
     */
    public static function findTypeBounds(File $phpcsFile, int $stackPtr, bool $isHook): array
    {
        if ($isHook) {
            return self::findHookTypeBounds($phpcsFile, $stackPtr);
        }

        return self::findParameterTypeBounds($phpcsFile, $stackPtr)
            ?? self::findDeclarationTypeBounds($phpcsFile, $stackPtr)
            ?? [$stackPtr, $stackPtr];
    }

    /**
     * Find the bounds of a parameter's type, from a symbol in it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of a symbol.
     * @return array{int, int}|null The positions of the type's first and last tokens, or null if the symbol isn't in a
     *     parameter's type.
     */
    private static function findParameterTypeBounds(File $phpcsFile, int $stackPtr): ?array
    {
        $owner = Parentheses::getLastOwner($phpcsFile, $stackPtr, Collections::functionDeclarationTokens());
        if ($owner === false) {
            return null;
        }

        foreach (FunctionDeclarations::getParameters($phpcsFile, $owner) as $parameter) {
            $bounds = self::between($parameter['type_hint_token'], $parameter['type_hint_end_token'], $stackPtr);
            if ($bounds !== null) {
                return $bounds;
            }
        }

        return null;
    }

    /**
     * Find the bounds of a property's, typed constant's or return type, from a symbol in it.
     *
     * What follows the type says which it is: a property's variable, a constant's `=`, or the `{`, `;` or `=>` after a
     * return type.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of a symbol.
     * @return array{int, int}|null The positions of the type's first and last tokens, or null if it can't be found.
     */
    private static function findDeclarationTypeBounds(File $phpcsFile, int $stackPtr): ?array
    {
        $tokens = $phpcsFile->getTokens();
        $ends = [T_VARIABLE, T_EQUAL, T_OPEN_CURLY_BRACKET, T_SEMICOLON, T_FN_ARROW];
        $after = $phpcsFile->findNext($ends, ($stackPtr + 1));
        if ($after === false) {
            return null;
        }

        try {
            if ($tokens[$after]['code'] === T_VARIABLE) {
                $property = Variables::getMemberProperties($phpcsFile, $after);

                return self::between($property['type_token'], $property['type_end_token'], $stackPtr);
            }

            if ($tokens[$after]['code'] === T_EQUAL) {
                $const = $phpcsFile->findPrevious(T_CONST, ($stackPtr - 1));
                $constant = $const === false ? null : Constants::getProperties($phpcsFile, $const);

                return $constant === null
                    ? null
                    : self::between($constant['type_token'], $constant['type_end_token'], $stackPtr);
            }

            $function = self::findReturnTypeOwner($phpcsFile, $stackPtr);
            $properties = $function === null ? null : FunctionDeclarations::getProperties($phpcsFile, $function);

            return $properties === null
                ? null
                : self::between($properties['return_type_token'], $properties['return_type_end_token'], $stackPtr);
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * Find the function, closure or arrow function whose return type a symbol is in.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of a symbol.
     * @return int|null The position of the function keyword, or null if there isn't one.
     */
    private static function findReturnTypeOwner(File $phpcsFile, int $stackPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $colon = $phpcsFile->findPrevious(T_COLON, ($stackPtr - 1));
        $closer = $colon === false ? false : $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($colon - 1), null, true);
        if ($closer === false) {
            return null;
        }

        $owner = $tokens[$closer]['parenthesis_owner'] ?? null;
        if (!is_int($owner)) {
            return null;
        }

        // After a closure's `use` list, the parentheses belong to `use`.
        if ($tokens[$owner]['code'] === T_USE) {
            $closure = $phpcsFile->findPrevious(T_CLOSURE, ($owner - 1));

            return $closure === false ? null : $closure;
        }

        return isset(Collections::functionDeclarationTokens()[$tokens[$owner]['code']]) ? $owner : null;
    }

    /**
     * Give a type's bounds if a token is within them.
     *
     * @param mixed $start The position of the type's first token, or false if there's no type.
     * @param mixed $end The position of the type's last token, or false if there's no type.
     * @param int $stackPtr The position of the token.
     * @return array{int, int}|null The bounds, or null if there's no type, or the token is outside it.
     */
    private static function between(mixed $start, mixed $end, int $stackPtr): ?array
    {
        return is_int($start) && is_int($end) && $start <= $stackPtr && $stackPtr <= $end ? [$start, $end] : null;
    }

    /**
     * Find the bounds of a `set` hook's parameter type, by walking out from a symbol over anything that can be in a
     * type. The type stops at the parameter list's own parentheses.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of a symbol.
     * @return array{int, int} The positions of the type's first and last tokens.
     */
    private static function findHookTypeBounds(File $phpcsFile, int $stackPtr): array
    {
        $tokens = $phpcsFile->getTokens();
        $limit = self::findHookParameterListOpener($phpcsFile, $stackPtr);

        $start = $stackPtr;
        while (($start - 1) > $limit && self::isTypeToken($tokens[($start - 1)]['code'], true)) {
            $start--;
        }

        $end = $stackPtr;
        while (isset($tokens[($end + 1)]) && self::isTypeToken($tokens[($end + 1)]['code'], true)) {
            $end++;
        }

        // The symbol itself isn't empty, so both searches stop at it at the latest.
        $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, $start, ($stackPtr + 1), true);
        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, $end, ($stackPtr - 1), true);

        return [$first === false ? $stackPtr : $first, $last === false ? $stackPtr : $last];
    }

    /**
     * Find the opening parenthesis of the `set` hook parameter list a token is in.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token.
     * @return int The position of the parenthesis, or -1 if there isn't one.
     */
    private static function findHookParameterListOpener(File $phpcsFile, int $stackPtr): int
    {
        $nested = $phpcsFile->getTokens()[$stackPtr]['nested_parenthesis'] ?? [];
        foreach (array_reverse(array_keys($nested)) as $opener) {
            if (is_int($opener) && PropertyHookHelper::isHookParameterList($phpcsFile, $opener)) {
                return $opener;
            }
        }

        return -1;
    }
}
