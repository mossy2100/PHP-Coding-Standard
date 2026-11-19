<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Functions;

use PERCS31\Helpers\LayoutHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\TokenHelper as SlevomatTokenHelper;
use SlevomatCodingStandard\Sniffs\Functions\DisallowTrailingCommaInCallSniff as SlevomatSniff;

/**
 * Disallows a trailing comma after the last argument of a call, unless the argument list is split across lines.
 *
 * PER Coding Style 3.1, section 2.6, says the last item of a list MUST NOT have a trailing comma unless the list is
 * split across lines. Section 4.7 says a single argument split across lines, such as an array or a closure, doesn't
 * split the argument list. So in `foo([` ... `],);` the list isn't split, and the comma isn't allowed.
 *
 * This extends SlevomatCodingStandard.Functions.DisallowTrailingCommaInCall, and changes two things:
 * - Which lists are checked. The parent checks every call, or with its onlySingleLine property, only one whose
 *   parentheses are on the same line, which misses `],);`. This sniff skips a split list, where the first argument
 *   starts a line or the closing parenthesis does, and leaves the rest to the parent, with onlySingleLine off.
 * - The fix when a comment follows the comma, as in `foo($a,` then a block comment then `);`. The parent's fix removes
 *   everything from the comma to the closing parenthesis, comment and all. This sniff reports that comma itself, and
 *   removes only the comma.
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

        $tokens = $phpcsFile->getTokens();
        $comma = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($closer - 1), null, true);
        if (
            $comma === false || $tokens[$comma]['code'] !== T_COMMA
            || $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($comma + 1), $closer) === false
        ) {
            parent::process($phpcsFile, $parenthesisOpenerPointer);
            return;
        }

        if (!$this->isCall($phpcsFile, $parenthesisOpenerPointer)) {
            return;
        }

        $error = 'Trailing comma after the last parameter in function call is disallowed.';
        if ($phpcsFile->addFixableError($error, $comma, self::CODE_DISALLOWED_TRAILING_COMMA)) {
            $phpcsFile->fixer->replaceToken($comma, '');
        }
    }

    /**
     * Check whether parentheses are a call's, as the parent decides it: after a name, a variable, `isset`, `unset`, a
     * closing parenthesis or a class keyword, and not in a function's declaration.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the opening parenthesis.
     * @return bool True if the parent would check them.
     */
    private function isCall(File $phpcsFile, int $opener): bool
    {
        $tokens = $phpcsFile->getTokens();
        $callCodes = [
            ...SlevomatTokenHelper::NAME_TOKEN_CODES,
            T_STRING,
            T_VARIABLE,
            T_ISSET,
            T_UNSET,
            T_CLOSE_PARENTHESIS,
            ...SlevomatTokenHelper::CLASS_KEYWORD_CODES,
        ];
        $before = SlevomatTokenHelper::findPreviousEffective($phpcsFile, ($opener - 1));
        if ($before === null || !in_array($tokens[$before]['code'], $callCodes, true)) {
            return false;
        }

        $function = SlevomatTokenHelper::findPreviousEffective($phpcsFile, ($before - 1));

        return $function === null
            || !in_array($tokens[$function]['code'], SlevomatTokenHelper::FUNCTION_TOKEN_CODES, true);
    }
}
