<?php

declare(strict_types=1);

namespace PERCS31\Helpers;

use PHP_CodeSniffer\Files\File;

/**
 * Helper class for common fixes.
 */
class FixerHelper
{
    /**
     * Report an error that can be fixed unless something is in the way, such as a comment that the fix would lose.
     *
     * @param File $phpcsFile The file being scanned.
     * @param bool $isFixable Whether the error can be fixed.
     * @param string $error The error message.
     * @param int $stackPtr The position of the token the error is on.
     * @param string $code The error's code.
     * @param array<int|string, mixed> $data The values for the message's placeholders.
     * @return bool True if the error is to be fixed now.
     */
    public static function addFixableErrorIf(
        File $phpcsFile,
        bool $isFixable,
        string $error,
        int $stackPtr,
        string $code,
        array $data = [],
    ): bool {
        if (!$isFixable) {
            $phpcsFile->addError($error, $stackPtr, $code, $data);

            return false;
        }

        return $phpcsFile->addFixableError($error, $stackPtr, $code, $data);
    }

    /**
     * Empty every token between two tokens, not including them.
     *
     * It doesn't start a changeset, so it can be used inside one. PHP_CodeSniffer's beginChangeset() drops the changes
     * of a changeset that's already open.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $from The token before the gap.
     * @param int $to The token after the gap.
     */
    public static function blankBetween(File $phpcsFile, int $from, int $to): void
    {
        for ($i = ($from + 1); $i < $to; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }
    }

    /**
     * Replace everything between two tokens with the given content.
     *
     * Like blankBetween(), it doesn't start a changeset, so several gaps can be set in one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $from The token before the gap.
     * @param int $to The token after the gap.
     * @param string $content The content for the gap, such as whitespace.
     */
    public static function setBetween(File $phpcsFile, int $from, int $to, string $content): void
    {
        self::blankBetween($phpcsFile, $from, $to);
        $phpcsFile->fixer->addContent($from, $content);
    }

    /**
     * Replace everything between two tokens with the given content, in a changeset of its own.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $from The token before the gap.
     * @param int $to The token after the gap.
     * @param string $content The content for the gap, such as whitespace.
     */
    public static function replaceBetween(File $phpcsFile, int $from, int $to, string $content): void
    {
        $phpcsFile->fixer->beginChangeset();
        self::setBetween($phpcsFile, $from, $to, $content);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Set the indent of a token that starts its line, replacing the whitespace before it or adding some.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the token.
     * @param int $spaces The number of spaces to indent it by.
     */
    public static function setIndent(File $phpcsFile, int $ptr, int $spaces): void
    {
        $tokens = $phpcsFile->getTokens();
        $before = $ptr - 1;
        if ($tokens[$before]['code'] === T_WHITESPACE && $tokens[$before]['line'] === $tokens[$ptr]['line']) {
            $phpcsFile->fixer->replaceToken($before, str_repeat(' ', $spaces));
        } else {
            $phpcsFile->fixer->addContentBefore($ptr, str_repeat(' ', $spaces));
        }
    }
}
