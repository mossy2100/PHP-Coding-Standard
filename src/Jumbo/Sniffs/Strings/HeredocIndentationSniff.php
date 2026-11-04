<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Strings;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires a heredoc's or nowdoc's body and closing marker to be indented once past the line it starts on.
 *
 * PER Coding Style 3.1, section 10, says subsequent lines in a heredoc or nowdoc MUST be indented once past the scope
 * indentation they're declared in. Its examples indent them once past the line holding `<<<`, whether that's an
 * assignment or a function call's argument. Since PHP 7.3, PHP removes the closing marker's indentation from every
 * body line, and rejects a body line indented less than the marker, so this sniff checks the marker. The fix moves the
 * marker and every body line by the same amount, keeping any extra indentation within the text.
 *
 * An indent is 4 spaces, as PER Coding Style section 2.4 requires.
 */
class HeredocIndentationSniff implements Sniff
{
    public const string CODE_INDENT = 'Indent';

    /**
     * The number of spaces in one indent.
     */
    private const int INDENT = 4;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_START_HEREDOC, T_START_NOWDOC];
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
        $end = $phpcsFile->findNext([T_END_HEREDOC, T_END_NOWDOC], ($stackPtr + 1));
        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
        if ($end === false || $firstOnLine === false) {
            return;
        }

        $expected = $tokens[$firstOnLine]['column'] - 1 + self::INDENT;
        $endContent = $tokens[$end]['content'];
        $found = strlen($endContent) - strlen(ltrim($endContent, ' '));
        if ($found === $expected) {
            return;
        }

        $kind = $tokens[$stackPtr]['code'] === T_START_NOWDOC ? 'nowdoc' : 'heredoc';
        $fix = $phpcsFile->addFixableError(
            'A %s must be indented once past the line it starts on; expected %s spaces before its closing marker, '
                . 'found %s',
            $end,
            self::CODE_INDENT,
            [$kind, $expected, $found],
        );
        if ($fix) {
            $this->reindent($phpcsFile, $stackPtr, $end, ($expected - $found));
        }
    }

    /**
     * Move the body lines and the closing marker of a heredoc or nowdoc left or right.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the `<<<` token.
     * @param int $end The position of the closing marker.
     * @param int $shift The number of spaces to add, or to remove if negative.
     */
    private function reindent(File $phpcsFile, int $start, int $end, int $shift): void
    {
        $tokens = $phpcsFile->getTokens();
        $phpcsFile->fixer->beginChangeset();
        for ($i = ($start + 1); $i <= $end; $i++) {
            if ($tokens[$i]['column'] !== 1) {
                continue;
            }

            $content = $tokens[$i]['content'];
            if (trim($content) === '') {
                // A blank line in the body.
                continue;
            }

            if ($shift > 0) {
                $phpcsFile->fixer->replaceToken($i, str_repeat(' ', $shift) . $content);
            } else {
                $phpcsFile->fixer->replaceToken($i, substr($content, -$shift));
            }
        }

        $phpcsFile->fixer->endChangeset();
    }
}
