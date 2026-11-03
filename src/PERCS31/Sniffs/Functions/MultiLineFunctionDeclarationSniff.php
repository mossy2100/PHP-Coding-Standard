<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Functions;

use PERCS31\Helpers\EmptyBodyHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Functions\MultiLineFunctionDeclarationSniff as SquizSniff;

/**
 * Checks function and closure declarations, allowing an empty body as `{}` after a single-line signature.
 *
 * Squiz.Functions.MultiLineFunctionDeclaration requires the opening brace of a single-line declaration on the next
 * line, so it reports `function foo(): void {}`. PER Coding Style 3.1, sections 4.4 and 7, says an empty function body
 * should, and an empty closure body must, be written that way. For an empty body on one line, this sniff skips the
 * single-line brace checks. That also skips the parent's `CloseBracketNewLine` check for those functions, as it can't
 * be run on its own. Everything else is left to the parent, including multi-line declarations, which already allow
 * `) {}`.
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
