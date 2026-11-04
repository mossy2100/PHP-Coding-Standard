<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Arrays;
use PHPCSUtils\Utils\PassedParameters;

/**
 * Requires the comma after an element of a multi-line array to be on the element's last line, not the next one.
 *
 * PER Coding Style 3.1 says nothing about where an array element's comma goes, so this is an extra rule, for the
 * Jumbo standard only. It replaces Squiz.Arrays.ArrayDeclaration's NoComma check, which only checks a keyed element,
 * and wrongly expects the comma on the line after a heredoc's or nowdoc's closing marker, as before PHP 7.3. So a
 * heredoc element written `EOT,` is reported, and the fix moves the comma onto a line of its own.
 *
 * A heredoc's or nowdoc's closing marker counts as the element's last line.
 */
class ElementCommaSniff implements Sniff
{
    public const string CODE_NOT_ON_ELEMENT_LINE = 'NotOnElementLine';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_ARRAY, T_OPEN_SHORT_ARRAY];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] === T_OPEN_SHORT_ARRAY && !Arrays::isShortArray($phpcsFile, $stackPtr)) {
            return;
        }

        $openClose = Arrays::getOpenClose($phpcsFile, $stackPtr);
        if ($openClose === false || $tokens[$openClose['opener']]['line'] === $tokens[$openClose['closer']]['line']) {
            return;
        }

        foreach (PassedParameters::getParameters($phpcsFile, $stackPtr) as $element) {
            $this->checkElement($phpcsFile, (int) $element['start'], (int) $element['end'], $openClose['closer']);
        }
    }

    /**
     * Check that the comma after an element is on the element's last line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the element's first token.
     * @param int $end The position of the element's last token, which may be whitespace or a comment.
     * @param int $closer The position of the array's closing bracket.
     */
    private function checkElement(File $phpcsFile, int $start, int $end, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, $end, ($start - 1), true);
        $comma = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($end + 1), $closer, true);
        if ($last === false || $comma === false || $tokens[$comma]['code'] !== T_COMMA) {
            return;
        }

        if ($tokens[$comma]['line'] === $tokens[$last]['line']) {
            return;
        }

        $error = 'The comma after an array element must be on the element\'s last line';
        if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($last + 1), $comma) !== false) {
            $phpcsFile->addError($error, $comma, self::CODE_NOT_ON_ELEMENT_LINE);
            return;
        }

        if (!$phpcsFile->addFixableError($error, $comma, self::CODE_NOT_ON_ELEMENT_LINE)) {
            return;
        }

        // Only whitespace is between them, so remove it with the comma, and put the comma after the element.
        $phpcsFile->fixer->beginChangeset();
        for ($i = ($last + 1); $i <= $comma; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContent($last, ',');
        $phpcsFile->fixer->endChangeset();
    }
}
