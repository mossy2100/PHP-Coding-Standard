<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Arrays;

use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PhpCollective\Sniffs\Arrays\ArrayBracketSpacingSniff as PhpCollectiveSniff;
use PHPCSUtils\Utils\Arrays;
use PHPCSUtils\Utils\Lists;

/**
 * Disallows a blank line after the opening bracket of a multi-line array, and before its closing bracket.
 *
 * PER Coding Style 3.1, section 11, says the first value MUST be on the next line, and the closing bracket MUST be on
 * the next line after the last value. PhpCollective.Arrays.ArrayBracketSpacing checks the closing bracket. This
 * subclass changes two things:
 * - It also reports a blank line after the opening bracket, as BlankLineAfterOpener, which the parent has no check
 *   for.
 * - It skips a destructuring assignment, which isn't an array declaration, so section 2.3 allows the blank line.
 */
class ArrayBracketSpacingSniff extends PhpCollectiveSniff
{
    public const string CODE_BLANK_LINE_AFTER_OPENER = 'BlankLineAfterOpener';

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

        $this->checkAfterOpener($phpcsFile, $stackPtr);
        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * Report a blank line between a multi-line array's opening bracket and what follows it, and fix it by removing it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the array keyword or the opening bracket.
     */
    private function checkAfterOpener(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $bounds = Arrays::getOpenClose($phpcsFile, $stackPtr);
        if ($bounds === false) {
            return;
        }

        // A comment on the bracket's line, or on the line after it, is the first thing in the array.
        $opener = $bounds['opener'];
        $first = $phpcsFile->findNext(T_WHITESPACE, ($opener + 1), $bounds['closer'], true);
        if ($first === false || ($tokens[$first]['line'] - $tokens[$opener]['line']) < 2) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'The first value of a multi-line array must be on the line after the opening bracket',
            $opener,
            self::CODE_BLANK_LINE_AFTER_OPENER,
        );
        if ($fix) {
            $indent = str_repeat(' ', ($tokens[$first]['column'] - 1));
            FixerHelper::replaceBetween($phpcsFile, $opener, $first, $phpcsFile->eolChar . $indent);
        }
    }
}
