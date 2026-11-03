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
 *
 * PER Coding Style 3.1, section 8, sets the layout for attributes on an anonymous class: they start on the line after
 * `new`, indented once, with `class` on the line after them, at the same indent. The fix puts each attribute block on
 * its own line.
 */
class AttributePlacementSniff implements Sniff
{
    public const string CODE_SAME_LINE = 'SameLine';

    public const string CODE_ANON_CLASS = 'AnonClass';

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

        if ($targetPtr === false) {
            return;
        }

        // An anonymous class's attributes are checked together, from the first one.
        $anonClassPtr = $this->findAnonClass($phpcsFile, $stackPtr);
        if ($anonClassPtr !== null) {
            $newPtr = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($stackPtr - 1), null, true);
            if ($newPtr !== false && $tokens[$newPtr]['code'] === T_NEW) {
                $this->processAnonClass($phpcsFile, $newPtr, $anonClassPtr);
            }

            return;
        }

        // Another attribute follows, so the last one in the group is checked against the target.
        if ($tokens[$targetPtr]['code'] === T_ATTRIBUTE) {
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
     * Check the layout of an anonymous class's attributes, and fix it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $newPtr The position of `new`.
     * @param int $anonClassPtr The position of `class`.
     */
    private function processAnonClass(File $phpcsFile, int $newPtr, int $anonClassPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $newPtr, true);
        assert(is_int($firstOnLine));
        $indent = str_repeat(' ', ($tokens[$firstOnLine]['column'] - 1 + 4));

        // The attribute blocks, and whether each starts a line at the right indent, as `class` must.
        $blocks = [];
        $isValid = true;
        $previous = $newPtr;
        for ($i = ($newPtr + 1); $i < $anonClassPtr; $i++) {
            if ($tokens[$i]['code'] !== T_ATTRIBUTE) {
                continue;
            }

            $closer = $tokens[$i]['attribute_closer'];
            $blocks[] = $phpcsFile->getTokensAsString($i, ($closer - $i + 1));
            $isValid = $isValid && $this->startsLineAt($phpcsFile, $i, $previous, strlen($indent));
            $previous = $closer;
            $i = $closer;
        }

        $isValid = $isValid && $this->startsLineAt($phpcsFile, $anonClassPtr, $previous, strlen($indent));
        if ($isValid) {
            return;
        }

        $error = 'Attributes on an anonymous class must start on the line after "new", indented once, with "class" on '
            . 'the line after them';

        // A comment between them would be lost.
        if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($newPtr + 1), $anonClassPtr) !== false) {
            $phpcsFile->addError($error, $newPtr, self::CODE_ANON_CLASS);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $newPtr, self::CODE_ANON_CLASS);
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($newPtr + 1); $i < $anonClassPtr; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $layout = '';
        foreach ($blocks as $block) {
            $layout .= $phpcsFile->eolChar . $indent . $block;
        }

        $phpcsFile->fixer->addContent($newPtr, $layout . $phpcsFile->eolChar . $indent);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check that a token starts a line after the previous token, at the given indent.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the token.
     * @param int $previousPtr The position of the token that must be on an earlier line.
     * @param int $indent The number of spaces expected before the token.
     * @return bool True if the token is laid out that way.
     */
    private function startsLineAt(File $phpcsFile, int $ptr, int $previousPtr, int $indent): bool
    {
        $tokens = $phpcsFile->getTokens();

        return $tokens[$ptr]['line'] > $tokens[$previousPtr]['line'] && $tokens[$ptr]['column'] === ($indent + 1);
    }

    /**
     * Find the anonymous class an attribute is on, if any.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $attributePtr The position of the attribute opener.
     * @return int|null The position of `class`, or null if the attribute isn't on an anonymous class.
     */
    private function findAnonClass(File $phpcsFile, int $attributePtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $next = $attributePtr;
        while ($tokens[$next]['code'] === T_ATTRIBUTE && isset($tokens[$next]['attribute_closer'])) {
            $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($tokens[$next]['attribute_closer'] + 1), null, true);
            if ($next === false) {
                return null;
            }
        }

        return $tokens[$next]['code'] === T_ANON_CLASS ? $next : null;
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
