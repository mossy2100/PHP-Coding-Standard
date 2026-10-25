<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires exactly one space before and after the `=>` in a match arm.
 *
 * Squiz.WhiteSpace.OperatorSpacing checks `=>` in arrays and foreach loops, but not in match arms, which have their own
 * token. Aligned arrows make noisy diffs, so they aren't allowed here either.
 *
 * A line break before or after the arrow is allowed, so a long arm can be wrapped.
 */
class MatchArrowSpacingSniff implements Sniff
{
    public const string CODE_SPACING_BEFORE = 'SpacingBefore';

    public const string CODE_SPACING_AFTER = 'SpacingAfter';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_MATCH_ARROW];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $this->checkSpacing($phpcsFile, $stackPtr, $stackPtr - 1, 'before', self::CODE_SPACING_BEFORE);
        $this->checkSpacing($phpcsFile, $stackPtr, $stackPtr + 1, 'after', self::CODE_SPACING_AFTER);
    }

    /**
     * Check the spacing on one side of the arrow, and fix it if needed.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $arrowPtr The position of the arrow.
     * @param int $sidePtr The position of the token next to the arrow, on the side being checked.
     * @param string $side 'before' or 'after', for the error message.
     * @param string $code The error code.
     */
    private function checkSpacing(File $phpcsFile, int $arrowPtr, int $sidePtr, string $side, string $code): void
    {
        $tokens = $phpcsFile->getTokens();
        $isWhitespace = $tokens[$sidePtr]['code'] === T_WHITESPACE;

        // One space is correct. A line break is allowed; indentation at the start of a line is a separate token.
        if (
            $isWhitespace
            && (
                $tokens[$sidePtr]['content'] === ' '
                || str_contains($tokens[$sidePtr]['content'], "\n")
                || $tokens[$sidePtr]['column'] === 1
            )
        ) {
            return;
        }

        $found = $isWhitespace ? strlen($tokens[$sidePtr]['content']) : 0;
        $fix = $phpcsFile->addFixableError(
            'Expected 1 space %s "=>" in match arm; %s found.',
            $arrowPtr,
            $code,
            [$side, $found]
        );
        if (!$fix) {
            return;
        }

        if ($isWhitespace) {
            $phpcsFile->fixer->replaceToken($sidePtr, ' ');
        } elseif ($side === 'before') {
            $phpcsFile->fixer->addContentBefore($arrowPtr, ' ');
        } else {
            $phpcsFile->fixer->addContent($arrowPtr, ' ');
        }
    }
}
