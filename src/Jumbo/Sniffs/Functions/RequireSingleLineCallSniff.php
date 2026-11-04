<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Sniffs\Functions\RequireSingleLineCallSniff as SlevomatSniff;

/**
 * Requires a call that fits on one line to be written on one line, except with an anonymous class argument.
 *
 * With ignoreWithComplexParameter, SlevomatCodingStandard.Functions.RequireSingleLineCall skips a call with a closure,
 * arrow function or array argument, but not one with an anonymous class. So it reports `consume(new class {` ...
 * `});`, and its fix puts the whole class body on one line, which then crashes another Slevomat sniff. This sniff
 * skips a call with an anonymous class argument too, and leaves everything else to the parent.
 */
class RequireSingleLineCallSniff extends SlevomatSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stringPointer The position of the call's name.
     */
    public function process(File $phpcsFile, int $stringPointer): void
    {
        if ($this->ignoreWithComplexParameter && $this->hasAnonClassArgument($phpcsFile, $stringPointer)) {
            return;
        }

        parent::process($phpcsFile, $stringPointer);
    }

    /**
     * Check whether a call's arguments contain an anonymous class.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stringPointer The position of the call's name.
     * @return bool True if an anonymous class is between the call's parentheses.
     */
    private function hasAnonClassArgument(File $phpcsFile, int $stringPointer): bool
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stringPointer + 1), null, true);
        if ($opener === false || $tokens[$opener]['code'] !== T_OPEN_PARENTHESIS) {
            return false;
        }

        $closer = $tokens[$opener]['parenthesis_closer'] ?? null;

        return is_int($closer) && $phpcsFile->findNext(T_ANON_CLASS, ($opener + 1), $closer) !== false;
    }
}
