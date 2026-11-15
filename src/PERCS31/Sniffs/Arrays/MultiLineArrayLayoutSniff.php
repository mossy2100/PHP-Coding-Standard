<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Arrays;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Arrays;
use PHPCSUtils\Utils\Context;
use PHPCSUtils\Utils\PassedParameters;

/**
 * Checks the layout of a multi-line array: its opening bracket isn't on a line of its own, and each value starts a
 * line.
 *
 * The opening bracket: PER Coding Style 3.1, section 11, says that when an array declaration is split across multiple
 * lines, the opening bracket MUST NOT be placed on its own line, as it is after `=` or `return` on the line before.
 * It's reported as OwnLine. Four kinds of array have to start a line, so they're allowed:
 * - An element of a multi-line array, as PER's own example shows.
 * - An argument in an argument list, as section 4.7 puts each argument of a split list on a line of its own.
 * - A condition of a `match` arm, as an arm starts a line.
 * - The first condition of a split `if`, `elseif`, `while`, `switch` or `for`, as sections 5.1 to 5.4 put it on the
 *   line after the opening parenthesis.
 *
 * So is a destructuring assignment that starts a statement, which isn't an array declaration.
 *
 * The fix moves the `[` up to the end of the previous line, e.g. `return [`, and Generic.Arrays.ArrayIndent then
 * re-indents the elements. After a comment, it's left to a person.
 *
 * The values: section 11 also says the first value MUST be on the next line, and there MUST be only one value per
 * line. A value that doesn't start a line is reported as ValueNoNewline. This replaces
 * Squiz.Arrays.ArrayDeclaration's ValueNoNewline check, which only looks at an array with two or more values and no
 * keys. This sniff also checks an array with one value, and one that mixes keyed and unkeyed values, which no Squiz
 * check covers. An array whose values all have keys is left to the Squiz sniff's IndexNoNewline check.
 *
 * The Squiz check reads an attribute on a value, as in `#[Pure] static fn() => 1,`, as an earlier value on the line, so
 * it reports a value that's already on a line of its own. A subclass can't fix that, as the check is in the middle of
 * processMultiLineArray(). Here, an attribute is part of its value.
 */
class MultiLineArrayLayoutSniff implements Sniff
{
    public const string CODE_OWN_LINE = 'OwnLine';

    public const string CODE_VALUE_NO_NEWLINE = 'ValueNoNewline';

    /**
     * Tokens that end the previous statement, so a `[` after them starts a destructuring assignment.
     *
     * @var list<int|string>
     */
    private const array STATEMENT_ENDS = [
        T_SEMICOLON,
        T_OPEN_CURLY_BRACKET,
        T_CLOSE_CURLY_BRACKET,
        T_OPEN_TAG,
        T_COLON,
    ];

    /**
     * The control structures whose first condition goes on the line after the opening parenthesis, when the
     * parentheses are split (sections 5.1 to 5.4). A `foreach` and a `match` have no such rule.
     *
     * @var list<int|string>
     */
    private const array SPLIT_CONDITION_OWNERS = [T_IF, T_ELSEIF, T_WHILE, T_SWITCH, T_FOR];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_ARRAY, T_OPEN_SHORT_ARRAY];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        if ($phpcsFile->getTokens()[$stackPtr]['code'] === T_OPEN_SHORT_ARRAY) {
            $this->checkOpeningBracket($phpcsFile, $stackPtr);
        }

        $this->checkValues($phpcsFile, $stackPtr);
    }

    /**
     * Report a multi-line array's opening bracket on a line of its own, unless the array is an item in a list, and fix
     * it by moving the bracket up to the end of the previous line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the array's `[`.
     */
    private function checkOpeningBracket(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$stackPtr]['bracket_closer'] ?? null;
        if (!is_int($closer) || $tokens[$closer]['line'] === $tokens[$stackPtr]['line']) {
            return;
        }

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($previous === false || $tokens[$previous]['line'] === $tokens[$stackPtr]['line']) {
            return;
        }

        // A comment before the bracket doesn't change what the bracket follows, so look past it for the statement's
        // start.
        $code = $tokens[$previous]['code'];
        $before = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($stackPtr - 1), null, true);
        $startsStatement = $before === false || in_array($tokens[$before]['code'], self::STATEMENT_ENDS, true);
        if ($startsStatement || $this->isListItem($phpcsFile, $stackPtr)) {
            return;
        }

        $error = 'The opening bracket of a multi-line array must not be on a line of its own';
        $isFixable = !isset(Tokens::COMMENT_TOKENS[$code]) && $code !== T_COMMA;
        if (!FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $stackPtr, self::CODE_OWN_LINE)) {
            return;
        }

        FixerHelper::replaceBetween($phpcsFile, $previous, $stackPtr, $code === T_OPEN_PARENTHESIS ? '' : ' ');
    }

    /**
     * Check whether an array is an item in a list: an element of an array, an argument in an argument list, a
     * condition of a `match` arm, or the first condition of a control structure that has the split rule. Each item of
     * a split list starts a line, so its `[` has to.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the array's `[`.
     * @return bool True if the array follows the list's opening bracket, or a comma in it.
     */
    private function isListItem(File $phpcsFile, int $opener): bool
    {
        $tokens = $phpcsFile->getTokens();
        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($opener - 1), null, true);
        $starts = [T_COMMA, T_OPEN_SHORT_ARRAY, T_OPEN_PARENTHESIS];
        if ($previous === false || !in_array($tokens[$previous]['code'], $starts, true)) {
            return false;
        }

        // The innermost bracket or parenthesis around the array decides what the comma belongs to.
        $container = TokenHelper::findContainer($phpcsFile, $opener);
        if ($container === null || $tokens[$container]['code'] === T_OPEN_SHORT_ARRAY) {
            return $container !== null;
        }

        // A comma directly inside a `match`'s braces ends an arm, or separates an arm's conditions.
        if ($tokens[$container]['code'] === T_OPEN_CURLY_BRACKET) {
            $owner = $tokens[$container]['scope_condition'] ?? null;

            return is_int($owner) && $tokens[$owner]['code'] === T_MATCH;
        }

        // A control structure's first condition goes on the line after its `(`, so an array that starts it has to
        // start a line.
        $owner = $tokens[$container]['parenthesis_owner'] ?? null;
        if (
            $previous === $container
            && is_int($owner)
            && in_array($tokens[$owner]['code'], self::SPLIT_CONDITION_OWNERS, true)
        ) {
            return true;
        }

        // Parentheses hold an argument list when they follow something that takes arguments, such as a function's
        // name. The parentheses of a `foreach` or a `match` don't.
        return TokenHelper::isArgumentListOpener($phpcsFile, $container);
    }

    /**
     * Report each value of a multi-line array that doesn't start a line, and fix it by breaking the line before it. An
     * array whose values all have keys is left to Squiz.Arrays.ArrayDeclaration.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the array keyword or the opening bracket.
     */
    private function checkValues(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        if ($this->isForeachList($phpcsFile, $stackPtr)) {
            return;
        }

        // A short list counts too, as Squiz.Arrays.ArrayDeclaration's check did.
        $bounds = Arrays::getOpenClose($phpcsFile, $stackPtr, true);
        if ($bounds === false || $tokens[$bounds['opener']]['line'] === $tokens[$bounds['closer']]['line']) {
            return;
        }

        $values = $this->findValues($phpcsFile, $stackPtr);
        if ($values === null) {
            return;
        }

        foreach ($values as $index => $first) {
            $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($first - 1), null, true);
            if ($previous === false || $tokens[$previous]['line'] !== $tokens[$first]['line']) {
                continue;
            }

            $error = $index === 0
                ? 'The first value in a multi-line array must be on a new line'
                : 'Each value in a multi-line array must be on a new line';
            if (!$phpcsFile->addFixableError($error, $first, self::CODE_VALUE_NO_NEWLINE)) {
                continue;
            }

            FixerHelper::breakLineBefore($phpcsFile, $first);
        }
    }

    /**
     * Find the first token of each value, or of its key, including any attribute on it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the array keyword or the opening bracket.
     * @return list<int>|null The positions, or null if every value has a key.
     */
    private function findValues(File $phpcsFile, int $stackPtr): ?array
    {
        $values = [];
        $keys = 0;
        foreach (PassedParameters::getParameters($phpcsFile, $stackPtr, 0, true) as $item) {
            $start = (int) $item['start'];
            $end = (int) $item['end'];

            // An empty slot in a list, as in `[, $b]`, has nothing to place.
            $first = $start > $end ? false : $phpcsFile->findNext(Tokens::EMPTY_TOKENS, $start, ($end + 1), true);
            if ($first === false) {
                continue;
            }

            $values[] = $first;
            if (Arrays::getDoubleArrowPtr($phpcsFile, $start, $end) !== false) {
                $keys++;
            }
        }

        // An array whose values all have keys is left to Squiz.Arrays.ArrayDeclaration.
        return $values !== [] && $keys === count($values) ? null : $values;
    }

    /**
     * Check whether a short list is the value variable in a `foreach`, which Squiz.Arrays.ArrayDeclaration skips too.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the array's opener.
     * @return bool True if it is.
     */
    private function isForeachList(File $phpcsFile, int $stackPtr): bool
    {
        return $phpcsFile->getTokens()[$stackPtr]['code'] === T_OPEN_SHORT_ARRAY
            && Context::inForeachCondition($phpcsFile, $stackPtr) === 'afterAs';
    }
}
