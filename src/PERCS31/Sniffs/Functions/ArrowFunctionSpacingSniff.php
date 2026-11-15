<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Functions;

use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Disallows a space before the semicolon that ends an arrow function's expression, and a line break after `fn`.
 *
 * PER Coding Style 3.1, section 7.1, says the semicolon at the end of a short closure's expression MUST NOT be
 * preceded by a space. SlevomatCodingStandard.Functions.ArrowFunctionDeclaration checks the spacing around `fn` and
 * `=>`, but not this. Squiz.WhiteSpace.SemicolonSpacing would, but it checks every semicolon, which PER doesn't
 * require. The fix removes the space, or line break, before the semicolon.
 *
 * Section 7.1 also says the `fn` keyword MUST NOT be succeeded by a space. The Slevomat sniff checks that, but not when
 * a line break follows `fn`, as its allowMultiLine property turns that check off, and PERCS31 needs the property on so
 * that `=>` may start the next line. So this sniff reports a line break after `fn`, and the fix removes it.
 */
class ArrowFunctionSpacingSniff implements Sniff
{
    public const string CODE_SPACE_BEFORE = 'SpaceBefore';

    public const string CODE_LINE_BREAK_AFTER_FN = 'LineBreakAfterFn';

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

        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
        if (
            !is_int($closer)
            || $tokens[$closer]['code'] !== T_SEMICOLON
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
