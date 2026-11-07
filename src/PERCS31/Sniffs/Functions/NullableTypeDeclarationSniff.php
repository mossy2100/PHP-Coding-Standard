<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Functions;

use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Functions\NullableTypeDeclarationSniff as Psr12Sniff;

/**
 * Disallows a space between the `?` of a nullable type and the type, wherever a type can appear.
 *
 * PER Coding Style 3.1, section 4.5, says there MUST NOT be a space between the question mark and the type.
 * PSR12.Functions.NullableTypeDeclaration checks every position but one: PHP_CodeSniffer doesn't know about hooks, so
 * it gives the `?` in `set(? int $value)` a ternary's token, and the parent doesn't see it. This sniff also checks that
 * `?`. There, `array` keeps its own token, which the parent doesn't accept as a type, so this sniff checks that case
 * itself. Everything else is left to the parent.
 */
class NullableTypeDeclarationSniff extends Psr12Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [...parent::register(), T_INLINE_THEN];
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
        if ($tokens[$stackPtr]['code'] === T_INLINE_THEN) {
            if (!PropertyHookHelper::isHookParameterType($phpcsFile, $stackPtr)) {
                return;
            }

            $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
            if ($next !== false && $tokens[$next]['code'] === T_ARRAY) {
                $this->checkArraySpacing($phpcsFile, $stackPtr, $next);
                return;
            }
        }

        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * Report whitespace between a `set` hook's `?` and `array`, and fix it by removing it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the `?`.
     * @param int $next The position of `array`.
     */
    private function checkArraySpacing(File $phpcsFile, int $stackPtr, int $next): void
    {
        if ($next === ($stackPtr + 1)) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'There must not be a space between the question mark and the type in nullable type declarations',
            $stackPtr,
            'WhitespaceFound',
        );
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($stackPtr + 1); $i < $next; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }
}
