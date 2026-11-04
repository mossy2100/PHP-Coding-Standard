<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Disallows the opening bracket of a multi-line array on a line of its own, e.g. as a function call's argument.
 *
 * PER Coding Style 3.1, section 11, says that when an array declaration is split across multiple lines, the opening
 * bracket MUST NOT be placed on its own line. PER's own example puts a nested array's `[` on its own line, so an array
 * that's an element of a multi-line array is allowed. So is a destructuring assignment that starts a statement, which
 * isn't an array declaration.
 *
 * The fix moves the `[` up to the end of the previous line, e.g. `foo([`, and Generic.Arrays.ArrayIndent then
 * re-indents the elements. For a call's only argument, it also moves the call's `)` up after the `]`, and drops the
 * trailing comma. When the array follows a comma, as in a call with several arguments, it's left to a person.
 */
class OpeningBracketPlacementSniff implements Sniff
{
    public const string CODE_OWN_LINE = 'OwnLine';

    /**
     * Tokens that end the previous statement, so a `[` after them starts a destructuring assignment.
     *
     * @var list<int|string>
     */
    private const array STATEMENT_ENDS = [
        T_SEMICOLON,
        T_OPEN_CURLY_BRACKET,
        T_CLOSE_CURLY_BRACKET,
        T_OPEN_TAG,
        T_COLON,
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_OPEN_SHORT_ARRAY];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$stackPtr]['bracket_closer'] ?? null;
        if (!is_int($closer) || $tokens[$closer]['line'] === $tokens[$stackPtr]['line']) {
            return;
        }

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($previous === false || $tokens[$previous]['line'] === $tokens[$stackPtr]['line']) {
            return;
        }

        $code = $tokens[$previous]['code'];
        if (in_array($code, self::STATEMENT_ENDS, true) || $this->isArrayElement($phpcsFile, $stackPtr, $previous)) {
            return;
        }

        $error = 'The opening bracket of a multi-line array must not be on a line of its own';
        $onlyArgumentCloser = $this->findOnlyArgumentCloser($phpcsFile, $previous, $closer);
        $isFixable = !isset(Tokens::COMMENT_TOKENS[$code])
            && $code !== T_COMMA
            && ($code !== T_OPEN_PARENTHESIS || $onlyArgumentCloser !== null);
        if (!$isFixable) {
            $phpcsFile->addError($error, $stackPtr, self::CODE_OWN_LINE);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, self::CODE_OWN_LINE);
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($previous + 1); $i < $stackPtr; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        if ($code === T_OPEN_PARENTHESIS) {
            for ($i = ($closer + 1); $i < $onlyArgumentCloser; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        } else {
            $phpcsFile->fixer->addContent($previous, ' ');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check whether an array is an element of a multi-line array, which PER allows to start on its own line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the array's `[`.
     * @param int $previous The position of the token before it.
     * @return bool True if the token before is the enclosing array's `[`, or a comma in it.
     */
    private function isArrayElement(File $phpcsFile, int $opener, int $previous): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$previous]['code'] !== T_COMMA && $tokens[$previous]['code'] !== T_OPEN_SHORT_ARRAY) {
            return false;
        }

        // The innermost bracket or parenthesis around the array decides what the comma belongs to.
        for ($i = ($opener - 1); $i >= 0; $i--) {
            $code = $tokens[$i]['code'];
            if ($code === T_CLOSE_SHORT_ARRAY || $code === T_CLOSE_SQUARE_BRACKET) {
                $i = $tokens[$i]['bracket_opener'] ?? $i;
                continue;
            }

            if ($code === T_CLOSE_PARENTHESIS) {
                $i = $tokens[$i]['parenthesis_opener'] ?? $i;
                continue;
            }

            if ($code === T_CLOSE_CURLY_BRACKET) {
                $i = $tokens[$i]['scope_opener'] ?? $tokens[$i]['bracket_opener'] ?? $i;
                continue;
            }

            if (in_array($code, [T_OPEN_PARENTHESIS, T_OPEN_SQUARE_BRACKET, T_OPEN_CURLY_BRACKET, T_SEMICOLON], true)) {
                return false;
            }

            if ($code === T_OPEN_SHORT_ARRAY) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the closing parenthesis of a call whose only argument is the array.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $previous The position of the token before the array's `[`.
     * @param int $closer The position of the array's `]`.
     * @return int|null The position of the `)`, or null if the array isn't the only argument.
     */
    private function findOnlyArgumentCloser(File $phpcsFile, int $previous, int $closer): ?int
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$previous]['code'] !== T_OPEN_PARENTHESIS) {
            return null;
        }

        $next = $phpcsFile->findNext(T_WHITESPACE, ($closer + 1), null, true);
        if ($next !== false && $tokens[$next]['code'] === T_COMMA) {
            $next = $phpcsFile->findNext(T_WHITESPACE, ($next + 1), null, true);
        }

        if ($next === false || ($tokens[$previous]['parenthesis_closer'] ?? null) !== $next) {
            return null;
        }

        return $next;
    }
}
