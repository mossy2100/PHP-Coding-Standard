<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\Functions;

use Mossy2100\Helpers\EmptyBodyHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Functions\MultiLineFunctionDeclarationSniff as SquizSniff;

/**
 * Checks function declarations, allowing an empty body as `{}` after a single-line signature.
 *
 * Squiz.Functions.MultiLineFunctionDeclaration requires the opening brace of a single-line declaration on the next
 * line, so it reports `function foo(): void {}`. PER Coding Style 3.0, section 4.4, says an empty body should be
 * written that way. For an empty body on one line, this sniff skips the single-line brace checks. That also skips the
 * parent's `CloseBracketNewLine` check for those functions, as it can't be run on its own. Everything else is left to
 * the parent, including multi-line declarations, which already allow `) {}`.
 */
class MultiLineFunctionDeclarationSniff extends SquizSniff
{
    /**
     * Processes single-line declarations.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @param array<int, array<string, mixed>> $tokens The stack of tokens that make up the file.
     * @return void
     */
    public function processSingleLineDeclaration(File $phpcsFile, int $stackPtr, array $tokens): void
    {
        if (EmptyBodyHelper::isOnOneLine($phpcsFile, $stackPtr)) {
            return;
        }

        parent::processSingleLineDeclaration($phpcsFile, $stackPtr, $tokens);
    }
}
