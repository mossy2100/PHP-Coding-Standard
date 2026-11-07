<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\LanguageConstructSpacingSniff as GenericSniff;

/**
 * Requires a single space after a language construct, such as `return`, `echo` or `throw`, with no line break.
 *
 * Generic.WhiteSpace.LanguageConstructSpacing does this, but it also checks `new`, which PER Coding Style 3.1, section
 * 8, puts on its own line before an anonymous class's attributes. This sniff registers every token the parent does
 * except `new`.
 *
 * The parent also wants a space after a `yield` with no value before `)`, `]` or `,`, so its fix writes `(yield )`.
 * This sniff skips those, and leaves everything else to the parent.
 */
class LanguageConstructSpacingSniff extends GenericSniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return array_values(array_filter(parent::register(), static fn(int|string $token): bool => $token !== T_NEW));
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return int|null The position to skip to, as the parent returns, or null.
     */
    public function process(File $phpcsFile, int $stackPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $next = $tokens[($stackPtr + 1)]['code'] ?? null;
        $endsExpression = in_array(
            $next,
            [T_CLOSE_PARENTHESIS, T_CLOSE_SQUARE_BRACKET, T_CLOSE_SHORT_ARRAY, T_COMMA],
            true,
        );
        if ($tokens[$stackPtr]['code'] === T_YIELD && $endsExpression) {
            return null;
        }

        $result = parent::process($phpcsFile, $stackPtr);

        return is_int($result) ? $result : null;
    }
}
