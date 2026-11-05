<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Classes;

use PERCS31\Helpers\EmptyBodyHelper;
use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\Classes\EmptyLinesAroundClassBracesSniff as SlevomatSniff;

/**
 * Checks the blank lines inside a class's braces, allowing an empty body as `{}`.
 *
 * SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces reports `class Foo {}`, as it expects a line break after
 * the opening brace and before the closing one. PER Coding Style 3.0, section 4, says an empty body should be written
 * that way. This sniff skips an empty body on one line, and leaves everything else to the parent.
 */
class EmptyLinesAroundClassBracesSniff extends SlevomatSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPointer The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPointer): void
    {
        if (EmptyBodyHelper::isOnOneLine($phpcsFile, $stackPointer)) {
            return;
        }

        parent::process($phpcsFile, $stackPointer);
    }
}
