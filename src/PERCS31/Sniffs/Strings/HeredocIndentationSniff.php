<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Strings;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\OperatorHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a heredoc or nowdoc to begin on the line of its context, and its body and closing marker to be indented once
 * past that line.
 *
 * PER Coding Style 3.1, section 10, says a heredoc or nowdoc MUST begin on the same line as the context it's used in,
 * and on the same line as the variable it's set against. So `$x =` or `return` followed by `<<<` on the next line is
 * reported, and so is `$x` followed by `= <<<`, although section 6.4 allows a statement to be split before `=`. Its own
 * example has a call's argument on its own line, so one after `(`, `,` or `[` is allowed. One after a binary operator
 * is allowed too, as that operator's own sniff moves it to the start of the line, as in `. <<<`.
 *
 * Section 10 also says subsequent lines in a heredoc or nowdoc MUST be indented once past the scope
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

    public const string CODE_NOT_ON_CONTEXT_LINE = 'NotOnContextLine';

    /**
     * Tokens that a heredoc or nowdoc may follow on an earlier line: the start of a list item or a statement.
     */
    private const array ITEM_OR_STATEMENT_STARTS = [
        T_OPEN_PARENTHESIS,
        T_OPEN_SHORT_ARRAY,
        T_OPEN_SQUARE_BRACKET,
        T_COMMA,
        T_OPEN_CURLY_BRACKET,
        T_CLOSE_CURLY_BRACKET,
        T_SEMICOLON,
        T_OPEN_TAG,
    ];

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
        // Joining lines changes the line to indent past, so the indent is checked on the next pass.
        if ($this->checkContext($phpcsFile, $stackPtr)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $end = $phpcsFile->findNext([T_END_HEREDOC, T_END_NOWDOC], ($stackPtr + 1));
        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
        if ($end === false || $firstOnLine === false) {
            return;
        }

        $expected = $tokens[$firstOnLine]['column'] - 1 + LayoutHelper::INDENT;
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
     * Check that a heredoc or nowdoc begins on the line of its context.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the `<<<` token.
     * @return bool True if it doesn't.
     */
    private function checkContext(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $context = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($stackPtr - 1), null, true);
        if ($context === false) {
            return false;
        }

        $code = $tokens[$context]['code'];
        if ($tokens[$context]['line'] !== $tokens[$stackPtr]['line']) {
            if (in_array($code, self::ITEM_OR_STATEMENT_STARTS, true) || $this->isLeftToOperatorSniffs($code)) {
                return false;
            }

            $this->reportContext($phpcsFile, $stackPtr, $context, $stackPtr);
            return true;
        }

        // `$x` followed by `= <<<`. The operator is on the heredoc's line, but what it sets isn't.
        if (!$this->isAssignmentOrArrow($code)) {
            return false;
        }

        $target = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($context - 1), null, true);
        if ($target === false || $tokens[$target]['line'] === $tokens[$context]['line']) {
            return false;
        }

        $this->reportContext($phpcsFile, $stackPtr, $target, $context);
        return true;
    }

    /**
     * Report a heredoc or nowdoc that isn't on its context's line, and fix it by joining two tokens' lines.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the `<<<` token.
     * @param int $from The token to stay where it is.
     * @param int $to The token to move up to it.
     */
    private function reportContext(File $phpcsFile, int $stackPtr, int $from, int $to): void
    {
        $tokens = $phpcsFile->getTokens();
        $kind = $tokens[$stackPtr]['code'] === T_START_NOWDOC ? 'nowdoc' : 'heredoc';
        $error = 'A %s must begin on the same line as the context it\'s used in';
        if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($from + 1), $to) !== false) {
            $phpcsFile->addError($error, $stackPtr, self::CODE_NOT_ON_CONTEXT_LINE, [$kind]);
            return;
        }

        if (!$phpcsFile->addFixableError($error, $stackPtr, self::CODE_NOT_ON_CONTEXT_LINE, [$kind])) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        FixerHelper::blankBetween($phpcsFile, $from, $to);

        $phpcsFile->fixer->addContent($from, ' ');
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check whether a token is an assignment operator or a `=>`, which a heredoc or nowdoc must be on the line after.
     *
     * @param int|string $code The token's code.
     * @return bool True if it is.
     */
    private function isAssignmentOrArrow(int|string $code): bool
    {
        return isset(Tokens::ASSIGNMENT_TOKENS[$code]) || in_array($code, [T_MATCH_ARROW, T_FN_ARROW], true);
    }

    /**
     * Check whether a token is a binary operator that its own sniff moves to the start of the next line, so that the
     * heredoc or nowdoc is on the operator's line after all. An assignment operator or a `=>` isn't one of them.
     *
     * @param int|string $code The token's code.
     * @return bool True if it is.
     */
    private function isLeftToOperatorSniffs(int|string $code): bool
    {
        if ($this->isAssignmentOrArrow($code)) {
            return false;
        }

        return in_array($code, [...OperatorHelper::binaryOperators(), T_INLINE_THEN, T_INLINE_ELSE], true);
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
