<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Formatting;

use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\MatchHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\UseStatements;

/**
 * Requires a trailing comma in a multi-line `match`, compound namespace, `list()`, or the arguments of an anonymous
 * class, `clone()`, `exit()`, `die()` or a call through an expression, as in `$handlers[$name](`, and disallows one on
 * a single line, including in a `match` arm's conditions.
 *
 * PER Coding Style 3.1, section 2.6, says that for any comma-separated list, including `match` arms and compound
 * namespace statements, the last item MUST have a trailing comma if the list is split across lines, and MUST NOT if
 * it's on one. Slevomat's trailing comma sniffs cover function calls, declarations and closure `use` lists, but skip
 * `new class (`, and `clone`, `exit` and `die`, which PHP 8.4 and 8.5 made function-like. They also skip a call unless
 * a name, a variable or a `)` comes before its `(`, so they miss `$handlers[$name](`, `[$object, 'method'](`,
 * `$object->{$method}(` and `'strlen'(`. Slevomat's array sniff covers arrays and short lists, as in `[$a, $b] = $c;`,
 * but not `list()`, and Universal.Attributes.TrailingComma covers attribute blocks.
 * PhpCollective.PHP.DisallowTrailingCommaInSingleLine reports a comma in some of these on one line, but it goes by the
 * comma's line, not the list's, so it also reports a comma PER requires in a split list. Nothing else covers these. A
 * trailing comma in `list()` is an empty slot at the end, which PHP ignores, so adding or removing one doesn't change
 * the code.
 *
 * Like Slevomat's call sniffs, this doesn't require a comma in an anonymous class's arguments, or `clone()`'s,
 * `exit()`'s, `die()`'s or such a call's, when the closing parenthesis follows the last one on its line, as in
 * `new class (function () {\n}) {`. Parentheses after `clone` followed by `->`, `?->`, `::` or `[` may only group an
 * expression, so they're skipped.
 *
 * A `match` arm's conditions are a list too, so the same rule applies: no comma before the `=>` when they're on one
 * line, as in `1, 2 => 'a',`, and one when they're split over lines, as in `1,` then `2, => 'a',`. The list is split
 * when it doesn't start and end on the same line, which includes one with a condition that spans lines, such as an
 * array.
 *
 * It also reports a trailing comma with a comment after it, on one line, in a parameter list, a closure's `use` list
 * and an array or short list, as in `[1, 2,` then a block comment then `]`. The sniffs that check those lists look for
 * the comma straight before the closing bracket, so the comment hides it from them. Without a comment, they report it.
 */
class TrailingCommaSniff implements Sniff
{
    public const string CODE_MISSING = 'Missing';

    public const string CODE_DISALLOWED = 'Disallowed';

    /**
     * The tokens that start a list another sniff checks, where this sniff only looks for a comma hidden by a comment.
     */
    private const array LISTS_OTHER_SNIFFS_CHECK = [T_FUNCTION, T_CLOSURE, T_FN, T_USE, T_OPEN_SHORT_ARRAY, T_ARRAY];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [
            T_MATCH,
            T_OPEN_USE_GROUP,
            T_ANON_CLASS,
            T_LIST,
            T_CLONE,
            T_EXIT,
            T_OPEN_PARENTHESIS,
            ...self::LISTS_OTHER_SNIFFS_CHECK,
        ];
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
        if (in_array($code, self::LISTS_OTHER_SNIFFS_CHECK, true)) {
            $this->checkCommaBeforeComment($phpcsFile, $stackPtr);
            return;
        }

        if ($code === T_OPEN_PARENTHESIS) {
            $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
            if (is_int($closer) && $this->isExpressionCall($phpcsFile, $stackPtr)) {
                $this->checkArguments($phpcsFile, $stackPtr, $closer, 'function call argument list');
            }

            return;
        }

        if ($code === T_ANON_CLASS || $code === T_CLONE || $code === T_EXIT) {
            $kind = $code === T_ANON_CLASS ? 'anonymous class' : strtolower($tokens[$stackPtr]['content']) . '()';
            $this->processArguments($phpcsFile, $stackPtr, "{$kind} argument list");
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
            $kind = 'compound namespace';
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
     * Report a trailing comma with a comment after it, in a list on one line that another sniff checks: a parameter
     * list, a closure's `use` list, an array or a short list. The fix removes the comma, and keeps the comment.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token that starts the list, or owns its brackets.
     */
    private function checkCommaBeforeComment(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $code = $tokens[$stackPtr]['code'];
        if ($code === T_OPEN_SHORT_ARRAY) {
            $opener = $stackPtr;
            $closer = $tokens[$stackPtr]['bracket_closer'] ?? null;
            $kind = 'array';
        } elseif ($code === T_USE) {
            if (!UseStatements::isClosureUse($phpcsFile, $stackPtr)) {
                return;
            }

            $opener = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
            $closer = $opener === false ? null : ($tokens[$opener]['parenthesis_closer'] ?? null);
            $kind = 'closure use list';
        } else {
            $opener = $tokens[$stackPtr]['parenthesis_opener'] ?? null;
            $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
            $kind = $code === T_ARRAY ? 'array' : 'parameter list';
        }

        if (!is_int($opener) || !is_int($closer) || $tokens[$opener]['line'] !== $tokens[$closer]['line']) {
            return;
        }

        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($closer - 1), $opener, true);
        if (
            $last === false || $tokens[$last]['code'] !== T_COMMA
            || $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($last + 1), $closer) === false
        ) {
            return;
        }

        $error = 'The last item in a single-line %s must not have a trailing comma';
        if ($phpcsFile->addFixableError($error, $last, self::CODE_DISALLOWED, [$kind])) {
            $phpcsFile->fixer->replaceToken($last, '');
        }
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
     * Checks the last of a `match` arm's conditions: it needs a trailing comma, before the `=>`, if the conditions are
     * split across lines, and can't have one if they aren't.
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
            if ($last === false) {
                continue;
            }

            $isSplit = MatchHelper::hasSplitConditions($phpcsFile, $arm);
            $hasComma = $tokens[$last]['code'] === T_COMMA;
            if ($isSplit === $hasComma) {
                continue;
            }

            if ($hasComma) {
                $error = 'The last item in a %s must not have a trailing comma';
                if ($phpcsFile->addFixableError($error, $last, self::CODE_DISALLOWED, ['match condition list'])) {
                    $phpcsFile->fixer->replaceToken($last, '');
                }

                continue;
            }

            $error = 'The last item in a multi-line %s must have a trailing comma';
            if ($phpcsFile->addFixableError($error, $last, self::CODE_MISSING, ['match condition list'])) {
                $phpcsFile->fixer->addContent($last, ',');
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
