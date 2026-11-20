<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\ControlStructures;

use Jumbo\Helpers\SingleLineHelper;
use PERCS31\Helpers\LayoutHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires a control structure's header on one line if it fits, and split over lines if it doesn't.
 *
 * It covers `if`, `elseif`, `while`, including a `do`-`while`'s, `foreach`, `for`, `switch` and `match`:
 * - A header split over lines is joined if the whole resulting line fits: the text before the opening parenthesis, the
 *   joined header, and the text after the closing parenthesis on its line, such as ` {`. So the fix never creates a
 *   line that's too long. See SingleLineHelper for the headers that are never joined, such as one with a comment.
 * - A header on one line too long for the line length is split. The condition goes on its own line after the `(`,
 *   indented once, and the `)` starts the next line, level with the keyword's line, as PER Coding Style 3.1, section 5,
 *   shows. A `for` header's expressions go on a line each.
 *
 * The condition itself is left as it is. Jumbo.Formatting.ExpressionFormat splits it further, if it's still too long
 * on its own line, and PERCS31.ControlStructures.ControlStructureSpacing checks the rest of the split layout, such as a
 * `foreach`'s `as` starting its own line.
 */
class HeaderLayoutSniff implements Sniff
{
    public const string CODE_SHOULD_BE_SINGLE_LINE = 'ShouldBeSingleLine';

    public const string CODE_SHOULD_BE_MULTI_LINE = 'ShouldBeMultiLine';

    /**
     * Maximum line length.
     */
    public int $maxLineLength = 120;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_IF, T_ELSEIF, T_WHILE, T_FOREACH, T_FOR, T_SWITCH, T_MATCH];
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
        if (!isset($tokens[$stackPtr]['parenthesis_opener'], $tokens[$stackPtr]['parenthesis_closer'])) {
            return;
        }

        $openPtr = $tokens[$stackPtr]['parenthesis_opener'];
        $closePtr = $tokens[$stackPtr]['parenthesis_closer'];
        if ($tokens[$openPtr]['line'] === $tokens[$closePtr]['line']) {
            $this->splitIfTooLong($phpcsFile, $stackPtr, $openPtr, $closePtr);
            return;
        }

        $collapsed = SingleLineHelper::collapse($phpcsFile, $openPtr, $closePtr, $this->maxLineLength);
        if ($collapsed === null) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Multi-line %s header fits within %s characters, so it must be written on one line.',
            $stackPtr,
            self::CODE_SHOULD_BE_SINGLE_LINE,
            [$tokens[$stackPtr]['content'], $this->maxLineLength],
        );
        if ($fix) {
            SingleLineHelper::replace($phpcsFile, $openPtr, $closePtr, $collapsed);
        }
    }

    /**
     * Report a header on one line that's too long for the line length, and fix it by splitting it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the keyword.
     * @param int $openPtr The position of the opening parenthesis.
     * @param int $closePtr The position of the closing parenthesis.
     */
    private function splitIfTooLong(File $phpcsFile, int $stackPtr, int $openPtr, int $closePtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $first = $phpcsFile->findNext(T_WHITESPACE, ($openPtr + 1), $closePtr, true);
        if ($first === false || $this->lineLength($phpcsFile, $openPtr) <= $this->maxLineLength) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'The %s header is too long for %s characters, so it must be split over lines.',
            $stackPtr,
            self::CODE_SHOULD_BE_MULTI_LINE,
            [$tokens[$stackPtr]['content'], $this->maxLineLength],
        );
        if (!$fix) {
            return;
        }

        $indent = LayoutHelper::lineIndent($phpcsFile, $stackPtr);
        $innerBreak = $phpcsFile->eolChar . str_repeat(' ', ($indent + LayoutHelper::INDENT));
        $last = $phpcsFile->findPrevious(T_WHITESPACE, ($closePtr - 1), $openPtr, true);

        $phpcsFile->fixer->beginChangeset();
        $this->setGap($phpcsFile, $openPtr, $first, $innerBreak);

        // A for header's expressions go on a line each.
        if ($tokens[$stackPtr]['code'] === T_FOR) {
            for ($i = $first; $i < $closePtr; $i++) {
                $next = $phpcsFile->findNext(T_WHITESPACE, ($i + 1), $closePtr, true);
                if ($tokens[$i]['code'] === T_SEMICOLON && $next !== false) {
                    $this->setGap($phpcsFile, $i, $next, $innerBreak);
                }
            }
        }

        if ($last !== false) {
            $this->setGap($phpcsFile, $last, $closePtr, $phpcsFile->eolChar . str_repeat(' ', $indent));
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Get the length of the line a token is on, without trailing whitespace.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the token.
     * @return int The number of characters.
     */
    private function lineLength(File $phpcsFile, int $ptr): int
    {
        $tokens = $phpcsFile->getTokens();
        $line = $tokens[$ptr]['line'];
        $text = '';
        for ($i = $ptr; $i >= 0 && $tokens[$i]['line'] === $line; $i--) {
            $text = $tokens[$i]['content'] . $text;
        }

        for ($i = ($ptr + 1); isset($tokens[$i]) && $tokens[$i]['line'] === $line; $i++) {
            $text .= $tokens[$i]['content'];
        }

        return mb_strlen(rtrim($text));
    }

    /**
     * Replace the whitespace between two tokens.
     *
     * @param File $phpcsFile The file being fixed.
     * @param int $from The token before the gap.
     * @param int $to The token after the gap.
     * @param string $content The whitespace wanted.
     */
    private function setGap(File $phpcsFile, int $from, int $to, string $content): void
    {
        for ($i = ($from + 1); $i < $to; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContent($from, $content);
    }
}
