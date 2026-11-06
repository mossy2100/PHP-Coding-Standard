<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a `match` arm that wraps to be indented one level past its first line.
 *
 * PER Coding Style 3.1 doesn't say how a wrapped arm is indented, so this is an extra rule, to keep each arm distinct.
 * It checks a line starting with a later condition, the `=>`, or the value after the `=>`. A line starting with an
 * operator is left to PERCS31.Operators.OperatorPlacement, and the lines inside brackets to the sniffs for those.
 */
class MatchArmWrapIndentSniff implements Sniff
{
    public const string CODE_INDENT = 'Indent';

    /**
     * The number of spaces in one indent.
     */
    public int $indent = 4;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_MATCH];
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
        $opener = $tokens[$stackPtr]['scope_opener'] ?? null;
        $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
        if (!is_int($opener) || !is_int($closer)) {
            return;
        }

        $armStart = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($opener + 1), $closer, true);
        $inBody = false;
        for ($i = ($opener + 1); $i < $closer; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_MATCH_ARROW) {
                $inBody = true;
                if ($armStart !== false) {
                    $this->checkLine($phpcsFile, $i, $armStart);
                    $value = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), $closer, true);
                    if ($value !== false) {
                        $this->checkLine($phpcsFile, $value, $armStart);
                    }
                }
            } elseif ($code === T_COMMA) {
                $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), $closer, true);
                if ($inBody) {
                    // The comma that ends an arm.
                    $armStart = $next;
                    $inBody = false;
                } elseif ($next !== false && $armStart !== false && $tokens[$next]['code'] !== T_MATCH_ARROW) {
                    // A comma between two conditions.
                    $this->checkLine($phpcsFile, $next, $armStart);
                }
            }

            $jumpTo = $tokens[$i]['parenthesis_closer'] ?? $tokens[$i]['bracket_closer']
                ?? $tokens[$i]['attribute_closer'] ?? null;
            if (is_int($jumpTo) && $jumpTo > $i && $jumpTo < $closer) {
                $i = $jumpTo;
            }
        }
    }

    /**
     * Check the indent of a token that starts a line, one level past the line its arm starts on.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token.
     * @param int $armStart The position of the arm's first token.
     */
    private function checkLine(File $phpcsFile, int $stackPtr, int $armStart): void
    {
        $tokens = $phpcsFile->getTokens();
        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
        if ($firstOnLine !== $stackPtr || $tokens[$stackPtr]['line'] === $tokens[$armStart]['line']) {
            return;
        }

        $firstOfArm = $phpcsFile->findFirstOnLine(T_WHITESPACE, $armStart, true);
        assert(is_int($firstOfArm));
        $expected = $tokens[$firstOfArm]['column'] - 1 + $this->indent;
        $found = $tokens[$stackPtr]['column'] - 1;
        if ($found === $expected) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'A wrapped match arm must be indented once past its first line; expected %s spaces, found %s',
            $stackPtr,
            self::CODE_INDENT,
            [$expected, $found],
        );
        if (!$fix) {
            return;
        }

        if ($found === 0) {
            $phpcsFile->fixer->addContentBefore($stackPtr, str_repeat(' ', $expected));
        } else {
            $phpcsFile->fixer->replaceToken(($stackPtr - 1), str_repeat(' ', $expected));
        }
    }
}
