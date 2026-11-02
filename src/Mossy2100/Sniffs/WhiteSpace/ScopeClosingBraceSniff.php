<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\WhiteSpace;

use Mossy2100\Helpers\EmptyBodyHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\ScopeClosingBraceSniff as SquizSniff;

/**
 * Checks that the closing brace of a scope is on its own line, allowing an empty class or function body as `{}`.
 *
 * Squiz.WhiteSpace.ScopeClosingBrace reports the `}` of `class Foo {}` and `function foo(): void {}`. PER Coding Style
 * 3.0, sections 4 and 4.4, says an empty body should be written that way. This sniff skips an empty class or function
 * body on one line, and leaves everything else to the parent.
 */
class ScopeClosingBraceSniff extends SquizSniff
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
        if (EmptyBodyHelper::isOnOneLine($phpcsFile, $stackPtr)) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }
}
