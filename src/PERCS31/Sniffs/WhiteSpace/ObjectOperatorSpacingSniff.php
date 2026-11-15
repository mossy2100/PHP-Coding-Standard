<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\ObjectOperatorSpacingSniff as SquizSniff;

/**
 * Disallows white space around `->`, `?->` and `::`, except a line break before `->` or `?->` in a split chain.
 *
 * PER Coding Style 3.1 has no rule text for these, but every example writes them with no space around them, as in
 * `$foo->bar($arg1);` and `Foo::bar($arg2, $arg3);` (4.7), `A::smallTalk insteadof B;` (4.2) and
 * `public const Wild = self::Spades;` (9). Section 4.7 lets a method chain be split, with each `->` starting a line.
 *
 * Squiz.WhiteSpace.ObjectOperatorSpacing checks all three. Its ignoreNewlines property allows a line break on either
 * side of any of them, and PERCS31 needs one before `->` and `?->`, but never around `::`. No property sets that per
 * token, so this subclass turns ignoreNewlines on by default, and off for `::`. A line break after `->` or `?->` is
 * reported by PERCS31.Formatting.MethodChaining, as OperatorAtEndOfLine.
 */
class ObjectOperatorSpacingSniff extends SquizSniff
{
    /**
     * Whether a line break is allowed instead of a space, before or after `->` and `?->`. It's never allowed around
     * `::`.
     *
     * @var bool
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint -- The parent's has no type.
    public $ignoreNewlines = true;

    /**
     * Check the spacing before the operator.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @param mixed $before The number of spaces before the operator, or 'newline'.
     * @return bool True if there was no error.
     */
    // phpcs:ignore Jumbo.TypeHints.ParameterTypeHint.MissingNativeTypeHint -- The parent's $before has no type.
    protected function checkSpacingBeforeOperator(File $phpcsFile, int $stackPtr, $before): bool
    {
        return $this->withNewlinesForToken(
            $phpcsFile,
            $stackPtr,
            fn(): bool => (bool) parent::checkSpacingBeforeOperator($phpcsFile, $stackPtr, $before),
        );
    }

    /**
     * Check the spacing after the operator.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @param mixed $after The number of spaces after the operator, or 'newline'.
     * @return bool True if there was no error.
     */
    // phpcs:ignore Jumbo.TypeHints.ParameterTypeHint.MissingNativeTypeHint -- The parent's $after has no type.
    protected function checkSpacingAfterOperator(File $phpcsFile, int $stackPtr, $after): bool
    {
        return $this->withNewlinesForToken(
            $phpcsFile,
            $stackPtr,
            fn(): bool => (bool) parent::checkSpacingAfterOperator($phpcsFile, $stackPtr, $after),
        );
    }

    /**
     * Run a check with ignoreNewlines off for `::`, and as set for any other operator.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @param callable(): bool $check The check to run.
     * @return bool The check's result.
     */
    private function withNewlinesForToken(File $phpcsFile, int $stackPtr, callable $check): bool
    {
        if ($phpcsFile->getTokens()[$stackPtr]['code'] !== T_DOUBLE_COLON) {
            return $check();
        }

        $ignoreNewlines = $this->ignoreNewlines;
        $this->ignoreNewlines = false;

        try {
            return $check();
        } finally {
            $this->ignoreNewlines = $ignoreNewlines;
        }
    }
}
