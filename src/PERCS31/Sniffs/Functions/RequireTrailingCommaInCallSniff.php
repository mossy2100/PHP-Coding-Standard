<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Functions;

use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\Functions\RequireTrailingCommaInCallSniff as SlevomatSniff;

/**
 * Requires a trailing comma after the last argument of a call split across lines, but not after the `...` of a
 * first-class callable.
 *
 * PER Coding Style 3.1, section 2.6, says the last item of a list split across lines MUST have a trailing comma. This
 * extends SlevomatCodingStandard.Functions.RequireTrailingCommaInCall, and changes one thing: it skips a first-class
 * callable, as in `foo(...)`. The parent reads the `...` as the last argument, so when one is split over lines, its fix
 * adds a comma after it, giving `...,`, which is a parse error. The parent has no property to turn that off, so this
 * needs a subclass. PERCS31.Functions.FunctionCallLayout reports the split, and fixes it to `foo(...)`.
 */
class RequireTrailingCommaInCallSniff extends SlevomatSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $parenthesisOpenerPointer The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $parenthesisOpenerPointer): void
    {
        if (TokenHelper::findCallableEllipsis($phpcsFile, $parenthesisOpenerPointer) !== null) {
            return;
        }

        parent::process($phpcsFile, $parenthesisOpenerPointer);
    }
}
