<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\Attributes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires an attribute to be on the line before its target, except on a parameter in a single-line parameter list.
 *
 * PER Coding Style 3.0, section 12.2, puts an attribute on its own line before a class, function, method, constant or
 * property, and before a parameter in a multi-line parameter list. In a single-line parameter list, the attribute must
 * be inline with its parameter.
 *
 * SlevomatCodingStandard.Attributes.AttributeAndTargetSpacing can only allow an attribute on the same line as its
 * target everywhere (allowOnSameLine) or nowhere. The standard allows it everywhere, so that a parameter attribute can
 * be inline, and this sniff reports every same-line attribute that isn't on a parameter in a single-line list.
 */
class AttributePlacementSniff implements Sniff
{
    public const string CODE_SAME_LINE = 'SameLine';

    /**
     * The tokens that own a parameter list.
     *
     * @var list<int|string>
     */
    private const array FUNCTION_TOKENS = [T_FUNCTION, T_CLOSURE, T_FN];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_ATTRIBUTE];
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

        // Live coding: an unclosed attribute.
        if (!isset($tokens[$stackPtr]['attribute_closer'])) {
            return;
        }

        $closerPtr = $tokens[$stackPtr]['attribute_closer'];
        $targetPtr = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closerPtr + 1), null, true);

        // Another attribute follows, so the last one in the group is checked against the target.
        if ($targetPtr === false || $tokens[$targetPtr]['code'] === T_ATTRIBUTE) {
            return;
        }

        if ($tokens[$targetPtr]['line'] !== $tokens[$closerPtr]['line']) {
            return;
        }

        // A parameter in a single-line parameter list must have its attribute inline.
        $listOpener = $this->findParameterListOpener($phpcsFile, $stackPtr);
        if ($listOpener !== null) {
            $listCloser = $tokens[$listOpener]['parenthesis_closer'];
            if ($tokens[$listOpener]['line'] === $tokens[$listCloser]['line']) {
                return;
            }
        }

        $error = $listOpener === null
            ? 'An attribute must be on the line before its target.'
            : 'In a multi-line parameter list, an attribute must be on the line before its parameter.';

        // A comment between the attribute and its target would have to move too, so leave that to a person.
        $nextPtr = $closerPtr + 1;
        if ($tokens[$nextPtr]['code'] !== T_WHITESPACE || ($nextPtr + 1) !== $targetPtr) {
            $phpcsFile->addError($error, $stackPtr, self::CODE_SAME_LINE);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, self::CODE_SAME_LINE);
        if (!$fix) {
            return;
        }

        // Indent the target as the first token on the attribute's line is indented.
        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
        assert(is_int($firstOnLine));
        $indent = str_repeat(' ', ($tokens[$firstOnLine]['column'] - 1));
        $phpcsFile->fixer->replaceToken($nextPtr, $phpcsFile->eolChar . $indent);
    }

    /**
     * Find the opening parenthesis of the parameter list an attribute is in, if it is on a parameter.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $attributePtr The position of the attribute opener.
     * @return int|null The position of the parameter list's opening parenthesis, or null if the attribute isn't on a
     *     parameter.
     */
    private function findParameterListOpener(File $phpcsFile, int $attributePtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        if (!isset($tokens[$attributePtr]['nested_parenthesis'])) {
            return null;
        }

        $opener = array_key_last($tokens[$attributePtr]['nested_parenthesis']);
        $owner = $tokens[$opener]['parenthesis_owner'] ?? null;
        if ($owner === null || !in_array($tokens[$owner]['code'], self::FUNCTION_TOKENS, true)) {
            return null;
        }

        return $opener;
    }
}
