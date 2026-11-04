<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Formatting;

use Jumbo\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\DisallowMultipleStatementsSniff as GenericSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Ensures each statement is on a line by itself, allowing the operations of an abstract or interface property.
 *
 * Generic.Formatting.DisallowMultipleStatements treats `{ get; set; }` on an abstract or interface property as two
 * statements on one line. PER Coding Style 3.0, section 4.10, requires them on one line. This sniff skips a semicolon
 * that ends a `get` or `set` operation when the previous one does too, and leaves everything else to the parent.
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
        $prev = $phpcsFile->findPrevious(T_SEMICOLON, ($stackPtr - 1));
        if (
            $prev !== false
            && $this->endsHookOperation($phpcsFile, $stackPtr)
            && $this->endsHookOperation($phpcsFile, $prev)
        ) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
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
        $tokens = $phpcsFile->getTokens();
        $keyword = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($semicolonPtr - 1), null, true);
        if ($keyword === false || $tokens[$keyword]['code'] !== T_STRING) {
            return false;
        }

        $name = strtolower($tokens[$keyword]['content']);
        if ($name !== 'get' && $name !== 'set') {
            return false;
        }

        return PropertyHookHelper::isInsidePropertyHook($phpcsFile, $semicolonPtr);
    }
}
