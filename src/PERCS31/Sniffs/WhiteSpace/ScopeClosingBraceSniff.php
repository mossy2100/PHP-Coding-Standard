<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\WhiteSpace;

use PERCS31\Helpers\EmptyBodyHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\ScopeClosingBraceSniff as SquizSniff;

/**
 * Checks that the closing brace of a scope is on its own line, allowing an empty body as `{}`.
 *
 * Squiz.WhiteSpace.ScopeClosingBrace reports the `}` of `class Foo {}`, `function foo(): void {}` and
 * `function () {}`. PER Coding Style 3.1, sections 4.4 and 7, says an empty function body should, and an empty closure
 * body must, be written that way. This sniff skips an empty body on one line, and leaves everything else to the parent.
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
