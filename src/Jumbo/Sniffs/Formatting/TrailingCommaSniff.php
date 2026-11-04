<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Formatting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a trailing comma in a multi-line `match` or group `use`, and disallows one on a single line.
 *
 * PER Coding Style 3.1, section 2.6, says that for any comma-separated list, including `match` arms and group use
 * statements, the last item MUST have a trailing comma if the list is split across lines, and MUST NOT if it's on one.
 * Slevomat's trailing comma sniffs cover function calls, declarations and closure `use` lists, and Squiz's array
 * sniff covers arrays, but nothing covers these two.
 */
class TrailingCommaSniff implements Sniff
{
    public const string CODE_MISSING = 'Missing';

    public const string CODE_DISALLOWED = 'Disallowed';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_MATCH, T_OPEN_USE_GROUP];
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
        if ($tokens[$stackPtr]['code'] === T_MATCH) {
            $opener = $tokens[$stackPtr]['scope_opener'] ?? null;
            $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
            $kind = 'match';
        } else {
            $opener = $stackPtr;
            $found = $phpcsFile->findNext(T_CLOSE_USE_GROUP, ($stackPtr + 1));
            $closer = $found === false ? null : $found;
            $kind = 'group use';
        }

        if (!is_int($opener) || !is_int($closer)) {
            return;
        }

        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($closer - 1), $opener, true);
        if ($last === false || $last === $opener) {
            return;
        }

        $hasComma = $tokens[$last]['code'] === T_COMMA;
        if ($tokens[$opener]['line'] !== $tokens[$closer]['line']) {
            if (!$hasComma) {
                $fix = $phpcsFile->addFixableError(
                    'The last item in a multi-line %s must have a trailing comma',
                    $last,
                    self::CODE_MISSING,
                    [$kind],
                );
                if ($fix) {
                    $phpcsFile->fixer->addContent($last, ',');
                }
            }

            return;
        }

        if ($hasComma) {
            $fix = $phpcsFile->addFixableError(
                'The last item in a single-line %s must not have a trailing comma',
                $last,
                self::CODE_DISALLOWED,
                [$kind],
            );
            if ($fix) {
                $phpcsFile->fixer->replaceToken($last, '');
            }
        }
    }
}
