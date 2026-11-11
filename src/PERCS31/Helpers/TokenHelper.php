<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Helper class for moving between tokens: a bracket's partner, the bracket a token is inside, the end of a `new`
 * expression, the argument list of `clone`, and the `...` of a first-class callable.
 */
class TokenHelper
{
    /**
     * Tokens that can name the class in a `new` expression.
     *
     * @var list<int|string>
     */
    private const array CLASS_REFERENCE = [
        T_STRING,
        T_NAME_QUALIFIED,
        T_NAME_FULLY_QUALIFIED,
        T_NAME_RELATIVE,
        T_VARIABLE,
        T_SELF,
        T_STATIC,
        T_PARENT,
    ];

    /**
     * Tokens that dereference the expression before them.
     *
     * @var list<int|string>
     */
    public const array DEREFERENCE = [
        T_OBJECT_OPERATOR,
        T_NULLSAFE_OBJECT_OPERATOR,
        T_DOUBLE_COLON,
        T_OPEN_SQUARE_BRACKET,
        T_OPEN_PARENTHESIS,
    ];

    /**
     * Find the closer that matches an opening parenthesis, bracket, brace or attribute.
     *
     * Only one of these is set on any token, so the order doesn't matter. A keyword such as `function` has its
     * parameter list's closer, not its body's.
     *
     * @param array<string, mixed> $token The token.
     * @return int|null The closer's position, or null if the token doesn't open anything.
     */
    public static function findCloser(array $token): ?int
    {
        $closer = $token['parenthesis_closer'] ?? $token['bracket_closer'] ?? $token['attribute_closer'] ?? null;

        return is_int($closer) ? $closer : null;
    }

    /**
     * Find the opener that matches a closing parenthesis, bracket, brace or attribute.
     *
     * @param array<string, mixed> $token The token.
     * @return int|null The opener's position, or null if the token doesn't close anything.
     */
    public static function findOpener(array $token): ?int
    {
        $opener = $token['parenthesis_opener'] ?? $token['bracket_opener'] ?? $token['attribute_opener'] ?? null;

        return is_int($opener) ? $opener : null;
    }

    /**
     * Find the innermost bracket, parenthesis or brace that a token is inside, within its statement.
     *
     * Walking back, any complete bracketed group is skipped, such as an earlier element. The walk stops at a `;` or a
     * PHP opening tag, the start of the statement.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the token.
     * @return int|null The position of the opener: `(`, `[` or `{`, or null if the token isn't inside one.
     */
    public static function findContainer(File $phpcsFile, int $ptr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $openers = [T_OPEN_PARENTHESIS, T_OPEN_SQUARE_BRACKET, T_OPEN_SHORT_ARRAY, T_OPEN_CURLY_BRACKET];
        for ($i = ($ptr - 1); $i >= 0; $i--) {
            $code = $tokens[$i]['code'];
            if (in_array($code, $openers, true)) {
                return $i;
            }

            if (in_array($code, [T_SEMICOLON, T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO], true)) {
                return null;
            }

            $opener = self::findOpener($tokens[$i]) ?? $tokens[$i]['scope_opener'] ?? null;
            if (is_int($opener) && $opener < $i) {
                $i = $opener;
            }
        }

        return null;
    }

    /**
     * Find the last token of a `new` expression: its argument list's `)`, or an anonymous class's `}`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $newPtr The position of `new`.
     * @param int|null $limit A position the expression must end before, or null for the end of the file.
     * @return int|null The position, or null if there's no argument list, the class reference isn't simple, or it
     *     ends at or after the limit.
     */
    public static function findNewExpressionEnd(File $phpcsFile, int $newPtr, ?int $limit = null): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $limit ??= $phpcsFile->numTokens;
        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($newPtr + 1), $limit, true);
        if ($next === false) {
            return null;
        }

        if ($tokens[$next]['code'] === T_ANON_CLASS) {
            $end = $tokens[$next]['scope_closer'] ?? null;

            return is_int($end) && $end < $limit ? $end : null;
        }

        for ($i = $next; $i < $limit; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_OPEN_PARENTHESIS) {
                $end = $tokens[$i]['parenthesis_closer'] ?? null;

                return is_int($end) && $end < $limit ? $end : null;
            }

            if (!in_array($code, self::CLASS_REFERENCE, true) && !isset(Tokens::EMPTY_TOKENS[$code])) {
                return null;
            }
        }

        return null;
    }

    /**
     * Find the parentheses after `clone`, unless they may only group the object.
     *
     * Before PHP 8.5, `clone` is an operator, so in `clone ($a)->b` the parentheses group `$a`, and `$a->b` is cloned.
     * From PHP 8.5, `clone()` is a function. So parentheses followed by a dereference aren't read as an argument list.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $clonePtr The position of `clone`.
     * @return array{int, int}|null The positions of the `(` and `)`, or null if there's no argument list.
     */
    public static function findCloneArguments(File $phpcsFile, int $clonePtr): ?array
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($clonePtr + 1), null, true);
        if ($opener === false || $tokens[$opener]['code'] !== T_OPEN_PARENTHESIS) {
            return null;
        }

        $closer = $tokens[$opener]['parenthesis_closer'] ?? null;
        if (!is_int($closer)) {
            return null;
        }

        $after = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closer + 1), null, true);
        if ($after !== false && in_array($tokens[$after]['code'], self::DEREFERENCE, true)) {
            return null;
        }

        return [$opener, $closer];
    }

    /**
     * Find the `...` of a first-class callable, as in `foo(...)`, which makes a closure of the function. It's the only
     * thing in the parentheses.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the `(`.
     * @return int|null The position of the `...`, or null if the parentheses hold anything else.
     */
    public static function findCallableEllipsis(File $phpcsFile, int $opener): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$opener]['parenthesis_closer'] ?? null;
        if (!is_int($closer)) {
            return null;
        }

        $ellipsis = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($opener + 1), $closer, true);
        if ($ellipsis === false || $tokens[$ellipsis]['code'] !== T_ELLIPSIS) {
            return null;
        }

        return $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($ellipsis + 1), $closer, true) === false ? $ellipsis : null;
    }
}
