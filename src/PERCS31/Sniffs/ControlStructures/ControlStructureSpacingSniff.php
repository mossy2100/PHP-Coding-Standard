<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\ControlStructures\ControlStructureSpacingSniff as Psr12Sniff;

/**
 * Checks the layout of a control structure's parentheses, without PSR-12's rules for a multi-line `foreach` or
 * `match`.
 *
 * PER Coding Style 3.1 gives `if`, `switch`, `while`, `do while` and `for` a rule for an expression split inside the
 * parentheses, but section 5.5 gives `foreach` none, and section 5.2 gives `match` none. The parent applies it anyway,
 * wanting the first expression on the line after `(`, so it rejects an array in the header, and a call with one
 * argument split over lines, as in `match ($this->lookup([`. Moving an array down then breaks section 11, which
 * forbids `[` on a line of its own. So there was no way to split one.
 *
 * For a multi-line `foreach` or `match`, this sniff only checks what section 5 requires of every control structure: no
 * space after the `(` or before the `)`. Everything else is left to the parent.
 */
class ControlStructureSpacingSniff extends Psr12Sniff
{
    public const string CODE_SPACE_AFTER_OPEN = 'SpacingAfterOpenBrace';

    public const string CODE_SPACE_BEFORE_CLOSE = 'SpaceBeforeCloseBrace';

    /**
     * Processes a control structure.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the control structure's keyword.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$stackPtr]['parenthesis_opener'] ?? null;
        $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
        if (
            !in_array($tokens[$stackPtr]['code'], [T_FOREACH, T_MATCH], true)
            || !is_int($opener)
            || !is_int($closer)
            || $tokens[$opener]['line'] === $tokens[$closer]['line']
        ) {
            parent::process($phpcsFile, $stackPtr);
            return;
        }

        $next = $tokens[($opener + 2)]['code'] ?? null;
        if ($next !== T_COMMENT) {
            $this->checkSpace($phpcsFile, $stackPtr, ($opener + 1), 'after the opening', self::CODE_SPACE_AFTER_OPEN);
        }

        $this->checkSpace($phpcsFile, $stackPtr, ($closer - 1), 'before the closing', self::CODE_SPACE_BEFORE_CLOSE);
    }

    /**
     * Report a space next to a parenthesis. A line break, or the indent of a `)` on its own line, isn't one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the control structure's keyword.
     * @param int $space The position of the token next to the parenthesis.
     * @param string $where Where the space is, for the message.
     * @param string $code The error code.
     */
    private function checkSpace(File $phpcsFile, int $stackPtr, int $space, string $where, string $code): void
    {
        $tokens = $phpcsFile->getTokens();
        if (
            $tokens[$space]['code'] !== T_WHITESPACE
            || $tokens[$space]['column'] === 1
            || str_contains($tokens[$space]['content'], $phpcsFile->eolChar)
        ) {
            return;
        }

        $error = "There must be no space $where parenthesis of a %s";
        if ($phpcsFile->addFixableError($error, $space, $code, [strtolower($tokens[$stackPtr]['content'])])) {
            $phpcsFile->fixer->replaceToken($space, '');
        }
    }
}
