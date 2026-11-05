<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Formatting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a trailing comma in a multi-line `match`, group `use` or anonymous class's arguments, and disallows one on a
 * single line, including in a `match` arm's conditions.
 *
 * PER Coding Style 3.1, section 2.6, says that for any comma-separated list, including `match` arms and group use
 * statements, the last item MUST have a trailing comma if the list is split across lines, and MUST NOT if it's on one.
 * Slevomat's trailing comma sniffs cover function calls, declarations and closure `use` lists, but skip `new class (`.
 * Slevomat's array sniff covers arrays, and Universal.Attributes.TrailingComma covers attribute blocks. Nothing else
 * covers these.
 *
 * Like Slevomat's call sniffs, this doesn't require a comma in an anonymous class's arguments when the closing
 * parenthesis follows the last one on its line, as in `new class (function () {\n}) {`. A `match` arm's conditions are
 * only checked on one line, so a split list of conditions isn't required to have a comma before `=>`.
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
        return [T_MATCH, T_OPEN_USE_GROUP, T_ANON_CLASS];
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
        if ($tokens[$stackPtr]['code'] === T_ANON_CLASS) {
            $this->processAnonymousClass($phpcsFile, $stackPtr);
            return;
        }

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

        if ($kind === 'match') {
            $this->checkMatchConditions($phpcsFile, $opener, $closer);
        }

        $this->checkList($phpcsFile, $opener, $closer, $kind);
    }

    /**
     * Checks the arguments of an anonymous class.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the `class` keyword.
     */
    private function processAnonymousClass(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if ($opener === false || $tokens[$opener]['code'] !== T_OPEN_PARENTHESIS) {
            return;
        }

        $closer = $tokens[$opener]['parenthesis_closer'] ?? null;
        if (!is_int($closer)) {
            return;
        }

        // As in a call, a closing parenthesis straight after a multi-line last argument isn't a split list.
        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($closer - 1), $opener, true);
        if (
            $last !== false
            && $tokens[$opener]['line'] !== $tokens[$closer]['line']
            && $tokens[$last]['line'] === $tokens[$closer]['line']
        ) {
            return;
        }

        $this->checkList($phpcsFile, $opener, $closer, 'anonymous class argument list');
    }

    /**
     * Disallows a trailing comma in a `match` arm's conditions, when they're on one line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the `match` body's opening brace.
     * @param int $closer The position of the `match` body's closing brace.
     */
    private function checkMatchConditions(File $phpcsFile, int $opener, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        $armStart = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($opener + 1), $closer, true);
        for ($i = ($opener + 1); $i < $closer; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_MATCH_ARROW && $armStart !== false) {
                $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($i - 1), $armStart, true);
                if (
                    $last !== false
                    && $tokens[$last]['code'] === T_COMMA
                    && $tokens[$last]['line'] === $tokens[$armStart]['line']
                ) {
                    $fix = $phpcsFile->addFixableError(
                        'The last item in a single-line %s must not have a trailing comma',
                        $last,
                        self::CODE_DISALLOWED,
                        ['match condition list'],
                    );
                    if ($fix) {
                        $phpcsFile->fixer->replaceToken($last, '');
                    }
                }

                $armStart = false;
            } elseif ($code === T_COMMA && $armStart === false) {
                // The comma that ends an arm's body.
                $armStart = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), $closer, true);
            }

            // Commas and arrows inside brackets aren't this match's.
            $jumpTo = $tokens[$i]['parenthesis_closer'] ?? $tokens[$i]['bracket_closer']
                ?? $tokens[$i]['attribute_closer'] ?? null;
            if (is_int($jumpTo) && $jumpTo > $i && $jumpTo < $closer) {
                $i = $jumpTo;
            }
        }
    }

    /**
     * Checks the last item of a list: it needs a trailing comma if the list is on several lines, and can't have one if
     * it's on one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the list's opening bracket.
     * @param int $closer The position of the list's closing bracket.
     * @param string $kind The kind of list, for the error message.
     */
    private function checkList(File $phpcsFile, int $opener, int $closer, string $kind): void
    {
        $tokens = $phpcsFile->getTokens();
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
