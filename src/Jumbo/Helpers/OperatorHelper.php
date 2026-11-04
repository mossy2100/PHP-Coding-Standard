<?php

declare(strict_types=1);

namespace Jumbo\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Helper class for telling a unary `+`, `-` or `&` from a binary one.
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
}
