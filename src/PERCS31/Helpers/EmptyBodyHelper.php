<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;

/**
 * Helper class for empty class, function, closure and anonymous class bodies.
 *
 * PER Coding Style 3.1, sections 4 and 4.4, says an empty class or function body should be abbreviated as `{}` on the
 * same line as the previous symbol. Section 7 says an empty closure body must be, and section 8 says an anonymous class
 * must follow the same rules as a closure. Several inherited sniffs require the braces on separate lines, so the
 * extended versions of them skip a body that this helper says is already on one line.
 *
 * Section 5 says nothing about a control structure's empty body, so PERCS31 allows `{}` there too, and the Jumbo
 * standard requires it.
 */
class EmptyBodyHelper
{
    /**
     * The tokens whose empty body PER says to abbreviate.
     *
     * @var list<int|string>
     */
    public const array OWNER_TOKENS = [T_CLASS, T_ANON_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION, T_CLOSURE];

    /**
     * The control structures with a body in braces, whose empty body may be written as `{}`. These are the tokens
     * Squiz.ControlStructures.ControlSignature registers.
     *
     * @var list<int|string>
     */
    public const array CONTROL_STRUCTURE_TOKENS = [
        T_TRY,
        T_CATCH,
        T_FINALLY,
        T_DO,
        T_WHILE,
        T_FOR,
        T_IF,
        T_FOREACH,
        T_ELSE,
        T_ELSEIF,
        T_SWITCH,
        T_MATCH,
    ];

    /**
     * Check whether a body contains nothing but whitespace.
     *
     * A comment makes the body non-empty, as in PER.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ownerPtr The position of the class, function or control structure keyword.
     * @return bool True if the owner has a body in braces with only whitespace inside it.
     */
    public static function isEmpty(File $phpcsFile, int $ownerPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $code = $tokens[$ownerPtr]['code'];
        if (
            (!in_array($code, self::OWNER_TOKENS, true) && !in_array($code, self::CONTROL_STRUCTURE_TOKENS, true))
            || !isset($tokens[$ownerPtr]['scope_opener'], $tokens[$ownerPtr]['scope_closer'])
        ) {
            return false;
        }

        $opener = $tokens[$ownerPtr]['scope_opener'];
        $closer = $tokens[$ownerPtr]['scope_closer'];

        // The alternative syntax, as in `if ($a):`, has no braces.
        return $tokens[$opener]['code'] === T_OPEN_CURLY_BRACKET
            && $phpcsFile->findNext(T_WHITESPACE, ($opener + 1), $closer, true) === false;
    }

    /**
     * Check whether an empty body is on one line, with the previous symbol, e.g. `class Foo {}` or `) {}`.
     *
     * The spacing around and inside the braces may still be wrong. PERCS31.Formatting.EmptyBody checks that.
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
