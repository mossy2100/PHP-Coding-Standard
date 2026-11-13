<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Functions;

use PERCS31\Helpers\FixerHelper;
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
 * and changes four things:
 * - It checks `clone()`, which section 4.7 calls a function, but the parent doesn't register. Parentheses that may
 *   only group the object, as in `clone ($a)->b`, are skipped, and so are parentheses after a space, as in
 *   `clone ($a)`, where `clone` may be the operator. PERCS31.PHP.RequireCloneParentheses reports that space.
 * - It skips a `set` hook's parameter list, as in `set (string $value)`, which PHP_CodeSniffer reads as a call to
 *   `set()`. It's part of the hook's declaration, not a call, so PERCS31.Classes.PropertyHookLayout checks it.
 * - It reports a first-class callable split over lines, as in `foo(` then `...` then `)`. Section 4.8 says the `...`
 *   MUST NOT have whitespace before or after it. The parent reads the `...` as an argument on a line of its own, which
 *   is how a split argument list is written, so it says nothing. On one line, the parent reports `foo( ... )`.
 * - It skips `require`, `include` and their `_once` forms, which the parent registers as function names. They're
 *   language constructs, and parentheses after one only group its expression, as in
 *   `include ($a ? 'x' : 'y') . '.php';`. PER has no rule for them.
 *
 * It's named Layout, not Signature like its parent, as a function's signature is its declaration, not a call.
 */
class FunctionCallLayoutSniff extends Psr2Sniff
{
    public const string CODE_SPLIT_FIRST_CLASS_CALLABLE = 'SplitFirstClassCallable';

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

        // `require` and `include` are language constructs, so parentheses after one only group its expression.
        if (isset(Tokens::INCLUDE_TOKENS[$tokens[$stackPtr]['code']])) {
            return;
        }

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

            if ($opener !== false && $this->checkSplitCallable($phpcsFile, $opener)) {
                return;
            }
        }

        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * Report a first-class callable whose `...` isn't on the line of both its parentheses, and fix it to `(...)`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the token after the function's name.
     * @return bool True if it was reported, so the parent's checks for a split argument list are skipped.
     */
    private function checkSplitCallable(File $phpcsFile, int $opener): bool
    {
        $tokens = $phpcsFile->getTokens();
        $ellipsis = TokenHelper::findCallableEllipsis($phpcsFile, $opener);
        if ($ellipsis === null) {
            return false;
        }

        $closer = $tokens[$opener]['parenthesis_closer'];
        assert(is_int($closer));
        if ($tokens[$opener]['line'] === $tokens[$closer]['line']) {
            return false;
        }

        $error = 'A first-class callable must be written as (...), with no whitespace before or after the ...';

        // A comment in the parentheses would be lost.
        if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($opener + 1), $closer) !== false) {
            $phpcsFile->addError($error, $ellipsis, self::CODE_SPLIT_FIRST_CLASS_CALLABLE);

            return true;
        }

        if ($phpcsFile->addFixableError($error, $ellipsis, self::CODE_SPLIT_FIRST_CLASS_CALLABLE)) {
            $phpcsFile->fixer->beginChangeset();
            FixerHelper::blankBetween($phpcsFile, $opener, $ellipsis);
            FixerHelper::blankBetween($phpcsFile, $ellipsis, $closer);
            $phpcsFile->fixer->endChangeset();
        }

        return true;
    }
}
