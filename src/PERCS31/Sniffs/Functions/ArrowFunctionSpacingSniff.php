<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Functions;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Disallows a space before the semicolon that ends an arrow function's expression, a line break after `fn`, and a blank
 * line before a wrapped `=>`.
 *
 * PER Coding Style 3.1, section 7.1, says the semicolon at the end of a short closure's expression MUST NOT be
 * preceded by a space. SlevomatCodingStandard.Functions.ArrowFunctionDeclaration checks the spacing around `fn` and
 * `=>`, but not this. Squiz.WhiteSpace.SemicolonSpacing would, but it checks every semicolon, which PER doesn't
 * require. The fix removes the space, or line break, before the semicolon.
 *
 * Section 7.1 also says the `fn` keyword MUST NOT be succeeded by a space. The Slevomat sniff checks that, but not when
 * a line break follows `fn`, as its allowMultiLine property turns that check off, and PERCS31 needs the property on so
 * that `=>` may start the next line. So this sniff reports a line break after `fn`, and the fix removes it.
 *
 * Section 7.1 also says that if the expression is split to a subsequent line, the `=>` MUST be included on the second
 * line. So a blank line before a wrapped `=>` is reported, as BlankLineBeforeArrow, and the fix removes it.
 */
class ArrowFunctionSpacingSniff implements Sniff
{
    public const string CODE_SPACE_BEFORE = 'SpaceBefore';

    public const string CODE_LINE_BREAK_AFTER_FN = 'LineBreakAfterFn';

    public const string CODE_BLANK_LINE_BEFORE_ARROW = 'BlankLineBeforeArrow';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_FN];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $this->checkLineBreakAfterFn($phpcsFile, $stackPtr);
        $this->checkBlankLineBeforeArrow($phpcsFile, $stackPtr);

        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
        if (
            !is_int($closer) || $tokens[$closer]['code'] !== T_SEMICOLON
            || $tokens[($closer - 1)]['code'] !== T_WHITESPACE
        ) {
            return;
        }

        // An arrow function nested in another shares its semicolon, so only the outermost reports it.
        for ($i = ($stackPtr - 1); $i >= 0 && $tokens[$i]['code'] !== T_SEMICOLON; $i--) {
            if ($tokens[$i]['code'] === T_FN && ($tokens[$i]['scope_closer'] ?? null) === $closer) {
                return;
            }
        }

        $fix = $phpcsFile->addFixableError(
            'There must be no space before the semicolon that ends an arrow function',
            $closer,
            self::CODE_SPACE_BEFORE,
        );
        if (!$fix) {
            return;
        }

        $expressionEnd = $phpcsFile->findPrevious(T_WHITESPACE, ($closer - 1), null, true);
        if ($expressionEnd === false) {
            return;
        }

        FixerHelper::replaceBetween($phpcsFile, $expressionEnd, $closer, '');
    }

    /**
     * Report a blank line before a wrapped `=>`, and fix it by removing every blank line there.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of `fn`.
     */
    private function checkBlankLineBeforeArrow(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $arrow = $tokens[$stackPtr]['scope_opener'] ?? null;
        if (!is_int($arrow)) {
            return;
        }

        $blankLines = LayoutHelper::findBlankLinesBefore($phpcsFile, $arrow);
        if ($blankLines === []) {
            return;
        }

        $error = 'There must be no blank line before an arrow function\'s wrapped "=>"';
        if ($phpcsFile->addFixableError($error, $arrow, self::CODE_BLANK_LINE_BEFORE_ARROW)) {
            FixerHelper::removeTokens($phpcsFile, $blankLines);
        }
    }

    /**
     * Report a line break between `fn` and what follows it, and fix it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of `fn`.
     */
    private function checkLineBreakAfterFn(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if ($next === false || $tokens[$next]['line'] === $tokens[$stackPtr]['line']) {
            return;
        }

        // A comment between them would be lost, so that's left to a person.
        $error = 'There must be no line break after "fn"';
        $isFixable = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($stackPtr + 1), $next) === false;
        $code = self::CODE_LINE_BREAK_AFTER_FN;
        if (!FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $stackPtr, $code)) {
            return;
        }

        FixerHelper::replaceBetween($phpcsFile, $stackPtr, $next, '');
    }
}
