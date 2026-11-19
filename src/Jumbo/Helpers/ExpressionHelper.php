<?php

declare(strict_types=1);

namespace Jumbo\Helpers;

use PERCS31\Helpers\OperatorHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Helper class for reading an expression's binary operators and grouping parentheses into a tree, shared by
 * Jumbo.Formatting.ExpressionFormat, which lays expressions out, and Jumbo.Formatting.RequireSingleLineStatement,
 * which leaves them to it.
 */
class ExpressionHelper
{
    /**
     * Binary operators by precedence, from the loosest to the tightest, as in the PHP manual. The pipe operator `|>` is
     * added when PHP defines it.
     *
     * Assignment operators, and `and`, `or` and `xor`, which bind more loosely than assignment, aren't included.
     *
     * @var list<list<int|string>>
     */
    private const array PRECEDENCE_LEVELS = [
        [T_COALESCE],
        [T_BOOLEAN_OR],
        [T_BOOLEAN_AND],
        [T_BITWISE_OR],
        [T_BITWISE_XOR],
        [T_BITWISE_AND],
        [T_IS_EQUAL, T_IS_NOT_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL, T_SPACESHIP],
        [T_LESS_THAN, T_GREATER_THAN, T_IS_SMALLER_OR_EQUAL, T_IS_GREATER_OR_EQUAL],
        [],
        [T_STRING_CONCAT],
        [T_SL, T_SR],
        [T_PLUS, T_MINUS],
        [T_MULTIPLY, T_DIVIDE, T_MODULUS],
        [T_INSTANCEOF],
        [T_POW],
    ];

    /**
     * The index of the pipe operator's level in PRECEDENCE_LEVELS.
     */
    private const int PIPE_LEVEL = 8;

    /**
     * Tokens that end an expression, besides assignment operators and unmatched brackets.
     *
     * @var list<int|string>
     */
    private const array BOUNDARIES = [
        T_SEMICOLON,
        T_COMMA,
        T_DOUBLE_ARROW,
        T_MATCH_ARROW,
        T_FN_ARROW,
        T_INLINE_THEN,
        T_INLINE_ELSE,
        T_COLON,
        T_RETURN,
        T_ECHO,
        T_PRINT,
        T_YIELD,
        T_YIELD_FROM,
        T_THROW,
        T_CASE,
        T_AS,
        T_ELLIPSIS,
        T_LOGICAL_AND,
        T_LOGICAL_OR,
        T_LOGICAL_XOR,
        T_INCLUDE,
        T_INCLUDE_ONCE,
        T_REQUIRE,
        T_REQUIRE_ONCE,
        T_OPEN_TAG,
        T_OPEN_TAG_WITH_ECHO,
        T_CLOSE_TAG,
        T_INLINE_HTML,
        T_OPEN_PARENTHESIS,
        T_OPEN_SQUARE_BRACKET,
        T_OPEN_SHORT_ARRAY,
        T_OPEN_CURLY_BRACKET,
        T_OPEN_USE_GROUP,
        T_ATTRIBUTE,
    ];

    /**
     * Prefix operators that can come before an operand's grouping parentheses, besides casts.
     *
     * @var list<int|string>
     */
    private const array PREFIXES = [T_BOOLEAN_NOT, T_BITWISE_NOT, T_ASPERAND, T_MINUS, T_PLUS];

    /**
     * Each binary operator's precedence, once worked out.
     *
     * @var array<int|string, int>|null
     */
    private static ?array $precedences = null;

    /**
     * Get each binary operator's precedence.
     *
     * @return array<int|string, int> Token code => precedence, from 0 for the loosest.
     */
    public static function precedences(): array
    {
        if (self::$precedences === null) {
            self::$precedences = [];
            foreach (self::PRECEDENCE_LEVELS as $precedence => $codes) {
                $codes = $precedence === self::PIPE_LEVEL ? OperatorHelper::pipeOperator() : $codes;
                foreach ($codes as $code) {
                    self::$precedences[$code] = $precedence;
                }
            }
        }

        return self::$precedences;
    }

    /**
     * Check whether a token is one of the binary operators, not a unary `+` or `-`, or a reference.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token.
     * @return bool True if it is.
     */
    public static function isBinary(File $phpcsFile, int $stackPtr): bool
    {
        $code = $phpcsFile->getTokens()[$stackPtr]['code'];
        if (!isset(self::precedences()[$code])) {
            return false;
        }

        return !in_array($code, [T_PLUS, T_MINUS, T_BITWISE_AND], true)
            || !OperatorHelper::isUnary($phpcsFile, $stackPtr);
    }

    /**
     * Check whether parentheses group part of an expression, rather than belong to a call, a declaration or a control
     * structure.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the opening parenthesis.
     * @return bool True if they're grouping parentheses.
     */
    public static function isGrouping(File $phpcsFile, int $opener): bool
    {
        $tokens = $phpcsFile->getTokens();

        return $tokens[$opener]['code'] === T_OPEN_PARENTHESIS && !isset($tokens[$opener]['parenthesis_owner'])
            && !TokenHelper::isArgumentListOpener($phpcsFile, $opener);
    }

    /**
     * Read the expression an operator is part of, unless it's left alone.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @return array{ExpressionNode, int, int}|null The expression's tree, and the positions of the tokens that end it
     * before and after, or null if it's left alone or has a line break inside an operand. The one before is -1 at the
     * start of the file.
     */
    public static function read(File $phpcsFile, int $stackPtr): ?array
    {
        $region = self::findExpression($phpcsFile, $stackPtr);
        if ($region === null || self::isSkipped($phpcsFile, $region[0], $region[1], $region[2])) {
            return null;
        }

        $root = self::parse($phpcsFile, $region[0], $region[1]);

        return $root === null || $root->kind === ExpressionNode::OPERAND ? null : [$root, $region[2], $region[3]];
    }

    /**
     * Find the expression an operator is part of, through any grouping parentheses around it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @return array{int, int, int, int}|null The positions of the first and last tokens, and of the tokens that end the
     * expression before and after it, or null if there's an arrow function in the way. The one before is -1 at the
     * start of the file.
     */
    public static function findExpression(File $phpcsFile, int $stackPtr): ?array
    {
        $tokens = $phpcsFile->getTokens();
        for ($before = ($stackPtr - 1); $before >= 0; $before--) {
            $code = $tokens[$before]['code'];
            if ($code === T_CLOSE_CURLY_BRACKET && OperatorHelper::closesStatementBlock($phpcsFile, $before)) {
                break;
            }

            // Skip a bracketed group, or a closure, `match` or anonymous class body, back to its keyword.
            $opener = TokenHelper::findOpener($tokens[$before]);
            if ($opener !== null) {
                $owner = $tokens[$opener]['scope_condition'] ?? null;
                $before = is_int($owner) && $owner < $opener ? $owner : $opener;
                continue;
            }

            if (self::isGrouping($phpcsFile, $before)) {
                continue;
            }

            if (in_array($code, self::BOUNDARIES, true) || isset(Tokens::ASSIGNMENT_TOKENS[$code])) {
                break;
            }
        }

        $count = count($tokens);
        for ($after = ($stackPtr + 1); $after < $count; $after++) {
            $code = $tokens[$after]['code'];
            if ($code === T_FN) {
                return null;
            }

            // Skip a closure, `match` or anonymous class to the end of its body.
            $bodyCloser = $tokens[$after]['scope_closer'] ?? null;
            if (in_array($code, [T_CLOSURE, T_MATCH, T_ANON_CLASS], true) && is_int($bodyCloser)) {
                $after = $bodyCloser;
                continue;
            }

            $opener = TokenHelper::findOpener($tokens[$after]);
            if ($opener !== null) {
                if (self::isGrouping($phpcsFile, $opener)) {
                    continue;
                }

                break;
            }

            $closer = TokenHelper::findCloser($tokens[$after]);
            if ($closer !== null && $closer > $after) {
                $after = $closer;
                continue;
            }

            if (in_array($code, self::BOUNDARIES, true) || isset(Tokens::ASSIGNMENT_TOKENS[$code])) {
                break;
            }
        }

        $start = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($before + 1), $stackPtr, true);
        $end = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($after - 1), $stackPtr, true);
        if ($start === false || $end === false) {
            return null;
        }

        return [$start, $end, $before, $after];
    }

    /**
     * Check whether an expression is left alone: a `case` condition, a `catch` type list, or one with a comment, a
     * heredoc, a nowdoc, a multi-line string or an arrow function.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the expression's first token.
     * @param int $end The position of its last token.
     * @param int $before The position of the token before it, or -1.
     * @return bool True if it's skipped.
     */
    public static function isSkipped(File $phpcsFile, int $start, int $end, int $before): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($before >= 0) {
            $owner = $tokens[$before]['parenthesis_owner'] ?? null;
            if ($tokens[$before]['code'] === T_CASE || (is_int($owner) && $tokens[$owner]['code'] === T_CATCH)) {
                return true;
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            $code = $tokens[$i]['code'];
            if (
                isset(Tokens::COMMENT_TOKENS[$code]) || in_array($code, [T_START_HEREDOC, T_START_NOWDOC, T_FN], true)
                || ($code !== T_WHITESPACE && str_contains($tokens[$i]['content'], "\n"))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse part of an expression into a tree of its operands, groups and levels of operators.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the first token.
     * @param int $end The position of the last token.
     * @return ExpressionNode|null The node, or null if an operand has a line break inside it.
     */
    public static function parse(File $phpcsFile, int $start, int $end): ?ExpressionNode
    {
        $tokens = $phpcsFile->getTokens();
        $operators = [];
        for ($i = $start; $i <= $end; $i++) {
            if ($i !== $start && self::isBinary($phpcsFile, $i)) {
                $operators[] = $i;
            }

            $closer = TokenHelper::findCloser($tokens[$i]);
            if ($closer !== null && $closer > $i) {
                $i = $closer;
            }
        }

        return $operators === []
            ? self::parseOperand($phpcsFile, $start, $end)
            : self::parseLevel($phpcsFile, $start, $end, $operators);
    }

    /**
     * Parse a run of operands and operators into a level of its loosest operators, whose operands are levels of the
     * tighter ones.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the first token.
     * @param int $end The position of the last token.
     * @param non-empty-list<int> $operators The positions of the operators, outside any brackets.
     * @return ExpressionNode|null The level, or null if an operand has a line break inside it.
     */
    private static function parseLevel(File $phpcsFile, int $start, int $end, array $operators): ?ExpressionNode
    {
        $tokens = $phpcsFile->getTokens();
        $precedences = self::precedences();
        $loosest = min(
            array_map(static fn(int $operator): int => $precedences[$tokens[$operator]['code']], $operators),
        );

        $children = [];
        $levelOperators = [];
        $flatLength = 0;
        $segmentStart = $start;
        $segmentOperators = [];
        foreach ([...$operators, null] as $operator) {
            if ($operator !== null && $precedences[$tokens[$operator]['code']] !== $loosest) {
                $segmentOperators[] = $operator;
                continue;
            }

            $segmentEnd = $end;
            if ($operator !== null) {
                $segmentEnd = $phpcsFile->findPrevious(T_WHITESPACE, ($operator - 1), null, true);
            }

            if ($segmentEnd === false) {
                return null;
            }

            $child = $segmentOperators === []
                ? self::parseOperand($phpcsFile, $segmentStart, $segmentEnd)
                : self::parseLevel($phpcsFile, $segmentStart, $segmentEnd, $segmentOperators);
            if ($child === null) {
                return null;
            }

            $children[] = $child;
            $flatLength += $child->flatLength;
            if ($operator !== null) {
                $next = $phpcsFile->findNext(T_WHITESPACE, ($operator + 1), null, true);
                if ($next === false) {
                    return null;
                }

                $levelOperators[] = $operator;
                $flatLength += mb_strlen($tokens[$operator]['content']) + 2;
                $segmentStart = $next;
                $segmentOperators = [];
            }
        }

        return new ExpressionNode(ExpressionNode::LEVEL, $start, $end, $flatLength, $children, $levelOperators);
    }

    /**
     * Parse an operand: grouping parentheses, with any prefix operators before them, or anything else, which is kept as
     * it's written.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the first token.
     * @param int $end The position of the last token.
     * @return ExpressionNode|null The operand or group, or null if it has a line break inside it.
     */
    private static function parseOperand(File $phpcsFile, int $start, int $end): ?ExpressionNode
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $start;
        while (
            $opener < $end
            && ($tokens[$opener]['code'] === T_WHITESPACE || in_array($tokens[$opener]['code'], self::PREFIXES, true)
                || isset(Tokens::CAST_TOKENS[$tokens[$opener]['code']]))
        ) {
            $opener++;
        }

        $prefix = $phpcsFile->getTokensAsString($start, ($opener - $start));
        $isGroup = self::isGrouping($phpcsFile, $opener) && ($tokens[$opener]['parenthesis_closer'] ?? null) === $end;
        if (!$isGroup) {
            $text = $phpcsFile->getTokensAsString($start, ($end - $start + 1));

            if (str_contains($text, "\n")) {
                return null;
            }

            return new ExpressionNode(ExpressionNode::OPERAND, $start, $end, mb_strlen($text));
        }

        $innerStart = $phpcsFile->findNext(T_WHITESPACE, ($opener + 1), $end, true);
        $innerEnd = $phpcsFile->findPrevious(T_WHITESPACE, ($end - 1), $opener, true);
        if (str_contains($prefix, "\n") || $innerStart === false || $innerEnd === false) {
            return null;
        }

        $inner = self::parse($phpcsFile, $innerStart, $innerEnd);
        if ($inner === null) {
            return null;
        }

        $flatLength = mb_strlen($prefix) + $inner->flatLength + 2;

        return new ExpressionNode(ExpressionNode::GROUP, $start, $end, $flatLength, [$inner], [], $opener, $end);
    }

    /**
     * Check whether a line break between two tokens is part of an expression's layout, which
     * Jumbo.Formatting.ExpressionFormat checks: next to a binary operator, or just inside grouping parentheses next to
     * or holding one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $before The position of the code before the line break.
     * @param int $after The position of the code after it.
     * @return bool True if it is.
     */
    public static function isExpressionBreak(File $phpcsFile, int $before, int $after): bool
    {
        $operator = self::findOperatorBeside($phpcsFile, $before, $after);

        return $operator !== null && self::read($phpcsFile, $operator) !== null;
    }

    /**
     * Find a binary operator a line break belongs to: one on either side of it, or, for a break just inside grouping
     * parentheses, the first one inside them or one beside them.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $before The position of the code before the line break.
     * @param int $after The position of the code after it.
     * @return int|null The operator's position, or null if there's none.
     */
    private static function findOperatorBeside(File $phpcsFile, int $before, int $after): ?int
    {
        foreach ([$before, $after] as $ptr) {
            if (self::isBinary($phpcsFile, $ptr)) {
                return $ptr;
            }
        }

        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$after]['code'] === T_CLOSE_PARENTHESIS
            ? ($tokens[$after]['parenthesis_opener'] ?? null)
            : $before;
        if (!is_int($opener) || !self::isGrouping($phpcsFile, $opener)) {
            return null;
        }

        $closer = $tokens[$opener]['parenthesis_closer'];
        for ($i = ($opener + 1); $i < $closer; $i++) {
            if (self::isBinary($phpcsFile, $i)) {
                return $i;
            }

            $nestedCloser = TokenHelper::findCloser($tokens[$i]);
            if ($nestedCloser !== null && $nestedCloser > $i) {
                $i = $nestedCloser;
            }
        }

        $outside = [
            $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($opener - 1), null, true),
            $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closer + 1), null, true),
        ];
        foreach ($outside as $ptr) {
            if ($ptr !== false && self::isBinary($phpcsFile, $ptr)) {
                return $ptr;
            }
        }

        return null;
    }
}
