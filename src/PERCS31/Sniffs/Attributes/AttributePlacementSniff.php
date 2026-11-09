<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Attributes;

use PERCS31\Helpers\AttributeHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\Parentheses;
use PHPCSUtils\Utils\PassedParameters;

/**
 * Requires an attribute to be on the line before its target, except on a parameter in a single-line parameter list.
 *
 * PER Coding Style 3.1, section 12.2, puts an attribute on its own line before a class, function, method, constant or
 * property, and before a parameter in a multi-line parameter list. In a single-line parameter list, the attribute must
 * be inline with its parameter.
 *
 * A closure or arrow function isn't a structure in that list, but as a call's argument, its attribute follows the
 * parameter rule: inline after a single space when the argument list is on one line, and on its own line before the
 * closure, indented the same, when the list is split. A closure split over lines doesn't split the list, as section
 * 4.7 says, so `usort($items, #[Pure] static function ($a, $b) {` keeps its attribute inline. A named argument's
 * closure keeps its attribute inline too, as `callback: #[Pure] function () {`, as section 4.7 requires a single space
 * between the colon and the value, and PHP doesn't allow the attribute before the name. Elsewhere, such as in an
 * assignment or an array, PER says nothing, so it isn't checked.
 *
 * An enum case isn't in section 12.2's list either, so its attribute may be on the case's line. Nor is a property hook,
 * which isn't a method, so its attribute may be on the hook's line. A hook's parameter follows the parameter rule.
 *
 * PER doesn't set the spacing where it allows an attribute on its target's line, or says nothing: an enum case, a hook,
 * or a closure outside a call. Set checkSameLineSpacing to require a single space there, as the Jumbo standard does.
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

    public const string CODE_SAME_LINE_SPACING = 'SameLineSpacing';

    public const string CODE_SINGLE_LINE_LIST = 'SingleLineList';

    /**
     * Whether to report a comment on a line of its own between an attribute and its parameter. Section 12.2 only says
     * "prior to the parameter", not "immediately prior", so it's off by default.
     */
    public bool $checkParameterComments = false;

    /**
     * Whether to require a single space between an attribute and its target on the same line, where PER doesn't set the
     * spacing: an enum case, a property hook, or a closure outside a call. It's off by default.
     */
    public bool $checkSameLineSpacing = false;

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

        $isGroup = $this->findFirstAttribute($phpcsFile, $stackPtr) !== $stackPtr;
        $listOpener = $isGroup ? AttributeHelper::findSingleLineListOpener($phpcsFile, $stackPtr) : null;
        if ($listOpener !== null) {
            $this->processSingleLineList($phpcsFile, $stackPtr, $listOpener);
            return;
        }

        if ($this->isClosure($phpcsFile, $targetPtr)) {
            $this->processClosure($phpcsFile, $stackPtr, $closerPtr, $targetPtr);
            return;
        }

        if ($tokens[$targetPtr]['line'] !== $tokens[$closerPtr]['line']) {
            return;
        }

        // An enum case or a hook can share a line with one attribute block, but not with two or more.
        $isInlineTarget = $tokens[$targetPtr]['code'] === T_ENUM_CASE || $this->isHook($phpcsFile, $targetPtr);
        if ($isInlineTarget && !$isGroup) {
            $this->checkSameLineSpacing($phpcsFile, $closerPtr, $targetPtr);
            return;
        }

        if ($isInlineTarget) {
            $this->reportTargetOnSameLine($phpcsFile, $stackPtr, $closerPtr, $targetPtr);
            return;
        }

        // A parameter in a single-line parameter list must have its attribute inline.
        $listOpener = AttributeHelper::findParameterListOpener($phpcsFile, $stackPtr);
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
     * Report two or more attribute blocks in a single-line parameter or argument list, as section 12.2 says separate
     * blocks MUST be on separate lines. The fix splits the list, with each item on its own line and a trailing comma,
     * and the blocks then go on lines of their own.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the last attribute block's opener.
     * @param int $opener The position of the list's opening parenthesis.
     */
    private function processSingleLineList(File $phpcsFile, int $stackPtr, int $opener): void
    {
        $fix = $phpcsFile->addFixableError(
            'Separate attribute blocks must be on separate lines, so the list they\'re in must be split',
            $stackPtr,
            self::CODE_SINGLE_LINE_LIST,
        );
        if (!$fix) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$opener]['parenthesis_closer'];
        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $opener, true);
        assert(is_int($firstOnLine));
        $indent = str_repeat(' ', ($tokens[$firstOnLine]['column'] - 1));
        $itemIndent = $phpcsFile->eolChar . $indent . '    ';

        // The commas between items, skipping anything nested in brackets, braces or attributes.
        $commas = [];
        for ($i = ($opener + 1); $i < $closer; $i++) {
            $i = TokenHelper::findCloser($tokens[$i]) ?? $i;
            if ($tokens[$i]['code'] === T_COMMA) {
                $commas[] = $i;
            }
        }

        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($closer - 1), $opener, true);
        $first = $phpcsFile->findNext(T_WHITESPACE, ($opener + 1), $closer, true);
        if ($last === false || $first === false) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $this->setSpacing($phpcsFile, $opener, $first, $itemIndent);
        foreach ($commas as $comma) {
            $next = $phpcsFile->findNext(T_WHITESPACE, ($comma + 1), $closer, true);
            if ($next !== false && $next !== $closer) {
                $this->setSpacing($phpcsFile, $comma, $next, $itemIndent);
            }
        }

        $comma = $tokens[$last]['code'] === T_COMMA ? '' : ',';
        $this->setSpacing($phpcsFile, $last, $closer, $comma . $phpcsFile->eolChar . $indent);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Report a target that shares a line with two or more attribute blocks, and fix it onto the next line. It's
     * indented as the line is, or one level more if code comes before the attributes, as in `$f = #[A]`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the last attribute block's opener.
     * @param int $closerPtr The position of the last attribute block's closer.
     * @param int $targetPtr The position of the target's first token.
     */
    private function reportTargetOnSameLine(File $phpcsFile, int $stackPtr, int $closerPtr, int $targetPtr): void
    {
        $error = 'With two or more attribute blocks, the target must be on the line after them.';
        if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($closerPtr + 1), $targetPtr) !== false) {
            $phpcsFile->addError($error, $stackPtr, self::CODE_SAME_LINE);
            return;
        }

        if ($phpcsFile->addFixableError($error, $stackPtr, self::CODE_SAME_LINE)) {
            $tokens = $phpcsFile->getTokens();
            $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
            assert(is_int($firstOnLine));
            $extra = $tokens[$firstOnLine]['code'] === T_ATTRIBUTE ? 0 : 4;
            $indent = str_repeat(' ', ($tokens[$firstOnLine]['column'] - 1 + $extra));
            $this->setSpacing($phpcsFile, $closerPtr, $targetPtr, $phpcsFile->eolChar . $indent);
        }
    }

    /**
     * Check whether an attribute's target is a property hook.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $targetPtr The position of the first token after the attributes.
     * @return bool True if it's a hook, with or without `final` or `&` before its name.
     */
    private function isHook(File $phpcsFile, int $targetPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $skip = Tokens::EMPTY_TOKENS + [
            T_FINAL => T_FINAL,
            T_BITWISE_AND => T_BITWISE_AND,
            T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG => T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG,
            T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG => T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG,
        ];
        $namePtr = $phpcsFile->findNext($skip, $targetPtr, null, true);
        if ($namePtr === false || $tokens[$namePtr]['code'] !== T_STRING) {
            return false;
        }

        return in_array(strtolower($tokens[$namePtr]['content']), ['get', 'set'], true)
            && PropertyHookHelper::isInsidePropertyHook($phpcsFile, $namePtr);
    }

    /**
     * Check that nothing comes between the last attribute block and its target on a later line: no blank line, as
     * section 12.2 says, and no comment, as the attributes go immediately prior to the structure.
     *
     * A docblock there is reported by SlevomatCodingStandard.Attributes.RequireAttributeAfterDocComment, and a blank
     * line before a parameter by PERCS31.Functions.MultiLineFunctionDeclaration, so both are left alone. A comment
     * before a parameter is only reported if checkParameterComments is set.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the attribute block's opener.
     * @param int $closerPtr The position of the attribute block's closer.
     * @param int $targetPtr The position of the first token after the attributes.
     */
    private function checkBeforeTarget(File $phpcsFile, int $stackPtr, int $closerPtr, int $targetPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $isParameter = AttributeHelper::findParameterListOpener($phpcsFile, $stackPtr) !== null;
        if (
            $tokens[$targetPtr]['code'] === T_ATTRIBUTE
            || $tokens[$targetPtr]['line'] === $tokens[$closerPtr]['line']
            || ($isParameter && !$this->checkParameterComments)
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

        if ($isParameter || ($tokens[$targetPtr]['line'] - $tokens[$closerPtr]['line']) < 2) {
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
     * own line before the closure, which is indented the same. Anywhere else, PER says nothing, so only the spacing is
     * checked, if checkSameLineSpacing is set.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the last attribute block's opener.
     * @param int $closerPtr The position of the last attribute block's closer.
     * @param int $targetPtr The position of the closure's first token.
     */
    private function processClosure(File $phpcsFile, int $stackPtr, int $closerPtr, int $targetPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $firstPtr = $this->findFirstAttribute($phpcsFile, $stackPtr);
        $arguments = $this->findCallArguments($phpcsFile, $firstPtr);
        if ($arguments === null && $firstPtr !== $stackPtr) {
            if ($tokens[$targetPtr]['line'] === $tokens[$closerPtr]['line']) {
                $this->reportTargetOnSameLine($phpcsFile, $stackPtr, $closerPtr, $targetPtr);
            }

            return;
        }

        if ($arguments === null) {
            $this->checkSameLineSpacing($phpcsFile, $closerPtr, $targetPtr);
            return;
        }

        if (!$this->isArgumentListSplit($phpcsFile, $arguments)) {
            $this->checkInlineSpacing($phpcsFile, $closerPtr, $targetPtr, 'In a single-line argument list, a '
                . 'closure\'s attribute must be inline, after a single space', self::CODE_CLOSURE_SPACING);
            return;
        }

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
     * If checkSameLineSpacing is set, check that an attribute on its target's line is followed by a single space.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $closerPtr The position of the last attribute block's closer.
     * @param int $targetPtr The position of the target's first token.
     */
    private function checkSameLineSpacing(File $phpcsFile, int $closerPtr, int $targetPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        if ($this->checkSameLineSpacing && $tokens[$targetPtr]['line'] === $tokens[$closerPtr]['line']) {
            $error = 'Expected 1 space between an attribute and its target on the same line';
            $this->checkInlineSpacing($phpcsFile, $closerPtr, $targetPtr, $error, self::CODE_SAME_LINE_SPACING);
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
}
