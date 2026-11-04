<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Attributes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\Parentheses;
use PHPCSUtils\Utils\PassedParameters;

/**
 * Requires an attribute to be on the line before its target, except on a parameter in a single-line parameter list.
 *
 * PER Coding Style 3.0, section 12.2, puts an attribute on its own line before a class, function, method, constant or
 * property, and before a parameter in a multi-line parameter list. In a single-line parameter list, the attribute must
 * be inline with its parameter.
 *
 * A closure or arrow function isn't a structure in that list, but as a call's argument, its attribute follows the
 * parameter rule: inline after a single space when the argument list is on one line, and on its own line before the
 * closure, indented the same, when the list is split. A closure split over lines doesn't split the list, as section
 * 4.7 says, so `usort($items, #[Pure] static function ($a, $b) {` keeps its attribute inline. Elsewhere, such as in an
 * assignment or an array, PER says nothing. Set checkClosureAttributesOutsideCalls to require the attribute inline
 * with a single space there too, as the Jumbo standard does.
 *
 * It also reports a blank line or a comment between the last attribute and its target. That replaces
 * SlevomatCodingStandard.Attributes.AttributeAndTargetSpacing, whose fix deletes a comment there, and whose only option
 * for an attribute on its target's line, allowOnSameLine, is all or nothing.
 *
 * PER Coding Style 3.1, section 8, sets the layout for attributes on an anonymous class: they start on the line after
 * `new`, indented once, with `class` on the line after them, at the same indent. The fix puts each attribute block on
 * its own line.
 *
 * Section 12.2 also says there MUST NOT be a blank line between a docblock and the attributes after it, or between two
 * attribute blocks, and that an attribute inline with its parameter is separated from it by a single space. Both are
 * checked here too.
 */
class AttributePlacementSniff implements Sniff
{
    public const string CODE_SAME_LINE = 'SameLine';

    public const string CODE_ANON_CLASS = 'AnonClass';

    public const string CODE_BLANK_LINE_BEFORE = 'BlankLineBefore';

    public const string CODE_BLANK_LINE_AFTER = 'BlankLineAfter';

    public const string CODE_COMMENT_BEFORE_TARGET = 'CommentBeforeTarget';

    public const string CODE_PARAMETER_SPACING = 'ParameterSpacing';

    public const string CODE_CLOSURE_SPACING = 'ClosureSpacing';

    public const string CODE_CLOSURE_INDENT = 'ClosureIndent';

    /**
     * The tokens that own a parameter list.
     *
     * @var list<int|string>
     */
    private const array FUNCTION_TOKENS = [T_FUNCTION, T_CLOSURE, T_FN];

    /**
     * Whether to check a closure's attribute anywhere other than a call's argument list, such as in an assignment or
     * an array, as inline with a single space. PER says nothing about it, so it's off by default.
     */
    public bool $checkClosureAttributesOutsideCalls = false;

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

        $this->checkBlankLineBefore($phpcsFile, $stackPtr);
        $closerPtr = $tokens[$stackPtr]['attribute_closer'];
        $targetPtr = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closerPtr + 1), null, true);

        if ($targetPtr === false) {
            return;
        }

        $this->checkBeforeTarget($phpcsFile, $stackPtr, $closerPtr, $targetPtr);

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

        if ($this->isClosure($phpcsFile, $targetPtr)) {
            $this->processClosure($phpcsFile, $stackPtr, $closerPtr, $targetPtr);
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
                $this->checkParameterSpacing($phpcsFile, $closerPtr, $targetPtr);
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
     * Check that nothing comes between the last attribute block and its target on a later line: no blank line, as
     * section 12.2 says, and no comment, as the attributes go immediately prior to the structure.
     *
     * A docblock there is reported by SlevomatCodingStandard.Attributes.RequireAttributeAfterDocComment, and a blank
     * line before a parameter by Jumbo.Functions.MultiLineFunctionDeclaration, so both are left alone.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the attribute block's opener.
     * @param int $closerPtr The position of the attribute block's closer.
     * @param int $targetPtr The position of the first token after the attributes.
     */
    private function checkBeforeTarget(File $phpcsFile, int $stackPtr, int $closerPtr, int $targetPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        if (
            $tokens[$targetPtr]['code'] === T_ATTRIBUTE
            || $tokens[$targetPtr]['line'] === $tokens[$closerPtr]['line']
            || $this->findParameterListOpener($phpcsFile, $stackPtr) !== null
            || $phpcsFile->findNext(T_DOC_COMMENT_OPEN_TAG, ($closerPtr + 1), $targetPtr) !== false
        ) {
            return;
        }

        // A comment at the end of the attribute's line, e.g. `#[Override] // Stringable`, is fine.
        for ($i = ($closerPtr + 1); $i < $targetPtr; $i++) {
            $isComment = isset(Tokens::COMMENT_TOKENS[$tokens[$i]['code']]);
            if ($isComment && $tokens[$i]['line'] > $tokens[$closerPtr]['line']) {
                $phpcsFile->addError(
                    'There must be no comment between an attribute and its target',
                    $closerPtr,
                    self::CODE_COMMENT_BEFORE_TARGET,
                );
                return;
            }
        }

        if (($tokens[$targetPtr]['line'] - $tokens[$closerPtr]['line']) < 2) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'There must be no blank line between an attribute and its target',
            $closerPtr,
            self::CODE_BLANK_LINE_AFTER,
        );
        if (!$fix) {
            return;
        }

        // Remove only the blank lines, keeping any comment at the end of the attribute's line.
        $phpcsFile->fixer->beginChangeset();
        for ($i = ($closerPtr + 1); $i < $targetPtr; $i++) {
            $line = $tokens[$i]['line'];
            $isBlankLine = $tokens[$i]['code'] === T_WHITESPACE && $tokens[$i]['column'] === 1
                && $line > $tokens[$closerPtr]['line'] && $line < $tokens[$targetPtr]['line'];
            if ($isBlankLine) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check that there's no blank line between an attribute block and a docblock or attribute block before it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the attribute block's opener.
     */
    private function checkBlankLineBefore(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if (
            $previous === false
            || !in_array($tokens[$previous]['code'], [T_DOC_COMMENT_CLOSE_TAG, T_ATTRIBUTE_END], true)
            || ($tokens[$stackPtr]['line'] - $tokens[$previous]['line']) < 2
        ) {
            return;
        }

        $error = $tokens[$previous]['code'] === T_DOC_COMMENT_CLOSE_TAG
            ? 'There must be no blank line between a docblock and the attributes after it'
            : 'There must be no blank line between two attribute blocks';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, self::CODE_BLANK_LINE_BEFORE);
        if (!$fix) {
            return;
        }

        $indent = str_repeat(' ', ($tokens[$stackPtr]['column'] - 1));
        $phpcsFile->fixer->beginChangeset();
        for ($i = ($previous + 1); $i < $stackPtr; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContent($previous, $phpcsFile->eolChar . $indent);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check that an attribute inline with its parameter is separated from it by a single space.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $closerPtr The position of the attribute block's closer.
     * @param int $targetPtr The position of the first token of the parameter.
     */
    private function checkParameterSpacing(File $phpcsFile, int $closerPtr, int $targetPtr): void
    {
        $spacing = $phpcsFile->getTokensAsString(($closerPtr + 1), ($targetPtr - $closerPtr - 1));
        $hasComment = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($closerPtr + 1), $targetPtr) !== false;
        if ($spacing === ' ' || $hasComment) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Expected 1 space between an attribute and its parameter',
            $closerPtr,
            self::CODE_PARAMETER_SPACING,
        );
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($closerPtr + 1); $i < $targetPtr; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContent($closerPtr, ' ');
        $phpcsFile->fixer->endChangeset();
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
     * Check a closure's attribute, as for a parameter when the closure is a call's argument.
     *
     * In a call's argument list that isn't split, the attribute is inline with the closure, after a single space. A
     * closure split over lines doesn't split the list, as section 4.7 says. In a split list, the attribute is on its
     * own line before the closure, which is indented the same. Anywhere else, PER says nothing, so the attribute is
     * only checked, as inline with a single space, if checkClosureAttributesOutsideCalls is set.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the last attribute block's opener.
     * @param int $closerPtr The position of the last attribute block's closer.
     * @param int $targetPtr The position of the closure's first token.
     */
    private function processClosure(File $phpcsFile, int $stackPtr, int $closerPtr, int $targetPtr): void
    {
        $firstPtr = $this->findFirstAttribute($phpcsFile, $stackPtr);
        $arguments = $this->findCallArguments($phpcsFile, $firstPtr);
        if ($arguments === null) {
            if ($this->checkClosureAttributesOutsideCalls) {
                $this->checkInlineSpacing($phpcsFile, $closerPtr, $targetPtr, 'Expected 1 space between an attribute '
                    . 'and its closure', self::CODE_CLOSURE_SPACING);
            }

            return;
        }

        if (!$this->isArgumentListSplit($phpcsFile, $arguments)) {
            $this->checkInlineSpacing($phpcsFile, $closerPtr, $targetPtr, 'In a single-line argument list, a '
                . 'closure\'s attribute must be inline, after a single space', self::CODE_CLOSURE_SPACING);
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
        $startsLine = $firstOnLine === $stackPtr;
        $indent = $tokens[$stackPtr]['column'] - 1;
        $hasComment = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($closerPtr + 1), $targetPtr) !== false;

        if ($tokens[$targetPtr]['line'] === $tokens[$closerPtr]['line']) {
            $error = 'In a multi-line argument list, a closure\'s attribute must be on the line before it.';
            if (!$startsLine || $hasComment) {
                $phpcsFile->addError($error, $stackPtr, self::CODE_SAME_LINE);
                return;
            }

            if ($phpcsFile->addFixableError($error, $stackPtr, self::CODE_SAME_LINE)) {
                $this->setSpacing($phpcsFile, $closerPtr, $targetPtr, $phpcsFile->eolChar . str_repeat(' ', $indent));
            }

            return;
        }

        // The attribute is indented the same as its closure, the argument, as section 12.2 says of a parameter.
        $expected = $tokens[$targetPtr]['column'] - 1;
        if (!$startsLine || $indent === $expected) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'In a multi-line argument list, a closure\'s attribute must be indented the same as the closure; '
                . 'expected %s spaces, found %s',
            $stackPtr,
            self::CODE_CLOSURE_INDENT,
            [$expected, $indent],
        );
        if (!$fix) {
            return;
        }

        if ($indent === 0) {
            $phpcsFile->fixer->addContentBefore($stackPtr, str_repeat(' ', $expected));
        } else {
            $phpcsFile->fixer->replaceToken(($stackPtr - 1), str_repeat(' ', $expected));
        }
    }

    /**
     * Check that an attribute is on its target's line, after a single space, and fix it to that.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $closerPtr The position of the last attribute block's closer.
     * @param int $targetPtr The position of the target's first token.
     * @param string $error The error message.
     * @param string $code The error code.
     */
    private function checkInlineSpacing(
        File $phpcsFile,
        int $closerPtr,
        int $targetPtr,
        string $error,
        string $code,
    ): void {
        $spacing = $phpcsFile->getTokensAsString(($closerPtr + 1), ($targetPtr - $closerPtr - 1));
        $hasComment = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($closerPtr + 1), $targetPtr) !== false;
        if ($spacing === ' ' || $hasComment) {
            return;
        }

        if ($phpcsFile->addFixableError($error, $closerPtr, $code)) {
            $this->setSpacing($phpcsFile, $closerPtr, $targetPtr, ' ');
        }
    }

    /**
     * Replace everything between two tokens with the given spacing.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $from The position of the token before the spacing.
     * @param int $to The position of the token after the spacing.
     * @param string $spacing The new spacing.
     */
    private function setSpacing(File $phpcsFile, int $from, int $to, string $spacing): void
    {
        $phpcsFile->fixer->beginChangeset();
        for ($i = ($from + 1); $i < $to; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContent($from, $spacing);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Find the first of a run of attribute blocks, from the last one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $attributePtr The position of an attribute block's opener.
     * @return int The position of the first attribute block's opener.
     */
    private function findFirstAttribute(File $phpcsFile, int $attributePtr): int
    {
        $tokens = $phpcsFile->getTokens();
        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($attributePtr - 1), null, true);
        while ($previous !== false && $tokens[$previous]['code'] === T_ATTRIBUTE_END) {
            $attributePtr = $tokens[$previous]['attribute_opener'];
            $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($attributePtr - 1), null, true);
        }

        return $attributePtr;
    }

    /**
     * Find the arguments of the call an attributed closure is a direct argument of, if any.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $attributePtr The position of the closure's first attribute block's opener.
     * @return array<int|string, array<string, int|string>>|null The call's arguments, as PHPCSUtils'
     *     PassedParameters::getParameters() gives them, or null if the closure isn't one of them.
     */
    private function findCallArguments(File $phpcsFile, int $attributePtr): ?array
    {
        $opener = Parentheses::getLastOpener($phpcsFile, $attributePtr);
        if ($opener === false) {
            return null;
        }

        $tokens = $phpcsFile->getTokens();
        $namePtr = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($opener - 1), null, true);
        if ($namePtr === false || !isset(Collections::functionCallTokens()[$tokens[$namePtr]['code']])) {
            return null;
        }

        // A named argument's closure follows `name:`, so it can't start its own line; it's treated as being elsewhere.
        $arguments = PassedParameters::getParameters($phpcsFile, $namePtr);
        foreach ($arguments as $argument) {
            $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, (int) $argument['start'], null, true);
            if ($first === $attributePtr) {
                return isset($argument['name_token']) ? null : $arguments;
            }
        }

        return null;
    }

    /**
     * Check whether a call's argument list is split across lines, as section 4.7 means it: an argument starts a new
     * line. An argument split over lines, such as a closure, doesn't split the list.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array<int|string, array<string, int|string>> $arguments The call's arguments, from PHPCSUtils.
     * @return bool True if the list is split.
     */
    private function isArgumentListSplit(File $phpcsFile, array $arguments): bool
    {
        $tokens = $phpcsFile->getTokens();
        foreach ($arguments as $argument) {
            // A named argument starts at its name.
            $start = (int) ($argument['name_token'] ?? $argument['start']);
            $first = $phpcsFile->findNext(T_WHITESPACE, $start, null, true);
            $before = $first === false ? false : $phpcsFile->findPrevious(T_WHITESPACE, ($first - 1), null, true);
            if ($first !== false && $before !== false && $tokens[$first]['line'] > $tokens[$before]['line']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether an attribute's target is a closure or arrow function, which section 12.2 doesn't list.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $targetPtr The position of the first token after the attributes.
     * @return bool True for `function`, `fn`, or `static` before either.
     */
    private function isClosure(File $phpcsFile, int $targetPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$targetPtr]['code'] === T_STATIC) {
            $targetPtr = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($targetPtr + 1), null, true);
            if ($targetPtr === false) {
                return false;
            }
        }

        return $tokens[$targetPtr]['code'] === T_CLOSURE || $tokens[$targetPtr]['code'] === T_FN;
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
