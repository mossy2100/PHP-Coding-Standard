<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\ControlStructures;

use Mossy2100\Helpers\SingleLineHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires a multi-line `foreach`, `for`, `switch` or `match` header to be written on one line if it fits.
 *
 * SlevomatCodingStandard.ControlStructures.RequireSingleLineCondition does this for `if`, `elseif`, `while` and
 * `do-while`, but not for these. A multi-line header is often left over after the array it contains is collapsed.
 *
 * A header fits if the whole resulting line does: the text before the opening parenthesis, the collapsed header, and
 * the text after the closing parenthesis on its line, such as ` {`. This means the fix can never create a line-length
 * error. See SingleLineHelper for the headers that are never collapsed, such as those containing a comment.
 */
class RequireSingleLineHeaderSniff implements Sniff
{
    public const string CODE_SHOULD_BE_SINGLE_LINE = 'ShouldBeSingleLine';

    /**
     * Maximum line length for a collapsed header.
     */
    public int $maxLineLength = 120;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_FOREACH, T_FOR, T_SWITCH, T_MATCH];
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
            [$tokens[$stackPtr]['content'], $this->maxLineLength]
        );
        if ($fix) {
            SingleLineHelper::replace($phpcsFile, $openPtr, $closePtr, $collapsed);
        }
    }
}
