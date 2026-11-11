<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Functions;

use PERCS31\Helpers\EmptyBodyHelper;
use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Functions\MultiLineFunctionDeclarationSniff as SquizSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks function, closure and arrow function declarations, allowing an empty body as `{}` after a single-line
 * signature.
 *
 * Squiz.Functions.MultiLineFunctionDeclaration requires the opening brace of a single-line declaration on the next
 * line, so it reports `function foo(): void {}`. PER Coding Style 3.1, sections 4.4 and 7, says an empty function body
 * should, and an empty closure body must, be written that way. For an empty body on one line, this sniff skips the
 * single-line brace checks. That also skips the parent's `CloseBracketNewLine` check for those functions, as it can't
 * be run on its own. Everything else is left to the parent, including multi-line declarations, which already allow
 * `) {}`.
 *
 * The parent also reports any blank line in a multi-line parameter or `use` list (EmptyLine). PER Coding Style 3.1,
 * section 12.4's example has one between two parameters, both before an attribute and before a parameter with none.
 * The parent's check can't be run on its own, so the PERCS31 ruleset excludes EmptyLine, and this sniff reports blank
 * lines itself (BlankLine). A single blank line between parameters is allowed, as the example shows. Any other, such
 * as more than one in a row, one before the closing parenthesis, or one between `use` items, is reported.
 *
 * The parent doesn't register an arrow function. Section 7.1 says one follows the same rules as a closure, so this
 * sniff registers `fn` too, and checks a split parameter list. It has no braces, so the brace checks don't apply.
 *
 * The parent would also indent every line of a promoted parameter's multi-line hook list as a parameter, fighting
 * PERCS31.WhiteSpace.ScopeIndent until phpcbf fails. PERCS31.Classes.PromotedPropertyHooks already reports that code,
 * and it has to be rewritten, so this sniff leaves its parameter list alone.
 *
 * The parent's fix for a closure's `use` on the line after the closing parenthesis never finishes. Its SpaceBeforeUse
 * fix reads the line break as no space, and adds one, leaving `use` on its line, and its Indent fix then indents that
 * line. The two undo each other until phpcbf fails. So this sniff reports that `use` itself, with the parent's code,
 * and joins it to the parenthesis's line. The parent's checks run on the next pass. This is a workaround for an
 * upstream bug, in PEAR.Functions.FunctionDeclaration, to remove once it's fixed there.
 */
class MultiLineFunctionDeclarationSniff extends SquizSniff
{
    public const string CODE_BLANK_LINE = 'BlankLine';

    /**
     * Returns an array of tokens this test wants to listen for: the parent's, and an arrow function's `fn`.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [...parent::register(), T_FN];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        if ($phpcsFile->getTokens()[$stackPtr]['code'] === T_FN) {
            $this->processArrowFunction($phpcsFile, $stackPtr);
            return;
        }

        if ($this->joinUseToParenthesis($phpcsFile, $stackPtr)) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * Report a closure's `use` on a line after its parameter list's closing parenthesis, and join it to that line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the function keyword.
     * @return bool True if it was reported, so the parent's checks, whose fixes for it undo each other, are skipped.
     */
    private function joinUseToParenthesis(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
        $opener = $tokens[$stackPtr]['scope_opener'] ?? null;
        if ($tokens[$stackPtr]['code'] !== T_CLOSURE || !is_int($closer) || !is_int($opener)) {
            return false;
        }

        $use = $phpcsFile->findNext(T_USE, ($closer + 1), $opener);
        if ($use === false || $tokens[$use]['line'] === $tokens[$closer]['line']) {
            return false;
        }

        // A comment between them would be lost.
        $isFixable = $phpcsFile->findNext(T_WHITESPACE, ($closer + 1), $use, true) === false;
        $error = 'Expected 1 space before USE keyword; found newline';
        if (FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $use, 'SpaceBeforeUse')) {
            FixerHelper::replaceBetween($phpcsFile, $closer, $use, ' ');
        }

        return true;
    }

    /**
     * Checks an arrow function's multi-line parameter list, with the parents' argument list checks.
     *
     * PER Coding Style 3.1, section 7.1, says an arrow function follows the same rules as a closure, so a split
     * parameter list has its first parameter on the next line, one parameter per line, each indented once, and the
     * closing parenthesis on a line of its own. An arrow function has no braces, so the parents' brace checks don't
     * apply.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of `fn`.
     */
    private function processArrowFunction(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$stackPtr]['parenthesis_opener'] ?? null;
        $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
        if (!is_int($opener) || !is_int($closer) || $tokens[$opener]['line'] === $tokens[$closer]['line']) {
            return;
        }

        $this->processBracket($phpcsFile, $opener, $tokens);
        $this->processArgumentList($phpcsFile, $stackPtr, $this->indent);
        $this->checkBlankLines($phpcsFile, $stackPtr);
    }

    /**
     * Processes a multi-line function's arguments, unless a promoted parameter has a multi-line hook list.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the function keyword.
     * @param int $indent The number of spaces code should be indented.
     * @param string $type The type of the token the brackets belong to.
     * @return void
     */
    public function processArgumentList(File $phpcsFile, int $stackPtr, int $indent, string $type = 'function'): void
    {
        if (PropertyHookHelper::hasMultiLinePromotedPropertyHook($phpcsFile, $stackPtr)) {
            return;
        }

        parent::processArgumentList($phpcsFile, $stackPtr, $indent, $type);
    }

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
     * Report each blank line in the parameter and `use` lists, except those PER allows.
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

        $parameterCloser = $tokens[$opener]['parenthesis_closer'];
        for ($i = ($opener + 1); $i < $closer; $i++) {
            $isBlankLine = $tokens[$i]['code'] === T_WHITESPACE
                && $tokens[$i]['column'] === 1
                && $tokens[$i]['line'] !== $tokens[($i + 1)]['line'];
            if (!$isBlankLine || $this->isAllowedBetweenParameters($phpcsFile, $i, $parameterCloser)) {
                continue;
            }

            $fix = $phpcsFile->addFixableError(
                'Blank line not allowed here in a multi-line function declaration',
                $i,
                self::CODE_BLANK_LINE,
            );
            if ($fix) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }
    }

    /**
     * Check whether a blank line is always allowed: a single one after a parameter's comma, before the next parameter
     * or its attributes.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $blankPtr The blank line's whitespace token.
     * @param int $parameterCloser The position of the parameter list's closing parenthesis.
     * @return bool True if it's a single blank line between two parameters.
     */
    private function isAllowedBetweenParameters(File $phpcsFile, int $blankPtr, int $parameterCloser): bool
    {
        $tokens = $phpcsFile->getTokens();
        $next = $phpcsFile->findNext(T_WHITESPACE, ($blankPtr + 1), null, true);
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($blankPtr - 1), null, true);
        if ($next === false || $previous === false) {
            return false;
        }

        // Inside the parameter list, not before its closing parenthesis, and only one blank line: the comma, or a
        // comment after it, is on the line just above.
        $comma = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($blankPtr - 1), null, true);

        return $next < $parameterCloser
            && $comma !== false
            && $tokens[$comma]['code'] === T_COMMA
            && $tokens[$previous]['line'] === ($tokens[$blankPtr]['line'] - 1);
    }
}
