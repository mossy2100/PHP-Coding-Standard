<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Functions;

use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Methods\FunctionCallSignatureSniff as Psr2Sniff;

/**
 * Checks the argument list of `clone()` like a function call's.
 *
 * PER Coding Style 3.1, section 4.7, calls it "the `clone()` function", so its argument list follows the rules for a
 * call: no space just inside the parentheses, and when split, the first argument on the next line and one per line.
 * PSR2.Methods.FunctionCallSignature checks those rules for `exit()` and `die()`, but doesn't register `clone`. This
 * subclass registers only `clone`, and leaves the rest to the parent.
 *
 * It skips parentheses that may only group the object, as in `clone ($a)->b`, and parentheses after a space, as in
 * `clone ($a)`, where `clone` may be the operator. PERCS31.PHP.RequireCloneParentheses reports that space.
 */
class CloneCallSignatureSniff extends Psr2Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_CLONE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return void
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $parentheses = TokenHelper::findCloneArguments($phpcsFile, $stackPtr);
        if ($parentheses === null || $parentheses[0] !== ($stackPtr + 1)) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }
}
