<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Disallows a space before the semicolon that ends an arrow function's expression.
 *
 * PER Coding Style 3.1, section 7.1, says the semicolon at the end of a short closure's expression MUST NOT be
 * preceded by a space. SlevomatCodingStandard.Functions.ArrowFunctionDeclaration checks the spacing around `fn` and
 * `=>`, but not this. The fix removes the space, or line break, before the semicolon.
 */
class ArrowFunctionSemicolonSpacingSniff implements Sniff
{
    public const string CODE_SPACE_BEFORE = 'SpaceBefore';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_FN];
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
        $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
        if (
            !is_int($closer)
            || $tokens[$closer]['code'] !== T_SEMICOLON
            || $tokens[($closer - 1)]['code'] !== T_WHITESPACE
        ) {
            return;
        }

        // An arrow function nested in another shares its semicolon, so only the outermost reports it.
        for ($i = ($stackPtr - 1); $i >= 0 && $tokens[$i]['code'] !== T_SEMICOLON; $i--) {
            if ($tokens[$i]['code'] === T_FN && ($tokens[$i]['scope_closer'] ?? null) === $closer) {
                return;
            }
        }

        $fix = $phpcsFile->addFixableError(
            'There must be no space before the semicolon that ends an arrow function',
            $closer,
            self::CODE_SPACE_BEFORE,
        );
        if (!$fix) {
            return;
        }

        $expressionEnd = $phpcsFile->findPrevious(T_WHITESPACE, ($closer - 1), null, true);
        $phpcsFile->fixer->beginChangeset();
        for ($i = ($closer - 1); $i > $expressionEnd; $i--) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }
}
