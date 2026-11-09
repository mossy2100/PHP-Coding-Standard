<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Formatting;

use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a trailing comma in a multi-line `match`, group `use`, `list()`, or the arguments of an anonymous class,
 * `clone()`, `exit()` or `die()`, and disallows one on a single line, including in a `match` arm's conditions.
 *
 * PER Coding Style 3.1, section 2.6, says that for any comma-separated list, including `match` arms and group use
 * statements, the last item MUST have a trailing comma if the list is split across lines, and MUST NOT if it's on one.
 * Slevomat's trailing comma sniffs cover function calls, declarations and closure `use` lists, but skip `new class (`,
 * and `clone`, `exit` and `die`, which PHP 8.4 and 8.5 made function-like.
 * Slevomat's array sniff covers arrays and short lists, as in `[$a, $b] = $c;`, but not `list()`, and
 * Universal.Attributes.TrailingComma covers attribute blocks. Nothing else covers these. A trailing comma in `list()`
 * is an empty slot at the end, which PHP ignores, so adding or removing one doesn't change the code.
 *
 * Like Slevomat's call sniffs, this doesn't require a comma in an anonymous class's arguments, or `clone()`'s,
 * `exit()`'s or `die()`'s, when the closing parenthesis follows the last one on its line, as in
 * `new class (function () {\n}) {`. Parentheses after `clone` followed by `->`, `?->`, `::` or `[` may only group an
 * expression, so they're skipped. A `match` arm's conditions are only checked on one line, so a split list of
 * conditions isn't required to have a comma before `=>`. Set disallowInSplitMatchConditions to disallow one there too,
 * as the Jumbo standard does.
 */
class TrailingCommaSniff implements Sniff
{
    public const string CODE_MISSING = 'Missing';

    public const string CODE_DISALLOWED = 'Disallowed';

    /**
     * Whether to disallow a comma before `=>` when a `match` arm's conditions are split over lines. PER's rule for a
     * split list would require one, but its example only has conditions on one line, so by default it isn't checked.
     */
    public bool $disallowInSplitMatchConditions = false;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_MATCH, T_OPEN_USE_GROUP, T_ANON_CLASS, T_LIST, T_CLONE, T_EXIT];
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
        $code = $tokens[$stackPtr]['code'];
        if ($code === T_ANON_CLASS || $code === T_CLONE || $code === T_EXIT) {
            $kind = $code === T_ANON_CLASS ? 'anonymous class' : strtolower($tokens[$stackPtr]['content']) . '()';
            $this->processArguments($phpcsFile, $stackPtr, $kind . ' argument list');
            return;
        }

        if ($tokens[$stackPtr]['code'] === T_MATCH) {
            $opener = $tokens[$stackPtr]['scope_opener'] ?? null;
            $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
            $kind = 'match';
        } elseif ($tokens[$stackPtr]['code'] === T_LIST) {
            $opener = $tokens[$stackPtr]['parenthesis_opener'] ?? null;
            $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
            $kind = 'list()';
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
     * Checks the arguments of an anonymous class, `clone`, `exit` or `die`, if there are parentheses after it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the `class`, `clone`, `exit` or `die` keyword.
     * @param string $kind The kind of list, for the error message.
     */
    private function processArguments(File $phpcsFile, int $stackPtr, string $kind): void
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

        // After clone, parentheses followed by an access may only group the object, as in `clone ($a)->b`.
        $after = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closer + 1), null, true);
        $accesses = [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_OPEN_SQUARE_BRACKET];
        $isAccess = $after !== false && in_array($tokens[$after]['code'], $accesses, true);
        if ($tokens[$stackPtr]['code'] === T_CLONE && $isAccess) {
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

        $this->checkList($phpcsFile, $opener, $closer, $kind);
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
                    && ($this->disallowInSplitMatchConditions || $tokens[$last]['line'] === $tokens[$armStart]['line'])
                ) {
                    $fix = $phpcsFile->addFixableError(
                        'The last item in a %s must not have a trailing comma',
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
            $jumpTo = TokenHelper::findCloser($tokens[$i]);
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
