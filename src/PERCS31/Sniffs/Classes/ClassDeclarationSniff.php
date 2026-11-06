<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\EmptyBodyHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\ClassDeclarationSniff as PSR2Sniff;

/**
 * Checks the declaration of a class, interface, trait or enum, allowing an empty body as `{}`.
 *
 * PSR2.Classes.ClassDeclaration requires the opening brace on the line after the declaration, so it reports
 * `class Foo {}`. PER Coding Style 3.1, section 4, says an empty body should be written that way. For an empty body on
 * one line, this sniff runs only the parent's checks on the declaration itself (`extends`, `implements` and their
 * spacing), and skips its brace checks. Everything else is left to the parent.
 */
class ClassDeclarationSniff extends PSR2Sniff
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
            $this->processOpen($phpcsFile, $stackPtr);
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }
}
