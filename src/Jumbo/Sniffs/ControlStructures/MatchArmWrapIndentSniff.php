<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\ControlStructures;

use PERCS31\Helpers\MatchHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a `match` arm to be on one line up to its value, if it fits, and otherwise indented one level past its first
 * line.
 *
 * PER Coding Style 3.1 doesn't say how a wrapped arm is indented, so this is an extra rule, to keep each arm distinct.
 * It checks a line starting with a later condition, the `=>`, or the value after the `=>`. A line starting with an
 * operator is left to PERCS31.Operators.OperatorPlacement, and the lines inside brackets to the sniffs for those.
 *
 * If the arm's conditions, `=>` and the first line of its value fit within maxLineLength, they must be on one line. A
 * comment among them is left to a person.
 */
class MatchArmWrapIndentSniff implements Sniff
{
    public const string CODE_INDENT = 'Indent';

    public const string CODE_SHOULD_BE_SINGLE_LINE = 'ShouldBeSingleLine';

    /**
     * The number of spaces in one indent.
     */
    public int $indent = 4;

    /**
     * The longest a line can be, to bring a wrapped arm onto one line.
     */
    public int $maxLineLength = 120;

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

        foreach (MatchHelper::findArms($phpcsFile, $opener, $closer) as $arm) {
            $value = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($arm['arrow'] + 1), $closer, true);
            if ($value === false) {
                continue;
            }

            if (!$this->checkFitsOnOneLine($phpcsFile, $arm['start'], $value)) {
                $this->checkLines($phpcsFile, $arm['start'], $arm['arrow'], $value);
            }
        }
    }

    /**
     * Report an arm that's split before its value, but would fit on one line, and fix it by joining the lines.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $armStart The position of the arm's first token.
     * @param int $value The position of the first token of the arm's value.
     * @return bool True if it was reported.
     */
    private function checkFitsOnOneLine(File $phpcsFile, int $armStart, int $value): bool
    {
        $tokens = $phpcsFile->getTokens();
        if (
            $tokens[$armStart]['line'] === $tokens[$value]['line']
            || $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($armStart + 1), $value) !== false
        ) {
            return false;
        }

        // The arm's start to its value, with each line break as one space, then the rest of the value's first line.
        $joined = $tokens[$armStart]['column'] - 1;
        for ($i = $armStart; $i < $value; $i++) {
            $isBreak = $tokens[$i]['code'] === T_WHITESPACE && str_contains($tokens[$i]['content'], "\n");
            $isIndent = $tokens[$i]['code'] === T_WHITESPACE && $tokens[$i]['column'] === 1;
            if ($isIndent) {
                continue;
            }

            $joined += $isBreak ? 1 : $tokens[$i]['length'];
        }

        for ($i = $value; isset($tokens[$i]) && $tokens[$i]['line'] === $tokens[$value]['line']; $i++) {
            if ($tokens[$i]['content'] !== "\n") {
                $joined += $tokens[$i]['length'];
            }
        }

        if ($joined > $this->maxLineLength) {
            return false;
        }

        $fix = $phpcsFile->addFixableError(
            'A match arm that fits within %s characters must be on one line up to its value',
            $armStart,
            self::CODE_SHOULD_BE_SINGLE_LINE,
            [$this->maxLineLength],
        );
        if (!$fix) {
            return true;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($armStart + 1); $i < $value; $i++) {
            if ($tokens[$i]['code'] !== T_WHITESPACE) {
                continue;
            }

            $phpcsFile->fixer->replaceToken($i, '');
            if ($tokens[($i - 1)]['code'] !== T_WHITESPACE) {
                $phpcsFile->fixer->addContent(($i - 1), ' ');
            }
        }

        $phpcsFile->fixer->endChangeset();

        return true;
    }

    /**
     * Check the indent of each line that starts with a condition, the `=>` or the value, up to the arm's value.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $armStart The position of the arm's first token.
     * @param int $arrow The position of the arm's `=>`.
     * @param int $value The position of the first token of the arm's value.
     */
    private function checkLines(File $phpcsFile, int $armStart, int $arrow, int $value): void
    {
        $tokens = $phpcsFile->getTokens();
        $this->checkLine($phpcsFile, $arrow, $armStart);
        $this->checkLine($phpcsFile, $value, $armStart);
        for ($i = ($armStart + 1); $i < $arrow; $i++) {
            if ($tokens[$i]['code'] === T_COMMA) {
                $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), $arrow, true);
                if ($next !== false) {
                    $this->checkLine($phpcsFile, $next, $armStart);
                }
            }

            $jumpTo = TokenHelper::findCloser($tokens[$i]);
            if (is_int($jumpTo) && $jumpTo > $i && $jumpTo < $arrow) {
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
