<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Operators;

/**
 * Helper class for telling a unary `+`, `-` or `&` from a binary one.
 */
class OperatorHelper
{
    /**
     * Tokens that can only come before a unary `+`, `-` or `&`, not a binary one.
     *
     * Operators::isUnaryPlusMinus() has a similar list, but only for `+` and `-`, so the `&` checks need this one.
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
        T_OPEN_CURLY_BRACKET,
        T_OPEN_TAG,
        T_OPEN_TAG_WITH_ECHO,
    ];

    /**
     * Constructs whose closing brace ends an expression, so a `+` or `-` after it is binary.
     *
     * @var list<int|string>
     */
    private const array EXPRESSION_BODY_OWNERS = [T_MATCH, T_CLOSURE, T_ANON_CLASS];

    /**
     * Check whether a closing brace ends a statement: it closes a block, such as an `if`'s or a function's, and not the
     * body of a closure, anonymous class or `match`, which is part of an expression.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $closer The position of the closing brace.
     * @return bool True if it closes a block that ends a statement.
     */
    public static function closesStatementBlock(File $phpcsFile, int $closer): bool
    {
        $tokens = $phpcsFile->getTokens();
        $owner = $tokens[$closer]['scope_condition'] ?? null;

        return is_int($owner) && !in_array($tokens[$owner]['code'], self::EXPRESSION_BODY_OWNERS, true);
    }

    /**
     * Check whether a `+`, `-` or `&` is a prefix unary operator, judged by the token before it, such as an operator, a
     * `(` or `,`, or the end of a statement or block.
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

        // A block's closing brace ends a statement, but one closing a closure, match or `${...}` ends an expression.
        if ($previousCode === T_CLOSE_CURLY_BRACKET) {
            return self::closesStatementBlock($phpcsFile, $previous);
        }

        $isPlusMinus = in_array($tokens[$stackPtr]['code'], [T_PLUS, T_MINUS], true);

        return in_array($previousCode, self::BEFORE_UNARY, true)
            || isset(Tokens::ASSIGNMENT_TOKENS[$previousCode])
            || isset(Tokens::COMPARISON_TOKENS[$previousCode])
            || isset(Tokens::OPERATORS[$previousCode])
            || isset(Tokens::BOOLEAN_OPERATORS[$previousCode])
            || isset(Tokens::CAST_TOKENS[$previousCode])
            || ($isPlusMinus && Operators::isUnaryPlusMinus($phpcsFile, $stackPtr));
    }

    /**
     * Get the binary operators the custom sniffs share: arithmetic, bitwise, comparison and boolean operators, `??`,
     * `.`, `instanceof`, and the pipe operator `|>` when PHP defines it. Each sniff adds any others it needs, such as
     * assignment operators.
     *
     * @return list<int|string> The token codes.
     */
    public static function binaryOperators(): array
    {
        $operators = Tokens::OPERATORS + Tokens::COMPARISON_TOKENS + Tokens::BOOLEAN_OPERATORS;

        return [...array_values($operators), T_STRING_CONCAT, T_INSTANCEOF, ...self::pipeOperator()];
    }

    /**
     * Get the pipe operator `|>`, if PHP defines it. PHP_CodeSniffer 4.0 doesn't define T_PIPE itself, but passes PHP's
     * own token through on PHP 8.5.
     *
     * @return list<int|string> The token code, or nothing before PHP 8.5.
     */
    public static function pipeOperator(): array
    {
        return defined('T_PIPE') ? [T_PIPE] : [];
    }
}
