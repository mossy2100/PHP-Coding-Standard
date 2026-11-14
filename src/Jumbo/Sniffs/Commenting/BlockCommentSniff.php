<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Commenting;

use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks the layout of a block comment, `/* ... *\/`.
 *
 * PER Coding Style 3.1 has no rule for comments, so this is an extra rule. A docblock, `/** ... *\/`, isn't checked.
 *
 * In a block comment over several lines, each line after the first is indented at least as far as the line the `/*`
 * is on. A blank line is allowed. A `*\/` on a line by itself lines up with the `/*`. Text may share a line with the
 * `/*` or the `*\/`. Where the `/*` itself goes is checked by PERCS31.WhiteSpace.ScopeIndent, as for a statement. A
 * comment with a `*` starting each line is allowed, and its `*\/` may be one column to the right, under the stars.
 *
 * A block comment on one line has one space after `/*` and one before `*\/`, as in `/* Text *\/`. With code before it
 * on the line, it has one space before it, and with code after it, one space after it. The space between a comment and
 * a bracket, comma or operator beside it, or a semicolon after it, isn't checked here, as the sniffs for those report
 * it.
 *
 * The fix corrects the spaces and the indents.
 */
class BlockCommentSniff implements Sniff
{
    public const string CODE_CLOSER_NOT_ALIGNED = 'CloserNotAligned';

    public const string CODE_CONTENT_INDENT = 'ContentIndent';

    public const string CODE_SPACING_INSIDE = 'SpacingInside';

    public const string CODE_SPACING_BEFORE = 'SpacingBefore';

    public const string CODE_SPACING_AFTER = 'SpacingAfter';

    /**
     * Tokens whose own sniffs check the space between them and a comment: brackets, commas and semicolons.
     *
     * @var list<int|string>
     */
    private const array PUNCTUATION = [
        T_OPEN_PARENTHESIS,
        T_OPEN_SQUARE_BRACKET,
        T_OPEN_SHORT_ARRAY,
        T_COMMA,
        T_SEMICOLON,
        T_CLOSE_PARENTHESIS,
        T_CLOSE_SQUARE_BRACKET,
        T_CLOSE_SHORT_ARRAY,
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_COMMENT];
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
        $content = $tokens[$stackPtr]['content'];
        if (!str_starts_with($content, '/*') || str_starts_with($content, '/**')) {
            return;
        }

        if (str_contains($content, '*/')) {
            $this->checkOneLine($phpcsFile, $stackPtr);

            return;
        }

        // Each line of a comment over several lines is a token of its own.
        $last = $stackPtr;
        while (
            isset($tokens[($last + 1)])
            && $tokens[($last + 1)]['code'] === T_COMMENT
            && !str_contains($tokens[$last]['content'], '*/')
        ) {
            $last++;
        }

        if (str_contains($tokens[$last]['content'], '*/')) {
            $this->checkLines($phpcsFile, $stackPtr, $last);
        }
    }

    /**
     * Check a block comment over several lines.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $first The position of the comment's first line.
     * @param int $last The position of the comment's last line.
     */
    private function checkLines(File $phpcsFile, int $first, int $last): void
    {
        $tokens = $phpcsFile->getTokens();
        $lineStart = $phpcsFile->findFirstOnLine(T_WHITESPACE, $first, true);
        assert(is_int($lineStart));

        // The stars of a comment with one on each line are a column to the right of the /*, and its closer may be too.
        $column = $tokens[$lineStart]['column'];
        $hasStars = true;
        for ($i = ($first + 1); $i < $last; $i++) {
            $text = trim($tokens[$i]['content']);
            $hasStars = $hasStars && ($text === '' || $text[0] === '*');
        }

        $isCloserAlone = trim($tokens[$last]['content']) === '*/';
        $end = $isCloserAlone ? $last : ($last + 1);
        for ($i = ($first + 1); $i < $end; $i++) {
            $line = $tokens[$i]['content'];
            $indent = strlen($line) - strlen(ltrim($line, ' '));
            if (trim($line) === '' || $indent >= ($column - 1)) {
                continue;
            }

            $error = 'Block comment line indented incorrectly; expected at least %s spaces, found %s';
            $data = [($column - 1), $indent];
            if ($phpcsFile->addFixableError($error, $i, self::CODE_CONTENT_INDENT, $data)) {
                $phpcsFile->fixer->replaceToken($i, str_repeat(' ', ($column - 1)) . ltrim($line, ' '));
            }
        }

        if (!$isCloserAlone) {
            return;
        }

        $closer = $tokens[$last]['content'];
        $indent = strlen($closer) - strlen(ltrim($closer, ' '));
        $expected = $column - 1;
        if ($indent === $expected || ($hasStars && $last > ($first + 1) && $indent === $column)) {
            return;
        }

        $error = 'The */ of a block comment must line up with its /*; expected %s spaces, found %s';
        if ($phpcsFile->addFixableError($error, $last, self::CODE_CLOSER_NOT_ALIGNED, [$expected, $indent])) {
            $phpcsFile->fixer->replaceToken($last, str_repeat(' ', $expected) . ltrim($closer, ' '));
        }
    }

    /**
     * Check a block comment on one line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the comment.
     */
    private function checkOneLine(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $content = rtrim($tokens[$stackPtr]['content']);
        $text = trim(substr($content, 2, -2));
        if ($text !== '' && $content !== "/* $text */") {
            $error = 'A block comment on one line must have one space after /* and one before */';
            if ($phpcsFile->addFixableError($error, $stackPtr, self::CODE_SPACING_INSIDE)) {
                $rest = substr($tokens[$stackPtr]['content'], strlen($content));
                $phpcsFile->fixer->replaceToken($stackPtr, "/* $text */" . $rest);
            }
        }

        $line = $tokens[$stackPtr]['line'];
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        $isBeside = $previous !== false && $tokens[$previous]['line'] === $line;
        if ($isBeside && !$this->isCheckedElsewhere($tokens[$previous]['code'], true)) {
            $this->checkSpace($phpcsFile, $previous, $stackPtr, true);
        }

        $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        $isBeside = $next !== false && $tokens[$next]['line'] === $line;
        if ($isBeside && !$this->isCheckedElsewhere($tokens[$next]['code'], false)) {
            $this->checkSpace($phpcsFile, $stackPtr, $next, false);
        }
    }

    /**
     * Check whether the space beside a token is another sniff's to check.
     *
     * @param int|string $code The code of the token beside the comment.
     * @param bool $isBefore Whether the token is before the comment. No sniff checks the space after a semicolon.
     * @return bool True if it is.
     */
    private function isCheckedElsewhere(int|string $code, bool $isBefore): bool
    {
        if ($isBefore && $code === T_SEMICOLON) {
            return false;
        }

        return $code === T_OPEN_TAG
            || in_array($code, self::PUNCTUATION, true)
            || isset(Tokens::OPERATORS[$code])
            || isset(Tokens::ASSIGNMENT_TOKENS[$code])
            || isset(Tokens::COMPARISON_TOKENS[$code])
            || isset(Tokens::BOOLEAN_OPERATORS[$code]);
    }

    /**
     * Check the space between a block comment and the code beside it on its line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $from The position of the first of the two tokens.
     * @param int $to The position of the second.
     * @param bool $isBefore Whether the space is before the comment.
     */
    private function checkSpace(File $phpcsFile, int $from, int $to, bool $isBefore): void
    {
        $tokens = $phpcsFile->getTokens();
        $found = $to === ($from + 1) ? '' : $tokens[($from + 1)]['content'];
        if ($found === ' ') {
            return;
        }

        $error = $isBefore
            ? 'Expected 1 space before a block comment; found %s'
            : 'Expected 1 space after a block comment; found %s';
        $code = $isBefore ? self::CODE_SPACING_BEFORE : self::CODE_SPACING_AFTER;
        $comment = $isBefore ? $to : $from;
        if ($phpcsFile->addFixableError($error, $comment, $code, [strlen($found)])) {
            FixerHelper::setBetween($phpcsFile, $from, $to, ' ');
        }
    }
}
