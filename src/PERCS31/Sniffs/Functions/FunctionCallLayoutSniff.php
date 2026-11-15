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
 * and changes four things:
 * - It checks `clone()`, which section 4.7 calls a function, but the parent doesn't register. Parentheses that may
 *   only group the object, as in `clone ($a)->b`, are skipped. A space before the parentheses, as in `clone ($a)`, is
 *   reported as SpaceBeforeOpenBracket, as for any other call.
 * - It doesn't fix a space before a call's parentheses that has a comment in it, which the parent's fix deletes. It's
 *   reported with no fix, and the call gets no other check from the parent until the comment is moved.
 * - It skips a `set` hook's parameter list, as in `set (string $value)`, which PHP_CodeSniffer reads as a call to
 *   `set()`. It's part of the hook's declaration, not a call, so PERCS31.Classes.PropertyHooks checks it.
 * - It skips a first-class callable split over lines, as in `foo(` then `...` then `)`, which
 *   Universal.WhiteSpace.FirstClassCallableSpacing reports, as section 4.8 says the `...` MUST NOT have whitespace
 *   before or after it. The parent reads the `...` as an argument on a line of its own, and its fixes fight that
 *   sniff's when a comment comes before the `...`. On one line, the parent reports `foo( ... )` too, and the two
 *   fixes agree.
 * - It skips `require`, `include` and their `_once` forms, which the parent registers as function names. They're
 *   language constructs, and parentheses after one only group its expression, as in
 *   `include ($a ? 'x' : 'y') . '.php';`. PER has no rule for them.
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

        // `require` and `include` are language constructs, so parentheses after one only group its expression.
        if (isset(Tokens::INCLUDE_TOKENS[$tokens[$stackPtr]['code']])) {
            return;
        }

        if ($tokens[$stackPtr]['code'] === T_CLONE) {
            $parentheses = TokenHelper::findCloneArguments($phpcsFile, $stackPtr);
            if ($parentheses === null) {
                return;
            }
        } else {
            $opener = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
            if ($opener !== false && PropertyHookHelper::isHookParameterList($phpcsFile, $opener)) {
                return;
            }

            if ($opener !== false && $this->isSplitCallable($phpcsFile, $opener)) {
                return;
            }
        }

        $opener = $tokens[$stackPtr]['code'] === T_CLONE ? $parentheses[0] : $opener;
        if ($opener !== false && $this->reportCommentBeforeParenthesis($phpcsFile, $stackPtr, $opener)) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * Report a comment between a function's name and its opening parenthesis, as in `foo` then a block comment then
     * `($a)`, with no fix. The parent's fix for the space there removes everything before the parenthesis, comment and
     * all.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the function's name.
     * @param int $opener The position of the token after the name, which may be the opening parenthesis.
     * @return bool True if it was reported, so the parent, which would report it again with its fix, isn't run.
     */
    private function reportCommentBeforeParenthesis(File $phpcsFile, int $stackPtr, int $opener): bool
    {
        $tokens = $phpcsFile->getTokens();
        if (
            $tokens[$stackPtr]['code'] === T_ANON_CLASS
            || $tokens[$opener]['code'] !== T_OPEN_PARENTHESIS
            || $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($stackPtr + 1), $opener) === false
        ) {
            return false;
        }

        // The name of a function's declaration isn't a call.
        $previous = $phpcsFile->findPrevious([...Tokens::EMPTY_TOKENS, T_BITWISE_AND], ($stackPtr - 1), null, true);
        if ($previous !== false && $tokens[$previous]['code'] === T_FUNCTION) {
            return false;
        }

        $error = 'Space before opening parenthesis of function call prohibited';
        $phpcsFile->addError($error, $stackPtr, 'SpaceBeforeOpenBracket');

        return true;
    }

    /**
     * Check whether parentheses hold a first-class callable that isn't on one line, as in `foo(` then `...` then `)`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the token after the function's name.
     * @return bool True if it is, so the parent's checks for a split argument list are skipped.
     */
    private function isSplitCallable(File $phpcsFile, int $opener): bool
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$opener]['parenthesis_closer'] ?? null;

        return is_int($closer)
            && $tokens[$opener]['line'] !== $tokens[$closer]['line']
            && TokenHelper::findCallableEllipsis($phpcsFile, $opener) !== null;
    }
}
