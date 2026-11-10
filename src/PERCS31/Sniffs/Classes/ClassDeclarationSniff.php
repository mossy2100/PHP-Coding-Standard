<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\EmptyBodyHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\ClassDeclarationSniff as Psr2Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks the declaration of a class, interface, trait or enum, allowing an empty body as `{}`.
 *
 * PSR2.Classes.ClassDeclaration requires the opening brace on the line after the declaration, so it reports
 * `class Foo {}`. PER Coding Style 3.1, section 4, says an empty body should be written that way. For an empty body on
 * one line, this sniff runs only the parent's checks on the declaration itself (`extends`, `implements` and their
 * spacing), and skips its brace checks. Everything else is left to the parent.
 *
 * It also requires a backed enum's type in lower case, as in `enum Suit: string`. Section 2.5 says all types MUST be in
 * lower case, and Generic.PHP.LowerCaseType doesn't check a backing type.
 */
class ClassDeclarationSniff extends Psr2Sniff
{
    public const string CODE_BACKING_TYPE_CASE = 'BackingTypeCase';

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return void
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        if ($phpcsFile->getTokens()[$stackPtr]['code'] === T_ENUM) {
            $this->checkBackingTypeCase($phpcsFile, $stackPtr);
        }

        if (EmptyBodyHelper::isOnOneLine($phpcsFile, $stackPtr)) {
            $this->processOpen($phpcsFile, $stackPtr);
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * Report a backed enum's type that isn't in lower case, and fix it by lowering it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the `enum` keyword.
     */
    private function checkBackingTypeCase(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $name = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        $colon = $name === false ? false : $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($name + 1), null, true);
        if ($colon === false || $tokens[$colon]['code'] !== T_COLON) {
            return;
        }

        $type = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($colon + 1), null, true);
        if ($type === false) {
            return;
        }

        $content = $tokens[$type]['content'];
        $lower = strtolower($content);
        if ($content === $lower) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'An enum\'s backing type must be in lower case; expected "%s" but found "%s"',
            $type,
            self::CODE_BACKING_TYPE_CASE,
            [$lower, $content],
        );
        if ($fix) {
            $phpcsFile->fixer->replaceToken($type, $lower);
        }
    }
}
