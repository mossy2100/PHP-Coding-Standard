<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Functions;

use PERCS31\Helpers\LayoutHelper;
use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\Functions\DisallowTrailingCommaInCallSniff as SlevomatSniff;

/**
 * Disallows a trailing comma after the last argument of a call, unless the argument list is split across lines.
 *
 * PER Coding Style 3.1, section 2.6, says the last item of a list MUST NOT have a trailing comma unless the list is
 * split across lines. Section 4.7 says a single argument split across lines, such as an array or a closure, doesn't
 * split the argument list. So in `foo([` ... `],);` the list isn't split, and the comma isn't allowed.
 *
 * This extends SlevomatCodingStandard.Functions.DisallowTrailingCommaInCall, and changes one thing: which lists are
 * checked. The parent checks every call, or with its onlySingleLine property, only one whose parentheses are on the
 * same line, which misses `],);`. This sniff skips a split list, where the first argument starts a line or the closing
 * parenthesis does, and leaves the rest to the parent, with onlySingleLine off.
 */
class DisallowTrailingCommaInCallSniff extends SlevomatSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $parenthesisOpenerPointer The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $parenthesisOpenerPointer): void
    {
        $closer = $phpcsFile->getTokens()[$parenthesisOpenerPointer]['parenthesis_closer'] ?? null;
        if (!is_int($closer) || LayoutHelper::isSplitList($phpcsFile, $parenthesisOpenerPointer, $closer)) {
            return;
        }

        parent::process($phpcsFile, $parenthesisOpenerPointer);
    }
}
