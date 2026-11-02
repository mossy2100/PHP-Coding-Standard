<?php

declare(strict_types=1);

namespace Mossy2100\Helpers;

use PHP_CodeSniffer\Files\File;

/**
 * Helper class for empty class and function bodies.
 *
 * PER Coding Style 3.0, sections 4 and 4.4, says an empty class or function body should be abbreviated as `{}` on the
 * same line as the previous symbol. An empty anonymous class is treated the same way, for consistency. Several
 * inherited sniffs require the braces on separate lines, so the Mossy2100 versions of them skip a body that this
 * helper says is already on one line.
 */
class EmptyBodyHelper
{
    /**
     * The tokens whose empty body PER says to abbreviate, plus anonymous classes.
     *
     * @var list<int|string>
     */
    public const array OWNER_TOKENS = [T_CLASS, T_ANON_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION];

    /**
     * Check whether a body contains nothing but whitespace.
     *
     * A comment makes the body non-empty, as in PER.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ownerPtr The position of the class or function keyword.
     * @return bool True if the owner has a body with only whitespace inside it.
     */
    public static function isEmpty(File $phpcsFile, int $ownerPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        if (
            !in_array($tokens[$ownerPtr]['code'], self::OWNER_TOKENS, true)
            || !isset($tokens[$ownerPtr]['scope_opener'], $tokens[$ownerPtr]['scope_closer'])
        ) {
            return false;
        }

        $opener = $tokens[$ownerPtr]['scope_opener'];
        $closer = $tokens[$ownerPtr]['scope_closer'];

        return $phpcsFile->findNext(T_WHITESPACE, ($opener + 1), $closer, true) === false;
    }

    /**
     * Check whether an empty body is on one line, with the previous symbol, e.g. `class Foo {}` or `) {}`.
     *
     * The spacing around and inside the braces may still be wrong. Mossy2100.Formatting.EmptyBody checks that.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ownerPtr The position of the class or function keyword.
     * @return bool True if the body is empty, and its braces are on the same line as the previous symbol.
     */
    public static function isOnOneLine(File $phpcsFile, int $ownerPtr): bool
    {
        if (!self::isEmpty($phpcsFile, $ownerPtr)) {
            return false;
        }

        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$ownerPtr]['scope_opener'];
        $closer = $tokens[$ownerPtr]['scope_closer'];
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($opener - 1), null, true);

        return $previous !== false
            && $tokens[$previous]['line'] === $tokens[$opener]['line']
            && $tokens[$opener]['line'] === $tokens[$closer]['line'];
    }
}
