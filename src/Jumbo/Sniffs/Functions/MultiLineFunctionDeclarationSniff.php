<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Functions;

use Jumbo\Helpers\EmptyBodyHelper;
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
 *
 * The parent also reports any blank line in a multi-line parameter or `use` list (EmptyLine). PER Coding Style 3.1,
 * section 12.2, allows one between a parameter and the attributes of the next. The parent's check can't be run on its
 * own, so the PERCS31 ruleset excludes EmptyLine, and this sniff reports blank lines itself (BlankLine), except that
 * one.
 */
class MultiLineFunctionDeclarationSniff extends SquizSniff
{
    public const string CODE_BLANK_LINE = 'BlankLine';

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

    /**
     * Processes multi-line declarations, then checks their blank lines.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @param array<int, array<string, mixed>> $tokens The stack of tokens that make up the file.
     * @return void
     */
    public function processMultiLineDeclaration(File $phpcsFile, int $stackPtr, array $tokens): void
    {
        parent::processMultiLineDeclaration($phpcsFile, $stackPtr, $tokens);
        $this->checkBlankLines($phpcsFile, $stackPtr);
    }

    /**
     * Report each blank line in the parameter and `use` lists, except one before a parameter's attributes.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the function keyword.
     */
    private function checkBlankLines(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$stackPtr]['parenthesis_opener'] ?? null;
        $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
        if (!is_int($opener) || !is_int($closer)) {
            return;
        }

        // A closure's use list is checked too, as the parent does.
        $scopeOpener = $tokens[$stackPtr]['scope_opener'] ?? null;
        if ($tokens[$stackPtr]['code'] === T_CLOSURE && is_int($scopeOpener)) {
            $use = $phpcsFile->findNext(T_USE, ($closer + 1), $scopeOpener);
            $useCloser = $use === false ? null : ($tokens[$use]['parenthesis_closer'] ?? null);
            $closer = is_int($useCloser) ? $useCloser : $closer;
        }

        for ($i = ($opener + 1); $i < $closer; $i++) {
            $isBlankLine = $tokens[$i]['code'] === T_WHITESPACE
                && $tokens[$i]['column'] === 1
                && $tokens[$i]['line'] !== $tokens[($i + 1)]['line'];
            if (!$isBlankLine || $this->isAllowedBeforeAttribute($phpcsFile, $i)) {
                continue;
            }

            $fix = $phpcsFile->addFixableError(
                'Blank lines are not allowed in a multi-line function declaration, except before a parameter\'s '
                    . 'attributes',
                $i,
                self::CODE_BLANK_LINE,
            );
            if ($fix) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }
    }

    /**
     * Check whether a blank line is the one PER allows: after a parameter's comma, and before the next parameter's
     * attributes.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $blankPtr The blank line's whitespace token.
     * @return bool True if it's a single blank line between a comma and an attribute.
     */
    private function isAllowedBeforeAttribute(File $phpcsFile, int $blankPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $next = $phpcsFile->findNext(T_WHITESPACE, ($blankPtr + 1), null, true);
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($blankPtr - 1), null, true);
        if ($next === false || $previous === false) {
            return false;
        }

        // Only one blank line: the code before it is on the line just above.
        return $tokens[$next]['code'] === T_ATTRIBUTE
            && $tokens[$previous]['code'] === T_COMMA
            && $tokens[$previous]['line'] === ($tokens[$blankPtr]['line'] - 1);
    }
}
