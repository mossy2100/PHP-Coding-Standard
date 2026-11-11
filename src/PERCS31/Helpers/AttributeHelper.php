<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\Parentheses;

/**
 * Helper class for finding where an attribute is.
 */
class AttributeHelper
{
    /**
     * Find the opening parenthesis of the parameter list an attribute is in, if it is on a parameter.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $attributePtr The position of the attribute opener.
     * @return int|null The position of the parameter list's opening parenthesis, or null if the attribute isn't on a
     *     parameter.
     */
    public static function findParameterListOpener(File $phpcsFile, int $attributePtr): ?int
    {
        $opener = Parentheses::getLastOpener($phpcsFile, $attributePtr);
        if ($opener === false) {
            return null;
        }

        $isFunction = Parentheses::isOwnerIn($phpcsFile, $opener, Collections::functionDeclarationTokens());

        return $isFunction || PropertyHookHelper::isHookParameterList($phpcsFile, $opener) ? $opener : null;
    }

    /**
     * Find the opening parenthesis of a single-line parameter or argument list that an attribute's target is in.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $attributePtr The position of an attribute block's opener.
     * @return int|null The list's opening parenthesis, or null if the attribute isn't on a parameter, or a call's
     *     argument, in a list on one line.
     */
    public static function findSingleLineListOpener(File $phpcsFile, int $attributePtr): ?int
    {
        $opener = Parentheses::getLastOpener($phpcsFile, $attributePtr);
        if ($opener === false) {
            return null;
        }

        $tokens = $phpcsFile->getTokens();
        if ($tokens[$opener]['line'] !== $tokens[$tokens[$opener]['parenthesis_closer']]['line']) {
            return null;
        }

        if (self::findParameterListOpener($phpcsFile, $attributePtr) === $opener) {
            return $opener;
        }

        // In a call, the attributes start an argument, so they follow the `(` or a `,`.
        $namePtr = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($opener - 1), null, true);
        if ($namePtr === false || !isset(Collections::functionCallTokens()[$tokens[$namePtr]['code']])) {
            return null;
        }

        $first = self::findFirstAttribute($phpcsFile, $attributePtr);
        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($first - 1), null, true);

        return $previous === $opener || ($previous !== false && $tokens[$previous]['code'] === T_COMMA)
            ? $opener
            : null;
    }

    /**
     * Find the first of a run of attribute blocks, from any block in it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $attributePtr The position of an attribute block's opener.
     * @return int The position of the first attribute block's opener.
     */
    public static function findFirstAttribute(File $phpcsFile, int $attributePtr): int
    {
        $tokens = $phpcsFile->getTokens();
        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($attributePtr - 1), null, true);
        while ($previous !== false && $tokens[$previous]['code'] === T_ATTRIBUTE_END) {
            $attributePtr = $tokens[$previous]['attribute_opener'];
            $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($attributePtr - 1), null, true);
        }

        return $attributePtr;
    }
}
