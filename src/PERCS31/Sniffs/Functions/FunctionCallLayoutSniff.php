<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Functions;

use PERCS31\Helpers\PropertyHookHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Methods\FunctionCallSignatureSniff as Psr2Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks the spacing and layout of a function call's argument list, including `clone()`'s, but not a `set` hook's
 * parameter list.
 *
 * PER Coding Style 3.1, section 4.7, sets the rules for a call: no space before or just inside the parentheses, and
 * when split, the first argument on the next line and one per line. This extends PSR2.Methods.FunctionCallSignature,
 * and changes two things:
 * - It checks `clone()`, which section 4.7 calls a function, but the parent doesn't register. Parentheses that may
 *   only group the object, as in `clone ($a)->b`, are skipped, and so are parentheses after a space, as in
 *   `clone ($a)`, where `clone` may be the operator. PERCS31.PHP.RequireCloneParentheses reports that space.
 * - It skips a `set` hook's parameter list, as in `set (string $value)`, which PHP_CodeSniffer reads as a call to
 *   `set()`. It's part of the hook's declaration, not a call, so PERCS31.Classes.PropertyHookLayout checks it, with
 *   checkHookParameterSpacing set, as in the PERCS31 ruleset.
 *
 * It's named Layout, not Signature like its parent, as a function's signature is its declaration, not a call.
 */
class FunctionCallLayoutSniff extends Psr2Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [...parent::register(), T_CLONE];
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
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] === T_CLONE) {
            $parentheses = TokenHelper::findCloneArguments($phpcsFile, $stackPtr);
            if ($parentheses === null || $parentheses[0] !== ($stackPtr + 1)) {
                return;
            }
        } else {
            $opener = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
            if ($opener !== false && PropertyHookHelper::isHookParameterList($phpcsFile, $opener)) {
                return;
            }
        }

        parent::process($phpcsFile, $stackPtr);
    }
}
