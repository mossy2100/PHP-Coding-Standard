<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Formatting;

use Jumbo\Helpers\SingleLineHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a statement or `case` label split across lines to be written on one line if it fits.
 *
 * PER Coding Style 3.1, section 6.4, says a statement MAY be split at an operator, so this is an extra rule. Other
 * sniffs do the same for a call, an array, a method signature, a condition and a loop header, but nothing covers the
 * rest of a statement, such as `$a = $b` then `+ $c;`, or grouping parentheses.
 *
 * It only reports a line break outside those constructs, so they keep their own messages. Once it does, the fix
 * collapses the whole statement. It's skipped for:
 * - A chain of two or more methods, each on its own line, which is the usual reason to write a chain that way.
 * - An import or a function declaration, which have their own rules.
 * - Whatever SingleLineHelper never collapses, such as a comment, a heredoc or a closure.
 */
class RequireSingleLineStatementSniff implements Sniff
{
    public const string CODE_SHOULD_BE_SINGLE_LINE = 'ShouldBeSingleLine';

    /**
     * Tokens other than a function name that can come before a call's `(`.
     *
     * @var list<int|string>
     */
    private const array CALLABLE_ENDS = [
        T_VARIABLE,
        T_CLOSE_PARENTHESIS,
        T_CLOSE_SQUARE_BRACKET,
        T_LIST,
        T_CLONE,
        T_ISSET,
        T_EMPTY,
        T_UNSET,
        T_EXIT,
    ];

    /**
     * Tokens that end whatever comes before a statement.
     *
     * @var list<int|string>
     */
    private const array STATEMENT_BOUNDARIES = [
        T_SEMICOLON,
        T_OPEN_CURLY_BRACKET,
        T_CLOSE_CURLY_BRACKET,
        T_OPEN_TAG,
        T_OPEN_TAG_WITH_ECHO,
        T_CLOSE_TAG,
        T_ATTRIBUTE_END,
    ];

    /**
     * Maximum line length for a collapsed statement.
     */
    public int $maxLineLength = 120;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_SEMICOLON, T_CASE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the semicolon, or `case`.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // The semicolons in a `for` header.
        if (isset($tokens[$stackPtr]['nested_parenthesis'])) {
            return;
        }

        // A `case` label runs to its colon. Anything else runs from the statement's start to its semicolon.
        $isCase = $tokens[$stackPtr]['code'] === T_CASE;
        $start = $isCase ? $stackPtr : $this->findStart($phpcsFile, $stackPtr);
        $end = $isCase ? ($tokens[$stackPtr]['scope_opener'] ?? null) : $stackPtr;
        if (
            !is_int($end)
            || $tokens[$start]['line'] === $tokens[$end]['line']
            || in_array($tokens[$start]['code'], [T_USE, T_NAMESPACE, T_DECLARE], true)
            || $phpcsFile->findNext(T_FUNCTION, $start, $end) !== false
            || $this->isChain($phpcsFile, $start, $end)
            || !$this->hasOwnLineBreak($phpcsFile, $start, $end)
        ) {
            return;
        }

        $collapsed = SingleLineHelper::collapse($phpcsFile, $start, $end, $this->maxLineLength);
        if ($collapsed === null) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'A statement split across lines fits within %s characters, so it must be written on one line.',
            $start,
            self::CODE_SHOULD_BE_SINGLE_LINE,
            [$this->maxLineLength],
        );
        if ($fix) {
            SingleLineHelper::replace($phpcsFile, $start, $end, $collapsed);
        }
    }

    /**
     * Find a statement's first token, walking back over brackets and closure, anonymous class and `match` bodies, to
     * the end of the statement before it, a block's opening brace, a `case` label or an attribute.
     *
     * PHP_CodeSniffer's File::findStartOfStatement() can stop at a `case` label's start, so its colon would be joined
     * to the next line, or inside a call's parentheses, after an arrow function.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the semicolon.
     * @return int The position of the first token.
     */
    private function findStart(File $phpcsFile, int $stackPtr): int
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = ($stackPtr - 1); $i >= 0; $i--) {
            $code = $tokens[$i]['code'];
            if ($code === T_CLOSE_PARENTHESIS && isset($tokens[$i]['parenthesis_opener'])) {
                $i = $tokens[$i]['parenthesis_opener'];
                continue;
            }

            $isBracket = $code === T_CLOSE_SQUARE_BRACKET || $code === T_CLOSE_SHORT_ARRAY;
            if ($isBracket && isset($tokens[$i]['bracket_opener'])) {
                $i = $tokens[$i]['bracket_opener'];
                continue;
            }

            if ($code === T_CLOSE_CURLY_BRACKET && $this->isExpressionBody($phpcsFile, $i)) {
                $i = $tokens[$i]['bracket_opener'];
                continue;
            }

            $isLabelColon = $code === T_COLON && isset($tokens[$i]['scope_condition']);
            if ($isLabelColon || in_array($code, self::STATEMENT_BOUNDARIES, true)) {
                break;
            }
        }

        $start = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), $stackPtr, true);

        return $start === false ? $stackPtr : $start;
    }

    /**
     * Check whether a closing brace ends a body that's part of an expression, or a dynamic name such as `$a->{$b}`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $closer The position of the closing brace.
     * @return bool True if the statement carries on before it.
     */
    private function isExpressionBody(File $phpcsFile, int $closer): bool
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$closer]['bracket_opener'] ?? null;
        if (!is_int($opener)) {
            return false;
        }

        $condition = $tokens[$closer]['scope_condition'] ?? null;
        if (is_int($condition)) {
            return in_array($tokens[$condition]['code'], [T_CLOSURE, T_ANON_CLASS, T_MATCH], true);
        }

        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($opener - 1), null, true);

        $objectOperators = [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON];

        return $previous !== false && in_array($tokens[$previous]['code'], $objectOperators, true);
    }

    /**
     * Check whether a statement has two or more lines starting with an object operator.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the statement's first token.
     * @param int $end The position of the semicolon or `case` label's colon.
     * @return bool True if it's a chain written one method per line.
     */
    private function isChain(File $phpcsFile, int $start, int $end): bool
    {
        $tokens = $phpcsFile->getTokens();
        $count = 0;
        for ($i = $start; $i < $end; $i++) {
            if ($tokens[$i]['code'] !== T_OBJECT_OPERATOR && $tokens[$i]['code'] !== T_NULLSAFE_OBJECT_OPERATOR) {
                continue;
            }

            $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($i - 1), null, true);
            if ($previous !== false && $tokens[$previous]['line'] !== $tokens[$i]['line']) {
                $count++;
            }
        }

        return $count >= 2;
    }

    /**
     * Check whether a statement has a line break outside a call or an array, whose own sniffs report those.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the statement's first token.
     * @param int $end The position of the semicolon or `case` label's colon.
     * @return bool True if it has one.
     */
    private function hasOwnLineBreak(File $phpcsFile, int $start, int $end): bool
    {
        $tokens = $phpcsFile->getTokens();
        $line = $tokens[$start]['line'];
        for ($i = $start; $i <= $end; $i++) {
            if (isset(Tokens::EMPTY_TOKENS[$tokens[$i]['code']])) {
                continue;
            }

            if ($tokens[$i]['line'] !== $line) {
                return true;
            }

            $closer = $this->findOwnSniffCloser($phpcsFile, $i);
            if ($closer !== null && $closer < $end) {
                $i = $closer;
                $line = $tokens[$closer]['line'];
            }
        }

        return false;
    }

    /**
     * Find the closer of a call's parentheses or an array's brackets, if a token opens one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token.
     * @return int|null The position of the closer, or null if the token doesn't open one.
     */
    private function findOwnSniffCloser(File $phpcsFile, int $stackPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] === T_OPEN_SHORT_ARRAY) {
            $closer = $tokens[$stackPtr]['bracket_closer'] ?? null;

            return is_int($closer) ? $closer : null;
        }

        if ($tokens[$stackPtr]['code'] !== T_OPEN_PARENTHESIS) {
            return null;
        }

        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($stackPtr - 1), null, true);
        $isCall = $previous !== false
            && (
                isset(Tokens::FUNCTION_NAME_TOKENS[$tokens[$previous]['code']])
                || in_array($tokens[$previous]['code'], self::CALLABLE_ENDS, true)
                || isset($tokens[$stackPtr]['parenthesis_owner'])
            );
        $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;

        return $isCall && is_int($closer) ? $closer : null;
    }
}
