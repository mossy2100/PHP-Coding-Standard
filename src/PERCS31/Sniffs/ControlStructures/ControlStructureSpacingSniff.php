<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\ControlStructures\ControlStructureSpacingSniff as Psr12Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks the layout of a control structure's parentheses, without PSR-12's rules for a multi-line `foreach` or
 * `match`.
 *
 * PER Coding Style 3.1 gives `if`, `switch`, `while`, `do while` and `for` a rule for an expression split inside the
 * parentheses, but section 5.5 gives `foreach` none, and section 5.2 gives `match` none. The parent applies it anyway,
 * wanting the first expression on the line after `(`, so it rejects an array in the header, and a call with one
 * argument split over lines, as in `match ($this->lookup([`. Moving an array down then breaks section 11, which
 * forbids `[` on a line of its own. So there was no way to split one.
 *
 * For a multi-line `foreach` or `match`, this sniff only checks what section 5 requires of every control structure: no
 * space after the `(` or before the `)`. Everything else is left to the parent.
 *
 * A standard that wants one layout for every split header, as Jumbo does, turns splitForeachAndMatch on. A multi-line
 * `foreach` or `match` then gets the parent's rule too: the first expression on the line after `(`, and `)` on a line
 * of its own. A `foreach`'s `as` also starts a line, level with the expression, with its variables after it:
 *
 *     foreach (
 *         array_filter($items, function ($item) {
 *             return $item->ok;
 *         })
 *         as $key => $item
 *     ) {
 *
 * PERCS31.Arrays.MultiLineArrayLayout has a property of the same name, which lets an array that starts such a header
 * start a line.
 */
class ControlStructureSpacingSniff extends Psr12Sniff
{
    public const string CODE_SPACE_AFTER_OPEN = 'SpacingAfterOpenBrace';

    public const string CODE_SPACE_BEFORE_CLOSE = 'SpaceBeforeCloseBrace';

    public const string CODE_AS_NOT_ON_OWN_LINE = 'AsNotOnOwnLine';

    public const string CODE_AS_INDENT = 'AsIndent';

    public const string CODE_VARIABLES_NOT_ON_AS_LINE = 'VariablesNotOnAsLine';

    /**
     * Whether a multi-line `foreach` or `match` takes the split layout the other control structures do, with a
     * `foreach`'s `as` starting a line. PER Coding Style 3.1 has no rule for either, so it's off by default.
     */
    public bool $splitForeachAndMatch = false;

    /**
     * Processes a control structure.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the control structure's keyword.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$stackPtr]['parenthesis_opener'] ?? null;
        $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;

        if (
            !in_array($tokens[$stackPtr]['code'], [T_FOREACH, T_MATCH], true)
            || !is_int($opener)
            || !is_int($closer)
            || $tokens[$opener]['line'] === $tokens[$closer]['line']
        ) {
            parent::process($phpcsFile, $stackPtr);
            return;
        }

        if ($this->splitForeachAndMatch) {
            parent::process($phpcsFile, $stackPtr);
            if ($tokens[$stackPtr]['code'] === T_FOREACH) {
                $this->checkAs($phpcsFile, $stackPtr, $opener, $closer);
            }

            return;
        }

        $next = $tokens[($opener + 2)]['code'] ?? null;
        if ($next !== T_COMMENT) {
            $this->checkSpace($phpcsFile, $stackPtr, ($opener + 1), 'after the opening', self::CODE_SPACE_AFTER_OPEN);
        }

        $this->checkSpace($phpcsFile, $stackPtr, ($closer - 1), 'before the closing', self::CODE_SPACE_BEFORE_CLOSE);
    }

    /**
     * Report a space next to a parenthesis. A line break, or the indent of a `)` on its own line, isn't one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the control structure's keyword.
     * @param int $space The position of the token next to the parenthesis.
     * @param string $where Where the space is, for the message.
     * @param string $code The error code.
     */
    private function checkSpace(File $phpcsFile, int $stackPtr, int $space, string $where, string $code): void
    {
        $tokens = $phpcsFile->getTokens();
        if (
            $tokens[$space]['code'] !== T_WHITESPACE
            || $tokens[$space]['column'] === 1
            || str_contains($tokens[$space]['content'], $phpcsFile->eolChar)
        ) {
            return;
        }

        $error = "There must be no space $where parenthesis of a %s";
        if ($phpcsFile->addFixableError($error, $space, $code, [strtolower($tokens[$stackPtr]['content'])])) {
            $phpcsFile->fixer->replaceToken($space, '');
        }
    }

    /**
     * Check a multi-line `foreach`'s `as`: it starts a line, level with the expression, and the loop's variables follow
     * it on that line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of `foreach`.
     * @param int $opener The position of the header's `(`.
     * @param int $closer The position of the header's `)`.
     */
    private function checkAs(File $phpcsFile, int $stackPtr, int $opener, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        $as = $this->findNextAtHeaderLevel($phpcsFile, ($opener + 1), $closer, static fn(array $token): bool
            => $token['code'] === T_AS);
        $before = $as === null ? false : $phpcsFile->findPrevious(T_WHITESPACE, ($as - 1), $opener, true);
        if ($as === null || $before === false) {
            return;
        }

        $first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
        $indent = str_repeat(' ', (($tokens[(int) $first]['column'] - 1) + $this->indent));

        if ($tokens[$before]['line'] === $tokens[$as]['line']) {
            $error = 'In a multi-line foreach, "as" must start a line';
            if ($phpcsFile->addFixableError($error, $as, self::CODE_AS_NOT_ON_OWN_LINE)) {
                FixerHelper::replaceBetween($phpcsFile, $before, $as, $phpcsFile->eolChar . $indent);
            }
        } elseif (($tokens[$as]['column'] - 1) !== strlen($indent)) {
            $error = 'In a multi-line foreach, "as" must be indented once; expected %s spaces but found %s';
            $data = [strlen($indent), ($tokens[$as]['column'] - 1)];
            if ($phpcsFile->addFixableError($error, $as, self::CODE_AS_INDENT, $data)) {
                $lineStart = $phpcsFile->findFirstOnLine([], $as, true);
                FixerHelper::replaceBetween($phpcsFile, ((int) $lineStart - 1), $as, $indent);
            }
        }

        $this->checkVariablesOnAsLine($phpcsFile, $as, $closer);
    }

    /**
     * Report a line break between a `foreach`'s `as` and its last variable, as in `as $key` then `=> $value`. One
     * inside a destructuring list, as in `as [` ... `]`, is the list's own.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $as The position of `as`.
     * @param int $closer The position of the header's `)`.
     */
    private function checkVariablesOnAsLine(File $phpcsFile, int $as, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        $last = $phpcsFile->findPrevious(T_WHITESPACE, ($closer - 1), $as, true);
        if ($last === false) {
            return;
        }

        // The first token after `as` that's on a later line. A comment holds its own line break, so the lines are
        // compared, not the whitespace.
        $asLine = $tokens[$as]['line'];
        $after = $this->findNextAtHeaderLevel($phpcsFile, ($as + 1), ($last + 1), static fn(array $token): bool
            => $token['code'] !== T_WHITESPACE && $token['line'] > $asLine);
        if ($after === null) {
            return;
        }

        // A comment before the break would swallow what's joined to its line, so that's left to a person.
        $before = $phpcsFile->findPrevious(T_WHITESPACE, ($after - 1), $as, true);
        $isFixable = $before !== false && !isset(Tokens::COMMENT_TOKENS[$tokens[$before]['code']]);
        $error = 'In a multi-line foreach, the variables must be on the same line as "as"';
        $code = self::CODE_VARIABLES_NOT_ON_AS_LINE;
        if (FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $as, $code)) {
            FixerHelper::replaceBetween($phpcsFile, (int) $before, $after, ' ');
        }
    }

    /**
     * Find the next token that matches, directly inside a header's parentheses, skipping anything in nested
     * parentheses, brackets or braces, such as a closure in the expression.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position to start at.
     * @param int $end The position to stop before.
     * @param callable(array<string, mixed>): bool $matches Whether a token is the one wanted.
     * @return int|null The token's position, or null if there's none.
     */
    private function findNextAtHeaderLevel(File $phpcsFile, int $start, int $end, callable $matches): ?int
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = $start; $i < $end; $i++) {
            if ($matches($tokens[$i])) {
                return $i;
            }

            $nestedCloser = TokenHelper::findCloser($tokens[$i]);
            if ($nestedCloser !== null && $nestedCloser > $i) {
                $i = $nestedCloser;
            }
        }

        return null;
    }
}
