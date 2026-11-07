<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Functions;

use Jumbo\Helpers\SingleLineHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Scopes;

/**
 * Requires a function, closure or arrow function signature that fits on one line to be written on one line.
 *
 * SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature does this for methods, and Jumbo uses it through
 * Jumbo.Classes.RequireSingleLineMethodSignature, so this sniff covers the rest, including a closure's `use` list, and
 * a compound type in a parameter or return type. The whole resulting line must fit, including what comes after the
 * signature on its line, so the fix can't create a line-length error.
 */
class RequireSingleLineSignatureSniff implements Sniff
{
    public const string CODE_SHOULD_BE_SINGLE_LINE = 'ShouldBeSingleLine';

    /**
     * Maximum line length for a collapsed signature.
     */
    public int $maxLineLength = 120;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_FUNCTION, T_CLOSURE, T_FN];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the function keyword.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$stackPtr]['parenthesis_opener'] ?? null;
        $bodyOpener = $tokens[$stackPtr]['scope_opener'] ?? null;
        if (!is_int($opener) || !is_int($bodyOpener) || Scopes::isOOMethod($phpcsFile, $stackPtr)) {
            return;
        }

        $end = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($bodyOpener - 1), null, true);
        if ($end === false || $tokens[$opener]['line'] === $tokens[$end]['line']) {
            return;
        }

        $collapsed = SingleLineHelper::collapse($phpcsFile, $opener, $end, $this->maxLineLength);
        if ($collapsed === null) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'A function signature split across lines fits within %s characters, so it must be written on one line.',
            $stackPtr,
            self::CODE_SHOULD_BE_SINGLE_LINE,
            [$this->maxLineLength],
        );
        if ($fix) {
            SingleLineHelper::replace($phpcsFile, $opener, $end, $collapsed);
        }
    }
}
