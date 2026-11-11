<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Arrays;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;

/**
 * Disallows the opening bracket of a multi-line array on a line of its own, e.g. after `=` or `return`, unless the
 * array is an item in a list.
 *
 * PER Coding Style 3.1, section 11, says that when an array declaration is split across multiple lines, the opening
 * bracket MUST NOT be placed on its own line. Two kinds of array have to start a line, so they're allowed:
 * - An element of a multi-line array, as PER's own example shows.
 * - An argument in an argument list, as section 4.7 puts each argument of a split list on a line of its own.
 *
 * So is a destructuring assignment that starts a statement, which isn't an array declaration.
 *
 * The fix moves the `[` up to the end of the previous line, e.g. `return [`, and Generic.Arrays.ArrayIndent then
 * re-indents the elements. After a comment, it's left to a person.
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
        if (in_array($code, self::STATEMENT_ENDS, true) || $this->isListItem($phpcsFile, $stackPtr)) {
            return;
        }

        $error = 'The opening bracket of a multi-line array must not be on a line of its own';
        $isFixable = !isset(Tokens::COMMENT_TOKENS[$code]) && $code !== T_COMMA;
        if (!FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $stackPtr, self::CODE_OWN_LINE)) {
            return;
        }

        FixerHelper::replaceBetween($phpcsFile, $previous, $stackPtr, $code === T_OPEN_PARENTHESIS ? '' : ' ');
    }

    /**
     * Check whether an array is an item in a list: an element of an array, or an argument in an argument list. Each
     * item of a split list starts a line, so its `[` has to.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the array's `[`.
     * @return bool True if the array follows the list's opening bracket, or a comma in it.
     */
    private function isListItem(File $phpcsFile, int $opener): bool
    {
        $tokens = $phpcsFile->getTokens();
        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($opener - 1), null, true);
        $starts = [T_COMMA, T_OPEN_SHORT_ARRAY, T_OPEN_PARENTHESIS];
        if ($previous === false || !in_array($tokens[$previous]['code'], $starts, true)) {
            return false;
        }

        // The innermost bracket or parenthesis around the array decides what the comma belongs to.
        $container = TokenHelper::findContainer($phpcsFile, $opener);
        if ($container === null || $tokens[$container]['code'] === T_OPEN_SHORT_ARRAY) {
            return $container !== null;
        }

        // Parentheses hold an argument list when they follow something that takes arguments, such as a function's
        // name. A control structure's condition, as in `foreach (`, isn't one.
        $name = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($container - 1), null, true);
        if ($tokens[$container]['code'] !== T_OPEN_PARENTHESIS || $name === false) {
            return false;
        }

        $code = $tokens[$name]['code'];

        return $code === T_CLONE || isset(Collections::parameterPassingTokens()[$code]);
    }
}
