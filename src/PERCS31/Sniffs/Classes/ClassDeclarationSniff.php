<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\EmptyBodyHelper;
use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
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
 * Section 4.1 says a split list of interfaces has "only one interface per line". The parent's check for that
 * (InterfaceSameLine, and ExtendsInterfaceSameLine for an interface's `extends`) also reports an interface after a
 * blank line, with the same message. Section 2.3 allows blank lines "except where explicitly forbidden", and nothing
 * forbids one between two interfaces. The parent's check can't be run without that, so the PERCS31 ruleset excludes
 * both codes, and this sniff reports an interface that shares a line with the one before it (InterfaceNotOnOwnLine).
 *
 * Section 4.1 also says the opening brace MUST NOT be followed by a blank line, so one is reported, as
 * BlankLineAfterOpenBrace. This replaces PSR12.Classes.OpeningBraceSpace, which also reports an anonymous class.
 * Section 8 gives an anonymous class the closure rules, which have no rule for the line after the brace, so it isn't
 * checked, as this sniff doesn't listen for one.
 */
class ClassDeclarationSniff extends Psr2Sniff
{
    public const string CODE_INTERFACE_NOT_ON_OWN_LINE = 'InterfaceNotOnOwnLine';

    public const string CODE_BLANK_LINE_AFTER_OPEN_BRACE = 'BlankLineAfterOpenBrace';

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return void
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $this->checkOneInterfacePerLine($phpcsFile, $stackPtr);

        if (EmptyBodyHelper::isOnOneLine($phpcsFile, $stackPtr)) {
            $this->processOpen($phpcsFile, $stackPtr);
            return;
        }

        parent::process($phpcsFile, $stackPtr);
        $this->checkBlankLineAfterOpenBrace($phpcsFile, $stackPtr);
    }

    /**
     * Report a blank line straight after the opening brace, as section 4.1 forbids one, and fix it by removing every
     * blank line there.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the class, interface, trait or enum keyword.
     */
    private function checkBlankLineAfterOpenBrace(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$stackPtr]['scope_opener'] ?? null;
        if (!is_int($opener)) {
            return;
        }

        $blankLines = [];
        for ($i = ($opener + 1); isset($tokens[$i]) && $tokens[$i]['code'] === T_WHITESPACE; $i++) {
            if (LayoutHelper::isBlankLine($phpcsFile, $i)) {
                $blankLines[] = $i;
            }
        }

        if ($blankLines === []) {
            return;
        }

        $error = 'The opening brace of a %s must not be followed by a blank line';
        $data = [strtolower($tokens[$stackPtr]['content'])];
        if (!$phpcsFile->addFixableError($error, $opener, self::CODE_BLANK_LINE_AFTER_OPEN_BRACE, $data)) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        foreach ($blankLines as $blankLine) {
            $phpcsFile->fixer->replaceToken($blankLine, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Report an interface that shares a line with the one before it, in a list of interfaces split over lines: a
     * class's or enum's `implements`, or an interface's `extends`. The first interface is left to the parent.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the class, interface or enum keyword.
     */
    private function checkOneInterfacePerLine(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$stackPtr]['scope_opener'] ?? null;
        $keywordCode = $tokens[$stackPtr]['code'] === T_INTERFACE ? T_EXTENDS : T_IMPLEMENTS;
        $keyword = is_int($opener) ? $phpcsFile->findNext($keywordCode, ($stackPtr + 1), $opener) : false;
        if (!is_int($opener) || $keyword === false) {
            return;
        }

        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($opener - 1), $keyword, true);
        if ($last === false || $tokens[$last]['line'] === $tokens[$keyword]['line']) {
            return;
        }

        $indent = str_repeat(' ', (LayoutHelper::lineIndent($phpcsFile, $stackPtr) + LayoutHelper::INDENT));
        for ($comma = $keyword; ($comma = $phpcsFile->findNext(T_COMMA, ($comma + 1), $opener)) !== false;) {
            $name = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($comma + 1), $opener, true);
            if ($name === false || $phpcsFile->findFirstOnLine(T_WHITESPACE, $name, true) === $name) {
                continue;
            }

            $error = 'Only one interface may be specified per line in a multi-line %s declaration';
            $data = [strtolower($tokens[$keyword]['content'])];
            $before = $phpcsFile->findPrevious(T_WHITESPACE, ($name - 1), $keyword, true);
            $code = self::CODE_INTERFACE_NOT_ON_OWN_LINE;
            if ($before !== false && $phpcsFile->addFixableError($error, $name, $code, $data)) {
                FixerHelper::replaceBetween($phpcsFile, $before, $name, $phpcsFile->eolChar . $indent);
            }
        }
    }
}
