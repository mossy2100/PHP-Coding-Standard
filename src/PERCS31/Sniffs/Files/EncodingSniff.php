<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires a file to be encoded in UTF-8.
 *
 * PSR-1, section 2.2, which PER Coding Style 3.1 includes, says PHP code MUST use only UTF-8 without BOM.
 * Generic.Files.ByteOrderMark reports the byte-order mark, but no sniff in PHP_CodeSniffer, Slevomat, PHPCSExtra or
 * php-collective checks the encoding itself. Drupal.Files.FileEncoding does, as a warning, but it isn't worth a
 * dependency on Drupal's standard.
 *
 * The file is reported once, at the first line with a byte sequence that isn't valid UTF-8. There's no fix, as the
 * file's real encoding can't be known.
 */
class EncodingSniff implements Sniff
{
    public const string CODE_NOT_UTF8 = 'NotUtf8';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_INLINE_HTML];
    }

    /**
     * Processes this test, when one of its tokens is encountered. It checks the whole file at the first one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return int The position to continue from, which is the end of the file.
     */
    public function process(File $phpcsFile, int $stackPtr): int
    {
        if (self::isUtf8($phpcsFile->getTokensAsString(0, $phpcsFile->numTokens, true))) {
            return $phpcsFile->numTokens;
        }

        // Find the first token with invalid bytes, so the error is on their line.
        $tokens = $phpcsFile->getTokens();
        $invalid = 0;
        foreach ($tokens as $ptr => $token) {
            if (!self::isUtf8($token['orig_content'] ?? $token['content'])) {
                $invalid = $ptr;
                break;
            }
        }

        $phpcsFile->addError('A file must be encoded in UTF-8', $invalid, self::CODE_NOT_UTF8);

        return $phpcsFile->numTokens;
    }

    /**
     * Check whether a string is valid UTF-8. A pattern with the `u` modifier only matches a valid subject.
     *
     * @param string $content The string.
     * @return bool True if it is.
     */
    private static function isUtf8(string $content): bool
    {
        return preg_match('//u', $content) === 1;
    }
}
