<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Attributes;

use PERCS31\Helpers\AttributeHelper;
use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Sniffs\Attributes\DisallowMultipleAttributesPerLineSniff as SlevomatSniff;

/**
 * Requires separate attribute blocks on separate lines.
 *
 * PER Coding Style 3.1, section 12.2, says two separate attribute blocks in a multi-line context MUST be on separate
 * lines. This extends SlevomatCodingStandard.Attributes.DisallowMultipleAttributesPerLine, and changes three things:
 * - It skips blocks on a parameter or argument in a single-line list, which isn't a multi-line context. Section 12.2
 *   requires a parameter's attribute inline there. PERCS31.Attributes.AttributePlacement checks the single space after
 *   each block.
 * - It reports blocks on a property hook, which the parent misses, as PHP_CodeSniffer doesn't know about hooks.
 * - When code comes before the blocks on their line, as in `$f = #[A] #[B] fn() => 1;`, the fix indents the next block
 *   one level past that line, as a continuation of the statement. The parent's fix lines it up with the line.
 */
class DisallowMultipleAttributesPerLineSniff extends SlevomatSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $attributeOpenerPointer The position of the attribute block's opener.
     */
    public function process(File $phpcsFile, int $attributeOpenerPointer): void
    {
        if (AttributeHelper::findSingleLineListOpener($phpcsFile, $attributeOpenerPointer) !== null) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $closerPtr = $tokens[$attributeOpenerPointer]['attribute_closer'];
        $nextPtr = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closerPtr + 1), null, true);
        $isNextOnLine = $nextPtr !== false
            && $tokens[$nextPtr]['code'] === T_ATTRIBUTE
            && $tokens[$nextPtr]['line'] === $tokens[$closerPtr]['line'];
        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $attributeOpenerPointer, true);
        assert(is_int($firstOnLine));
        $isContinued = $tokens[$firstOnLine]['code'] !== T_ATTRIBUTE;
        $isHook = $isNextOnLine && PropertyHookHelper::isInsidePropertyHook($phpcsFile, $attributeOpenerPointer);
        if (!$isNextOnLine || (!$isContinued && !$isHook)) {
            parent::process($phpcsFile, $attributeOpenerPointer);
            return;
        }

        // A comment between the blocks would have to move too, so leave that to a person.
        $error = 'Multiple attributes per line are disallowed.';
        $code = self::CODE_DISALLOWED_MULTIPLE_ATTRIBUTES_PER_LINE;
        $isFixable = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($closerPtr + 1), $nextPtr) === false;
        if (!FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $nextPtr, $code)) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        FixerHelper::blankBetween($phpcsFile, $closerPtr, $nextPtr);

        $indent = str_repeat(' ', ($tokens[$firstOnLine]['column'] - 1 + ($isContinued ? LayoutHelper::INDENT : 0)));
        $phpcsFile->fixer->addContent($closerPtr, $phpcsFile->eolChar . $indent);
        $phpcsFile->fixer->endChangeset();
    }
}
