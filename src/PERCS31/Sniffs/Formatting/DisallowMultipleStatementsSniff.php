<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Formatting;

use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\DisallowMultipleStatementsSniff as GenericSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Ensures each statement is on a line by itself, allowing the operations of an abstract or interface property.
 *
 * Generic.Formatting.DisallowMultipleStatements treats `{ get; set; }` on an abstract or interface property as two
 * statements on one line. PER Coding Style 3.1, section 4.10, requires them on one line. This sniff skips a semicolon
 * that ends a `get` or `set` operation when the previous one does too, and leaves everything else to the parent.
 *
 * It also skips a semicolon in the hooks of a promoted constructor parameter. Section 4.9 only allows a single short
 * hook on one line there, and PERCS31.Classes.PropertyHooks reports anything else, which a person has to move
 * to the class body. The parent's fix would split the hooks over lines first, leaving a layout nobody wants.
 *
 * The parent only compares the lines of two semicolons. So it misses a second statement that ends on a later line, as
 * in `$a = 1; foo(`, or has no semicolon, as in `$a = 1; if ($b) {`. This sniff reports a statement that starts after
 * a semicolon on the same line, when the parent doesn't.
 */
class DisallowMultipleStatementsSniff extends GenericSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return void
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        if ($this->isInPromotedPropertyHook($phpcsFile, $stackPtr)) {
            return;
        }

        $prev = $phpcsFile->findPrevious(T_SEMICOLON, ($stackPtr - 1));
        if (
            $prev !== false && $this->endsHookOperation($phpcsFile, $stackPtr)
            && $this->endsHookOperation($phpcsFile, $prev)
        ) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);

        $this->checkStatementAfter($phpcsFile, $stackPtr);
    }

    /**
     * Report a statement that starts on a semicolon's line, after it, when the next semicolon is on a later line. The
     * parent reports the statement when its own semicolon is on that line too.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $semicolonPtr The position of the semicolon.
     */
    private function checkStatementAfter(File $phpcsFile, int $semicolonPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $line = $tokens[$semicolonPtr]['line'];
        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($semicolonPtr + 1), null, true);
        if (
            $next === false || $tokens[$next]['line'] !== $line || $tokens[$next]['code'] === T_CLOSE_CURLY_BRACKET
            || $tokens[$next]['code'] === T_CLOSE_TAG
        ) {
            return;
        }

        // What follows `__halt_compiler();` is data, not code.
        if ($phpcsFile->findPrevious(T_HALT_COMPILER, ($semicolonPtr - 1)) !== false) {
            return;
        }

        // The semicolons in a for loop's parentheses separate expressions, not statements.
        foreach (($tokens[$semicolonPtr]['nested_parenthesis'] ?? []) as $opener => $closer) {
            $owner = $tokens[$opener]['parenthesis_owner'] ?? null;
            if (is_int($owner) && $tokens[$owner]['code'] === T_FOR) {
                return;
            }
        }

        // Left to the parent, and to this sniff's exceptions for hooks.
        $nextSemicolon = $phpcsFile->findNext(T_SEMICOLON, $next);
        if ($nextSemicolon !== false && $tokens[$nextSemicolon]['line'] === $line) {
            return;
        }

        $fix = $phpcsFile->addFixableError('Each PHP statement must be on a line by itself', $next, 'SameLine');
        if (!$fix) {
            return;
        }

        // The line break goes before the statement, so a comment after the semicolon stays with the first statement.
        if ($tokens[($next - 1)]['code'] === T_WHITESPACE) {
            $phpcsFile->fixer->replaceToken(($next - 1), $phpcsFile->eolChar);
        } else {
            $phpcsFile->fixer->addContentBefore($next, $phpcsFile->eolChar);
        }
    }

    /**
     * Check whether a semicolon is in a hook of a promoted constructor parameter.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $semicolonPtr The position of the semicolon.
     * @return bool True if it's inside a function's parameter list, and inside a property's hook list there.
     */
    private function isInPromotedPropertyHook(File $phpcsFile, int $semicolonPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        foreach (array_keys($tokens[$semicolonPtr]['nested_parenthesis'] ?? []) as $opener) {
            $owner = $tokens[$opener]['parenthesis_owner'] ?? null;
            if (is_int($owner) && $tokens[$owner]['code'] === T_FUNCTION) {
                return PropertyHookHelper::isInsidePropertyHook($phpcsFile, $semicolonPtr);
            }
        }

        return false;
    }

    /**
     * Check whether a semicolon ends a bodiless hook operation, such as the `get;` in `{ get; set; }`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $semicolonPtr The position of the semicolon.
     * @return bool True if the semicolon follows `get` or `set` inside a property's hook list.
     */
    private function endsHookOperation(File $phpcsFile, int $semicolonPtr): bool
    {
        $keyword = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($semicolonPtr - 1), null, true);

        return $keyword !== false && PropertyHookHelper::isHookName($phpcsFile, $keyword);
    }
}
