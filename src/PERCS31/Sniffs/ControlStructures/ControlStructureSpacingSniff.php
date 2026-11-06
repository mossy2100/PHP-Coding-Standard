<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\ControlStructures\ControlStructureSpacingSniff as Psr12Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks the layout of a control structure's parentheses, allowing a multi-line array as the whole condition.
 *
 * PER Coding Style 3.1, section 5, says a split expression's first condition MUST be on the line after `(`. But
 * PERCS31.Arrays.OpeningBracketPlacement won't allow `[` on a line of its own, so a condition that's only an array
 * can't satisfy both. This subclass keeps the array's `[` on the `(` line, and only checks that the `)` is on a line of
 * its own, lined up with the structure. The array sniffs check the array's lines.
 *
 * Everything else is left to the parent.
 */
class ControlStructureSpacingSniff extends Psr12Sniff
{
    public const string CODE_CLOSE_PARENTHESIS_LINE = 'CloseParenthesisLine';

    public const string CODE_CLOSE_PARENTHESIS_INDENT = 'CloseParenthesisIndent';

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
        if (!is_int($opener) || !is_int($closer) || !$this->isMultiLineArrayCondition($phpcsFile, $opener, $closer)) {
            parent::process($phpcsFile, $stackPtr);
            return;
        }

        $first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
        $indent = ($tokens[$first]['column'] - 1);
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($closer - 1), $opener, true);
        if ($tokens[$previous]['line'] === $tokens[$closer]['line']) {
            $error = 'The closing parenthesis of a multi-line control structure must be on a line of its own';
            if ($phpcsFile->addFixableError($error, $closer, self::CODE_CLOSE_PARENTHESIS_LINE)) {
                $padding = $phpcsFile->eolChar . str_repeat(' ', $indent);
                $phpcsFile->fixer->addContentBefore($closer, $padding);
            }

            return;
        }

        if ($tokens[$closer]['column'] - 1 !== $indent) {
            $error = 'The closing parenthesis of a multi-line control structure must line up with the structure';
            if ($phpcsFile->addFixableError($error, $closer, self::CODE_CLOSE_PARENTHESIS_INDENT)) {
                $phpcsFile->fixer->replaceToken(($closer - 1), str_repeat(' ', $indent));
            }
        }
    }

    /**
     * Check whether a condition is only an array, starting on the `(` line and split over lines.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the opening parenthesis.
     * @param int $closer The position of the closing parenthesis.
     * @return bool True if the array is the whole condition.
     */
    private function isMultiLineArrayCondition(File $phpcsFile, int $opener, int $closer): bool
    {
        $tokens = $phpcsFile->getTokens();
        $array = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($opener + 1), $closer, true);
        if (
            $array === false
            || $tokens[$array]['code'] !== T_OPEN_SHORT_ARRAY
            || $tokens[$array]['line'] !== $tokens[$opener]['line']
        ) {
            return false;
        }

        $arrayCloser = $tokens[$array]['bracket_closer'];
        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($arrayCloser + 1), $closer, true);

        return $next === false && $tokens[$arrayCloser]['line'] !== $tokens[$array]['line'];
    }
}
