<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Formatting;

use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\MatchHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a trailing comma in a multi-line `match`, group `use`, `list()`, or the arguments of an anonymous class,
 * `clone()`, `exit()`, `die()` or a call through an expression, as in `$handlers[$name](`, and disallows one on a
 * single line, including in a `match` arm's conditions.
 *
 * PER Coding Style 3.1, section 2.6, says that for any comma-separated list, including `match` arms and group use
 * statements, the last item MUST have a trailing comma if the list is split across lines, and MUST NOT if it's on one.
 * Slevomat's trailing comma sniffs cover function calls, declarations and closure `use` lists, but skip `new class (`,
 * and `clone`, `exit` and `die`, which PHP 8.4 and 8.5 made function-like. They also skip a call unless a name, a
 * variable or a `)` comes before its `(`, so they miss `$handlers[$name](`, `[$object, 'method'](`,
 * `$object->{$method}(` and `'strlen'(`.
 * Slevomat's array sniff covers arrays and short lists, as in `[$a, $b] = $c;`, but not `list()`, and
 * Universal.Attributes.TrailingComma covers attribute blocks. PhpCollective.PHP.DisallowTrailingCommaInSingleLine
 * reports a comma in some of these on one line, but it goes by the comma's line, not the list's, so it also reports a
 * comma PER requires in a split list. Nothing else covers these. A trailing comma in `list()` is an empty slot at the
 * end, which PHP ignores, so adding or removing one doesn't change the code.
 *
 * Like Slevomat's call sniffs, this doesn't require a comma in an anonymous class's arguments, or `clone()`'s,
 * `exit()`'s, `die()`'s or such a call's, when the closing parenthesis follows the last one on its line, as in
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
        return [T_MATCH, T_OPEN_USE_GROUP, T_ANON_CLASS, T_LIST, T_CLONE, T_EXIT, T_OPEN_PARENTHESIS];
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
        if ($code === T_OPEN_PARENTHESIS) {
            $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
            if (is_int($closer) && $this->isExpressionCall($phpcsFile, $stackPtr)) {
                $this->checkArguments($phpcsFile, $stackPtr, $closer, 'function call argument list');
            }

            return;
        }

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

        $this->checkList($phpcsFile, $opener, $closer, $kind, $tokens[$opener]['line'] !== $tokens[$closer]['line']);
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
        if ($tokens[$stackPtr]['code'] === T_CLONE) {
            // Parentheses followed by an access may only group the object, as in `clone ($a)->b`.
            $parentheses = TokenHelper::findCloneArguments($phpcsFile, $stackPtr);
            if ($parentheses === null) {
                return;
            }

            [$opener, $closer] = $parentheses;
        } else {
            $opener = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
            if ($opener === false || $tokens[$opener]['code'] !== T_OPEN_PARENTHESIS) {
                return;
            }

            $closer = $tokens[$opener]['parenthesis_closer'] ?? null;
            if (!is_int($closer)) {
                return;
            }
        }

        $this->checkArguments($phpcsFile, $opener, $closer, $kind);
    }

    /**
     * Check whether parentheses hold the arguments of a call that Slevomat's call sniffs skip: one through an array
     * element, an array callable, a member named by an expression, or a string.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the opening parenthesis.
     * @return bool True if it's such a call, and not a first-class callable.
     */
    private function isExpressionCall(File $phpcsFile, int $opener): bool
    {
        $tokens = $phpcsFile->getTokens();
        $before = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($opener - 1), null, true);
        if ($before === false) {
            return false;
        }

        $code = $tokens[$before]['code'];
        $callees = [T_CLOSE_SQUARE_BRACKET, T_CLOSE_SHORT_ARRAY, T_CONSTANT_ENCAPSED_STRING, T_DOUBLE_QUOTED_STRING];
        if ($code === T_CLOSE_CURLY_BRACKET) {
            // Only the brace of `->{$name}`, `::{$name}` or `${$name}`. A block's `}` can come before a statement
            // that starts with `(`.
            if (!TokenHelper::isNameBrace($phpcsFile, $before)) {
                return false;
            }
        } elseif (!in_array($code, $callees, true)) {
            return false;
        }

        return TokenHelper::findCallableEllipsis($phpcsFile, $opener) === null;
    }

    /**
     * Checks the last argument in a pair of parentheses.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the opening parenthesis.
     * @param int $closer The position of the closing parenthesis.
     * @param string $kind The kind of list, for the error message.
     */
    private function checkArguments(File $phpcsFile, int $opener, int $closer, string $kind): void
    {
        $tokens = $phpcsFile->getTokens();

        // As in a call, a single argument split over lines, as in `clone($a, [` ... `]);`, doesn't split the list. A
        // closing parenthesis after the last argument of a badly split list is left to the layout sniff.
        $isSplit = LayoutHelper::isSplitList($phpcsFile, $opener, $closer);
        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($closer - 1), $opener, true);
        if ($isSplit && $last !== false && $tokens[$last]['line'] === $tokens[$closer]['line']) {
            return;
        }

        $this->checkList($phpcsFile, $opener, $closer, $kind, $isSplit);
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
        foreach (MatchHelper::findArms($phpcsFile, $opener, $closer) as $arm) {
            $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($arm['arrow'] - 1), $arm['start'], true);
            if (
                $last === false
                || $tokens[$last]['code'] !== T_COMMA
                || (!$this->disallowInSplitMatchConditions && $tokens[$last]['line'] !== $tokens[$arm['start']]['line'])
            ) {
                continue;
            }

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
    }

    /**
     * Checks the last item of a list: it needs a trailing comma if the list is split across lines, and can't have one
     * if it isn't.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the list's opening bracket.
     * @param int $closer The position of the list's closing bracket.
     * @param string $kind The kind of list, for the error message.
     * @param bool $isSplit Whether the list is split across lines.
     */
    private function checkList(File $phpcsFile, int $opener, int $closer, string $kind, bool $isSplit): void
    {
        $tokens = $phpcsFile->getTokens();
        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($closer - 1), $opener, true);
        if ($last === false || $last === $opener) {
            return;
        }

        $hasComma = $tokens[$last]['code'] === T_COMMA;
        if ($isSplit) {
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
            $error = $tokens[$opener]['line'] === $tokens[$closer]['line']
                ? 'The last item in a single-line %s must not have a trailing comma'
                : 'The last item in the %s must not have a trailing comma, as the list isn\'t split across lines';
            $fix = $phpcsFile->addFixableError($error, $last, self::CODE_DISALLOWED, [$kind]);
            if ($fix) {
                $phpcsFile->fixer->replaceToken($last, '');
            }
        }
    }
}
