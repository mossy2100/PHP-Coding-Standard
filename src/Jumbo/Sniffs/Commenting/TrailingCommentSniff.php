<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Commenting;

use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires a single space between code and a `//` or `#` comment after it on the same line.
 *
 * PER Coding Style 3.1 has no rule for comments, so this is an extra rule. `$a = 1;// Text` and `$a = 1;    // Text`
 * are reported, and fixed to `$a = 1; // Text`. So comments after code aren't lined up in a column.
 *
 * A comment on a line of its own isn't checked here, as PERCS31.WhiteSpace.ScopeIndent places it. Neither is a block
 * comment, which Jumbo.Commenting.BlockComment checks, or a comment after a PHP open tag.
 */
class TrailingCommentSniff implements Sniff
{
    public const string CODE_SPACING_BEFORE = 'SpacingBefore';

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
        if (!str_starts_with($content, '//') && !str_starts_with($content, '#')) {
            return;
        }

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if (
            $previous === false
            || $tokens[$previous]['line'] !== $tokens[$stackPtr]['line']
            || $tokens[$previous]['code'] === T_OPEN_TAG
        ) {
            return;
        }

        $found = $previous === ($stackPtr - 1) ? '' : $tokens[($stackPtr - 1)]['content'];
        if ($found === ' ') {
            return;
        }

        $error = 'Expected 1 space before a comment after code; found %s';
        if ($phpcsFile->addFixableError($error, $stackPtr, self::CODE_SPACING_BEFORE, [strlen($found)])) {
            FixerHelper::setBetween($phpcsFile, $previous, $stackPtr, ' ');
        }
    }
}
