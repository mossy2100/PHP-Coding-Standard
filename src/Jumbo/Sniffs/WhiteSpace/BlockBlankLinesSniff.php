<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\WhiteSpace;

use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Disallows a blank line straight after the opening brace, or straight before the closing brace, of a block declare, a
 * property's hook list and a hook's body.
 *
 * PER Coding Style 3.1 only rules these blank lines out for a class (4.1) and a control structure (5), and the end of
 * a function's body (4.4, 7). It has no rule for a block declare, or for a property hook, so PERCS31 allows them, as
 * section 2.3 allows a blank line wherever nothing forbids one. This is an extra rule, so that every block in braces
 * starts and ends the same way. A blank line between two statements, or between two hooks, isn't reported.
 *
 * No inherited sniff covers these blocks. Squiz.WhiteSpace.ControlStructureSpacing has a fixed list of control
 * structures, without `declare`, and PHP_CodeSniffer's tokenizer doesn't give a property hook's braces a scope. The
 * start of a function's body is checked by Squiz.WhiteSpace.FunctionOpeningBraceSpace, which the Jumbo ruleset
 * includes.
 */
class BlockBlankLinesSniff implements Sniff
{
    public const string CODE_AFTER_OPENING_BRACE = 'AfterOpeningBrace';

    public const string CODE_BEFORE_CLOSING_BRACE = 'BeforeClosingBrace';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_OPEN_TAG];
    }

    /**
     * Processes this test, when one of its tokens is encountered. The whole file is checked from its first opening
     * tag, as the hooks of every property are found in one pass.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return int The position to skip to, which is the end of the file.
     */
    public function process(File $phpcsFile, int $stackPtr): int
    {
        $tokens = $phpcsFile->getTokens();
        foreach (PropertyHookHelper::findHookScopes($phpcsFile) as $opener => $closer) {
            $this->checkBlock($phpcsFile, $opener, $closer, 'property hook');
        }

        $declare = $phpcsFile->findNext(T_DECLARE, ($stackPtr + 1));
        while ($declare !== false) {
            $opener = $tokens[$declare]['scope_opener'] ?? null;
            $closer = $tokens[$declare]['scope_closer'] ?? null;
            if (is_int($opener) && is_int($closer) && $tokens[$opener]['code'] === T_OPEN_CURLY_BRACKET) {
                $this->checkBlock($phpcsFile, $opener, $closer, 'block declare');
            }

            $declare = $phpcsFile->findNext(T_DECLARE, ($declare + 1));
        }

        return $phpcsFile->numTokens;
    }

    /**
     * Report each blank line straight after a block's opening brace, and straight before its closing one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the opening brace.
     * @param int $closer The position of the closing brace.
     * @param string $kind The kind of block, for the message.
     */
    private function checkBlock(File $phpcsFile, int $opener, int $closer, string $kind): void
    {
        $firstContent = $phpcsFile->findNext(T_WHITESPACE, ($opener + 1), null, true);
        foreach (LayoutHelper::findBlankLinesAtStartAndEnd($phpcsFile, $opener, $closer) as $blankLine) {
            $isAtStart = $firstContent === false || $blankLine < $firstContent;
            $error = $isAtStart
                ? 'Blank line not allowed after the opening brace of a %s'
                : 'Blank line not allowed before the closing brace of a %s';
            $code = $isAtStart ? self::CODE_AFTER_OPENING_BRACE : self::CODE_BEFORE_CLOSING_BRACE;
            if ($phpcsFile->addFixableError($error, $blankLine, $code, [$kind])) {
                $phpcsFile->fixer->replaceToken($blankLine, '');
            }
        }
    }
}
