<?php

declare(strict_types=1);

namespace Mossy2100\Helpers;

use PHP_CodeSniffer\Files\File;

/**
 * Helper class for collapsing a multi-line bracketed construct, such as an array or a control structure header, onto
 * one line.
 *
 * The collapsed text has single spaces between tokens, no space inside brackets or parentheses, and no trailing commas.
 *
 * A construct is never collapsed if it contains:
 * - A comment, which would be lost or would comment out the rest of the line.
 * - A heredoc, nowdoc, or multi-line string, whose line breaks are part of the value.
 * - A brace body, such as a closure, anonymous class or match expression, which is harder to read on one line.
 */
class SingleLineHelper
{
    /**
     * Tokens that open a bracketed group, so no space follows them.
     *
     * @var list<int|string>
     */
    private const array OPENERS = [T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET, T_OPEN_PARENTHESIS, T_ATTRIBUTE];

    /**
     * Tokens that close a bracketed group, so no space or trailing comma comes before them.
     *
     * @var list<int|string>
     */
    private const array CLOSERS = [T_CLOSE_SHORT_ARRAY, T_CLOSE_SQUARE_BRACKET, T_CLOSE_PARENTHESIS];

    /**
     * Tokens that stop a construct from being collapsed.
     *
     * @var list<int|string>
     */
    private const array BLOCKERS = [
        T_COMMENT,
        T_DOC_COMMENT_OPEN_TAG,
        T_PHPCS_DISABLE,
        T_PHPCS_ENABLE,
        T_PHPCS_IGNORE,
        T_PHPCS_IGNORE_FILE,
        T_PHPCS_SET,
        T_START_HEREDOC,
        T_START_NOWDOC,
        T_OPEN_CURLY_BRACKET,
    ];

    /**
     * Get the tokens from an opening to a closing bracket written on one line, if they can be collapsed and the
     * resulting line fits.
     *
     * The whole resulting line must fit: the text before the opening bracket, the collapsed text, and the text after
     * the closing bracket on its line. This means a fix can never create a line-length error.
     *
     * If the line starts with the closer of an earlier multi-line array that will also be collapsed, this one is left
     * for the next fixer pass. Both would be fixed in the same pass, and the joined line could be too long.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openPtr The position of the opening bracket.
     * @param int $closePtr The position of the closing bracket.
     * @param int $maxLineLength The maximum line length.
     * @return ?string The collapsed text, or null if it can't be collapsed or doesn't fit.
     */
    public static function collapse(File $phpcsFile, int $openPtr, int $closePtr, int $maxLineLength): ?string
    {
        $tokens = $phpcsFile->getTokens();

        // Defer to the next pass if an earlier array on this line will be collapsed in this one.
        $firstPtr = $phpcsFile->findFirstOnLine(T_WHITESPACE, $openPtr, true);
        if ($firstPtr !== false && $firstPtr !== $openPtr && $tokens[$firstPtr]['code'] === T_CLOSE_SHORT_ARRAY) {
            $prevOpenPtr = $tokens[$firstPtr]['bracket_opener'];
            if (
                $tokens[$prevOpenPtr]['line'] !== $tokens[$firstPtr]['line']
                && self::collapse($phpcsFile, $prevOpenPtr, $firstPtr, $maxLineLength) !== null
            ) {
                return null;
            }
        }

        $result = '';
        $pendingSpace = false;
        for ($i = $openPtr; $i <= $closePtr; $i++) {
            $code = $tokens[$i]['code'];
            if (in_array($code, self::BLOCKERS, true)) {
                return null;
            }

            if ($code === T_WHITESPACE) {
                $pendingSpace = true;
                continue;
            }

            // A line break inside any other token, such as a multi-line string, is part of the value.
            if (str_contains($tokens[$i]['content'], "\n")) {
                return null;
            }

            // Drop trailing commas.
            if ($code === T_COMMA) {
                $nextPtr = $phpcsFile->findNext(T_WHITESPACE, $i + 1, $closePtr + 1, true);
                if ($nextPtr !== false && in_array($tokens[$nextPtr]['code'], self::CLOSERS, true)) {
                    continue;
                }
            }

            // One space between tokens, but none just inside brackets and parentheses.
            if ($pendingSpace && $result !== '') {
                $prevCode = $tokens[$phpcsFile->findPrevious(T_WHITESPACE, $i - 1, $openPtr, true)]['code'];
                if (!in_array($prevCode, self::OPENERS, true) && !in_array($code, self::CLOSERS, true)) {
                    $result .= ' ';
                }
            }
            $pendingSpace = false;
            $result .= $tokens[$i]['content'];
        }

        // Measure the whole resulting line.
        $after = '';
        for ($i = $closePtr + 1; isset($tokens[$i]) && !str_contains($tokens[$i]['content'], "\n"); $i++) {
            $after .= $tokens[$i]['content'];
        }
        $lineLength = $tokens[$openPtr]['column'] - 1 + mb_strlen($result) + mb_strlen(rtrim($after));

        return $lineLength <= $maxLineLength ? $result : null;
    }

    /**
     * Replace the tokens from an opening to a closing bracket with collapsed text.
     *
     * @param File $phpcsFile The file being fixed.
     * @param int $openPtr The position of the opening bracket.
     * @param int $closePtr The position of the closing bracket.
     * @param string $collapsed The collapsed text, from collapse().
     */
    public static function replace(File $phpcsFile, int $openPtr, int $closePtr, string $collapsed): void
    {
        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($openPtr, $collapsed);
        for ($i = $openPtr + 1; $i <= $closePtr; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }
        $phpcsFile->fixer->endChangeset();
    }
}
