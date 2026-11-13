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
 * `function () {}`. PER Coding Style 3.1, sections 4, 4.4 and 7, says an empty class or function body should, and an
 * empty closure body must, be written that way. Section 5 says nothing about a control structure's empty body, so
 * `catch (Exception $e) {}` is allowed too. This sniff skips an empty body on one line, and leaves everything else to
 * the parent.
 *
 * It also skips `case` and `default`, which have no braces. The parent treats a case's terminating statement, such as
 * `break`, as its closing brace. PERCS31.ControlStructures.SwitchDeclaration and
 * PERCS31.Formatting.DisallowMultipleStatements check where that statement goes.
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

        $tokens = $phpcsFile->getTokens();
        if (in_array($tokens[$stackPtr]['code'], [T_CASE, T_DEFAULT], true)) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }
}
