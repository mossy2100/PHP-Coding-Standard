<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineEndingsSniff as GenericSniff;

/**
 * Ensures every line of a file ends in the expected line ending, not only the first.
 *
 * PER Coding Style 3.1, section 2.2, says "All PHP files MUST use the Unix LF (linefeed) line ending only".
 * Generic.Files.LineEndings only compares the line ending PHP_CodeSniffer found on the file's first line. If that one
 * is right, a CRLF on a later line passes, with only an internal warning that the file has mixed line endings. One
 * after code or a comment was reported as trailing white space, and fixed, but one in a string, a heredoc or markup
 * outside the PHP tags wasn't reported or fixed at all.
 *
 * When the first line ending is wrong, this sniff leaves the file to the parent, which reports it once and fixes every
 * line. Otherwise, it reports each line that ends in the wrong line ending, as InvalidEOLChar, wherever it is, and
 * fixes it the way the parent does, so the result is the same as for a file that was wrong from its first line.
 *
 * In a docblock, PHP_CodeSniffer puts the CR of a CRLF at the end of one token, and the LF in the next, so the two
 * tokens are read together.
 */
class LineEndingsSniff extends GenericSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return int The position to skip to, which is the end of the file.
     */
    public function process(File $phpcsFile, int $stackPtr): int
    {
        $result = parent::process($phpcsFile, $stackPtr);
        $expected = str_replace(['\n', '\r'], ["\n", "\r"], $this->eolChar);
        if ($phpcsFile->eolChar !== $expected) {
            return $result;
        }

        $tokens = $phpcsFile->getTokens();
        $error = 'End of line character is invalid; expected "%s" but found "%s"';
        for ($i = 0; $i < $phpcsFile->numTokens; $i++) {
            // Only the last token on a line holds its line ending.
            if (isset($tokens[($i + 1)]) && $tokens[($i + 1)]['line'] <= $tokens[$i]['line']) {
                continue;
            }

            $content = $tokens[$i]['orig_content'] ?? $tokens[$i]['content'];
            $trimmed = rtrim($content, "\r\n");
            $found = substr($content, strlen($trimmed));

            // In a docblock, the CR of a CRLF ends the token before the one that holds the LF.
            $carrier = null;
            if ($trimmed === '' && $found === "\n" && $i > 0 && $tokens[($i - 1)]['line'] === $tokens[$i]['line']) {
                $before = $tokens[($i - 1)]['orig_content'] ?? $tokens[($i - 1)]['content'];
                if (str_ends_with($before, "\r")) {
                    $carrier = ($i - 1);
                    $found = "\r\n";
                }
            }

            if ($found === '' || $found === $expected) {
                continue;
            }

            $data = [$this->eolChar, str_replace(["\n", "\r"], ['\n', '\r'], $found)];
            if (!$phpcsFile->addFixableError($error, $i, 'InvalidEOLChar', $data)) {
                continue;
            }

            if ($carrier === null) {
                $phpcsFile->fixer->replaceToken($i, $trimmed . $expected);
            } else {
                $before = $tokens[$carrier]['orig_content'] ?? $tokens[$carrier]['content'];
                $phpcsFile->fixer->replaceToken($carrier, rtrim($before, "\r"));
                $phpcsFile->fixer->replaceToken($i, $expected);
            }
        }

        return $phpcsFile->numTokens;
    }
}
