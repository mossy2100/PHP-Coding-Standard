<?php

declare(strict_types=1);

namespace Jumbo\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Helper class for telling a unary `+`, `-` or `&` from a binary one, and a by-reference hook's `&` from either.
 */
class OperatorHelper
{
    /**
     * Tokens that can only come before a unary `+`, `-` or `&`, not a binary one.
     *
     * @var list<int|string>
     */
    private const array BEFORE_UNARY = [
        T_OPEN_PARENTHESIS,
        T_OPEN_SQUARE_BRACKET,
        T_OPEN_SHORT_ARRAY,
        T_COMMA,
        T_RETURN,
        T_ECHO,
        T_PRINT,
        T_YIELD,
        T_YIELD_FROM,
        T_THROW,
        T_INLINE_THEN,
        T_INLINE_ELSE,
        T_DOUBLE_ARROW,
        T_MATCH_ARROW,
        T_FN_ARROW,
        T_COLON,
        T_CASE,
        T_SEMICOLON,
    ];

    /**
     * The tokens PHP_CodeSniffer gives a `&`.
     *
     * @var list<int|string>
     */
    private const array AMPERSANDS = [
        T_BITWISE_AND,
        T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG,
        T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG,
    ];

    /**
     * Tokens that can come directly before a hook name in a property's hook list.
     *
     * @var list<int|string>
     */
    private const array BEFORE_HOOK_NAME = [
        T_OPEN_CURLY_BRACKET,
        T_SEMICOLON,
        T_CLOSE_CURLY_BRACKET,
        T_FINAL,
        T_ATTRIBUTE_END,
    ];

    /**
     * Check whether a `+`, `-` or `&` is a prefix unary operator, judged by the token before it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @return bool True if nothing it could be a binary operator on comes before it.
     */
    public static function isUnary(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($stackPtr - 1), null, true);
        if ($previous === false) {
            return true;
        }

        $previousCode = $tokens[$previous]['code'];

        return in_array($previousCode, self::BEFORE_UNARY, true)
            || isset(Tokens::ASSIGNMENT_TOKENS[$previousCode])
            || isset(Tokens::COMPARISON_TOKENS[$previousCode])
            || isset(Tokens::OPERATORS[$previousCode])
            || isset(Tokens::BOOLEAN_OPERATORS[$previousCode]);
    }

    /**
     * Check whether a `&` marks a by-reference `get` hook, e.g. `&get => $this->items;`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the `&`.
     * @return bool True if the `&` comes before a `get` hook name in a property's hook list.
     */
    public static function isHookReference(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        if (!in_array($tokens[$stackPtr]['code'], self::AMPERSANDS, true)) {
            return false;
        }

        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if ($next === false || $tokens[$next]['code'] !== T_STRING || strtolower($tokens[$next]['content']) !== 'get') {
            return false;
        }

        // A hook name follows the list's opening brace, the end of the previous hook, a modifier or an attribute.
        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($stackPtr - 1), null, true);
        if ($previous === false || !in_array($tokens[$previous]['code'], self::BEFORE_HOOK_NAME, true)) {
            return false;
        }

        return PropertyHookHelper::isInsidePropertyHook($phpcsFile, $stackPtr);
    }
}
