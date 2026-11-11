<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\PHP;

use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\LowerCaseKeywordSniff as GenericSniff;

/**
 * Requires PHP's keywords in lower case, including a property hook's name.
 *
 * PER Coding Style 3.1, section 2.5, says all PHP reserved keywords MUST be in lower case. This extends
 * Generic.PHP.LowerCaseKeyword, and changes one thing: it also checks a hook's name, as in `GET => 1;`.
 *
 * `get` and `set` aren't on PHP's list of reserved keywords, as they're only keywords in a hook list, and
 * PHP_CodeSniffer reads them as plain names, so the parent doesn't see them. Section 4.10 calls one "the hook keyword",
 * and every example writes it in lower case, so it's read as a keyword here.
 */
class LowerCaseKeywordSniff extends GenericSniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [...parent::register(), T_STRING];
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
        $isName = $phpcsFile->getTokens()[$stackPtr]['code'] === T_STRING;
        if (!$isName || PropertyHookHelper::isHookName($phpcsFile, $stackPtr)) {
            parent::process($phpcsFile, $stackPtr);
        }
    }
}
