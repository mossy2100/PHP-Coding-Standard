<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Classes;

use Jumbo\Helpers\SingleLineHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a class, interface or enum header that fits on one line to be written on one line.
 *
 * PER Coding Style 3.1, section 4.1, says a list of `implements` or `extends` MAY be split across lines, so this is an
 * extra rule. The whole resulting line must fit, so the fix can't create a line-length error.
 */
class RequireSingleLineClassHeaderSniff implements Sniff
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
        return [T_CLASS, T_INTERFACE, T_ENUM, T_ANON_CLASS];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the keyword.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $bodyOpener = $tokens[$stackPtr]['scope_opener'] ?? null;
        if (!is_int($bodyOpener)) {
            return;
        }

        $end = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($bodyOpener - 1), null, true);
        if ($end === false || $tokens[$stackPtr]['line'] === $tokens[$end]['line']) {
            return;
        }

        $collapsed = SingleLineHelper::collapse($phpcsFile, $stackPtr, $end, $this->maxLineLength);
        if ($collapsed === null) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'A class header split across lines fits within %s characters, so it must be written on one line.',
            $stackPtr,
            self::CODE_SHOULD_BE_SINGLE_LINE,
            [$this->maxLineLength],
        );
        if ($fix) {
            SingleLineHelper::replace($phpcsFile, $stackPtr, $end, $collapsed);
        }
    }
}
