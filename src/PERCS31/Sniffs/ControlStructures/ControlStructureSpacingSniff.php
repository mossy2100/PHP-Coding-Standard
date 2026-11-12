<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\ControlStructures\ControlStructureSpacingSniff as Psr12Sniff;
use PHP_CodeSniffer\Util\Tokens;

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
 *
 * It also requires one space between a `catch`'s type and its variable. Section 5.6 shows `catch (FirstThrowableType
 * $e)`, and says to "note the placement of parentheses, spaces, and braces". No inherited sniff checks that space, and
 * this sniff already visits every `catch`.
 */
class ControlStructureSpacingSniff extends Psr12Sniff
{
    public const string CODE_SPACE_AFTER_OPEN = 'SpacingAfterOpenBrace';

    public const string CODE_SPACE_BEFORE_CLOSE = 'SpaceBeforeCloseBrace';

    public const string CODE_SPACE_BEFORE_CATCH_VARIABLE = 'SpaceBeforeCatchVariable';

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
        if ($tokens[$stackPtr]['code'] === T_CATCH && is_int($opener) && is_int($closer)) {
            $this->checkCatchVariable($phpcsFile, $opener, $closer);
        }

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
     * Require one space between a `catch`'s type and its variable. A line break is allowed, as a multi-catch's types
     * may be split over lines.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the `catch`'s opening parenthesis.
     * @param int $closer The position of the `catch`'s closing parenthesis.
     */
    private function checkCatchVariable(File $phpcsFile, int $opener, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        $variable = $phpcsFile->findPrevious(T_WHITESPACE, ($closer - 1), $opener, true);
        if ($variable === false || $tokens[$variable]['code'] !== T_VARIABLE) {
            return;
        }

        // Anything but whitespace before the variable is the type, or a comment, which is left alone.
        $type = $phpcsFile->findPrevious(T_WHITESPACE, ($variable - 1), $opener, true);
        if (
            $type === false
            || $tokens[$type]['line'] !== $tokens[$variable]['line']
            || isset(Tokens::COMMENT_TOKENS[$tokens[$type]['code']])
        ) {
            return;
        }

        $found = $phpcsFile->getTokensAsString(($type + 1), ($variable - $type - 1));
        if ($found === ' ') {
            return;
        }

        $error = 'Expected 1 space between the type and the variable of a catch; %s found';
        $fix = $phpcsFile->addFixableError($error, $variable, self::CODE_SPACE_BEFORE_CATCH_VARIABLE, [strlen($found)]);
        if ($fix) {
            FixerHelper::replaceBetween($phpcsFile, $type, $variable, ' ');
        }
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
