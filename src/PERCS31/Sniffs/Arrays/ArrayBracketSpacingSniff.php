<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PhpCollective\Sniffs\Arrays\ArrayBracketSpacingSniff as PhpCollectiveSniff;
use PHPCSUtils\Utils\Lists;

/**
 * Disallows a blank line before the closing bracket of a multi-line array.
 *
 * PER Coding Style 3.1, section 11, says the closing bracket MUST be on the next line after the last value.
 * PhpCollective.Arrays.ArrayBracketSpacing checks that. This subclass only skips a destructuring assignment, which
 * isn't an array declaration, so section 2.3 allows the blank line.
 */
class ArrayBracketSpacingSniff extends PhpCollectiveSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    // phpcs:ignore Jumbo.TypeHints.ParameterTypeHint.MissingNativeTypeHint -- The parent has no type, so int is a fatal.
    public function process(File $phpcsFile, $stackPtr): void
    {
        if (Lists::isShortList($phpcsFile, $stackPtr)) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }
}
