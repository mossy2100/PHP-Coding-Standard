<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

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
        $start = $stackPtr;
        while ($start > 0 && self::isTypeToken($tokens[($start - 1)]['code'], $isHook)) {
            $start--;
        }

        $symbols = [];
        for ($i = $start; isset($tokens[$i]) && self::isTypeToken($tokens[$i]['code'], $isHook); $i++) {
            if (self::isSymbol($tokens[$i]['code'])) {
                $symbols[] = $i;
            }
        }

        return $symbols === [] ? [$stackPtr] : $symbols;
    }

    /**
     * Find the first and last tokens of the type a symbol belongs to, leaving out whitespace and comments around it.
     *
     * In a `set` hook's parameter, the type stops at the parameter list's own parentheses. In a typed constant, as in
     * `const int|null A = 1`, it stops before the constant's name.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of a symbol.
     * @param bool $isHook Whether the symbol is in a `set` hook's parameter type.
     * @return array{int, int} The positions of the type's first and last tokens.
     */
    public static function findTypeBounds(File $phpcsFile, int $stackPtr, bool $isHook): array
    {
        $tokens = $phpcsFile->getTokens();
        $limit = $isHook ? self::findHookParameterListOpener($phpcsFile, $stackPtr) : -1;

        $start = $stackPtr;
        while (($start - 1) > $limit && self::isTypeToken($tokens[($start - 1)]['code'], $isHook)) {
            $start--;
        }

        $end = $stackPtr;
        while (isset($tokens[($end + 1)]) && self::isTypeToken($tokens[($end + 1)]['code'], $isHook)) {
            $end++;
        }

        // The symbol itself isn't empty, so both searches stop at it at the latest.
        $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, $start, ($stackPtr + 1), true);
        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, $end, ($stackPtr - 1), true);
        $start = $first === false ? $stackPtr : $first;
        $end = $last === false ? $stackPtr : $last;

        // A constant's name follows its type, and is a T_STRING too.
        $before = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($start - 1), null, true);
        if ($before !== false && $tokens[$before]['code'] === T_CONST) {
            $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($end - 1), ($stackPtr - 1), true);
            $end = $last === false ? $stackPtr : $last;
        }

        return [$start, $end];
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
