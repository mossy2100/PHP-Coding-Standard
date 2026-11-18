<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires one space, or a line break, before and after a `foreach`'s `as` and `=>`.
 *
 * PER Coding Style 3.1, section 5.5, shows `foreach ($iterable as $key => $value)`, and says to "note the placement of
 * parentheses, spaces, and braces". It has no rule for a `foreach` split inside its parentheses, so a line break before
 * or after either one is allowed.
 *
 * This replaces Squiz.ControlStructures.ForEachLoopDeclaration, which PSR-12 includes. That sniff reads the indent of
 * an `as` or `=>` that starts a line as the space before it, and reports it. Its fix replaces the indent with one
 * space, which leaves the line indented by one space. It also takes the first `=>` after `as` for the loop's, so it
 * checks the `=>` of a key in `as ['key' => $value]`, which PERCS31.WhiteSpace.ArrowSpacing checks as a list's. It has
 * no property for either, so it can't be configured, and a subclass would have nothing of the parent left to call: its
 * other three checks are already turned off by PSR-12.
 */
class ForEachSpacingSniff implements Sniff
{
    public const string CODE_SPACING_BEFORE_AS = 'SpacingBeforeAs';

    public const string CODE_SPACING_AFTER_AS = 'SpacingAfterAs';

    public const string CODE_SPACING_BEFORE_ARROW = 'SpacingBeforeArrow';

    public const string CODE_SPACING_AFTER_ARROW = 'SpacingAfterArrow';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_FOREACH];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$stackPtr]['parenthesis_opener'] ?? null;
        $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
        if (!is_int($opener) || !is_int($closer)) {
            return;
        }

        // Directly in the parentheses, so the `=>` of an array or a list isn't taken for the loop's.
        $asPtr = TokenHelper::findNextAtLevel(
            $phpcsFile,
            ($opener + 1),
            $closer,
            static fn(array $token): bool => $token['code'] === T_AS,
        );
        if ($asPtr === null) {
            return;
        }

        $this->checkSpacing($phpcsFile, $asPtr, true, self::CODE_SPACING_BEFORE_AS);
        $this->checkSpacing($phpcsFile, $asPtr, false, self::CODE_SPACING_AFTER_AS);

        $arrowPtr = TokenHelper::findNextAtLevel(
            $phpcsFile,
            ($asPtr + 1),
            $closer,
            static fn(array $token): bool => $token['code'] === T_DOUBLE_ARROW,
        );
        if ($arrowPtr === null) {
            return;
        }

        $this->checkSpacing($phpcsFile, $arrowPtr, true, self::CODE_SPACING_BEFORE_ARROW);
        $this->checkSpacing($phpcsFile, $arrowPtr, false, self::CODE_SPACING_AFTER_ARROW);
    }

    /**
     * Check the spacing on one side of an `as` or `=>`, and fix it if needed.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the `as` or `=>`.
     * @param bool $isBefore Whether to check the side before the token, not the side after it.
     * @param string $code The error code.
     */
    private function checkSpacing(File $phpcsFile, int $ptr, bool $isBefore, string $code): void
    {
        $tokens = $phpcsFile->getTokens();
        $sidePtr = $isBefore ? ($ptr - 1) : ($ptr + 1);
        $isWhitespace = $tokens[$sidePtr]['code'] === T_WHITESPACE;

        // One space is correct, and so is a line break.
        if (FixerHelper::hasSingleSpaceBeside($phpcsFile, $ptr, $isBefore)) {
            return;
        }

        $error = 'Expected 1 space %s "%s" in foreach; %s found';
        $data = [
            $isBefore ? 'before' : 'after',
            strtolower($tokens[$ptr]['content']),
            $isWhitespace ? $tokens[$sidePtr]['length'] : 0,
        ];
        if (!$phpcsFile->addFixableError($error, $ptr, $code, $data)) {
            return;
        }

        FixerHelper::setSingleSpaceBeside($phpcsFile, $ptr, $isBefore);
    }
}
