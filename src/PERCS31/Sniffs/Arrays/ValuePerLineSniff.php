<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Arrays;

use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Context;

/**
 * Requires each value of a multi-line array to start on a new line.
 *
 * PER Coding Style 3.1, section 11, says that when an array is split across lines, the first value MUST be on the next
 * line, and there MUST be only one value per line. This replaces Squiz.Arrays.ArrayDeclaration's ValueNoNewline check,
 * which only looks at an array with two or more values and no keys. This sniff also checks an array with one value,
 * and one that mixes keyed and unkeyed values, which no Squiz check covers. An array whose values all have keys is left
 * to the Squiz sniff's IndexNoNewline check.
 *
 * The Squiz check reads an attribute on a value, as in `#[Pure] static fn() => 1,`, as an earlier value on the line, so
 * it reports a value that's already on a line of its own. A subclass can't fix that, as the check is in the middle of
 * processMultiLineArray(). Here, an attribute is part of its value.
 */
class ValuePerLineSniff implements Sniff
{
    public const string CODE_VALUE_NO_NEWLINE = 'ValueNoNewline';

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
        $tokens = $phpcsFile->getTokens();
        if ($this->isForeachList($phpcsFile, $stackPtr)) {
            return;
        }

        $opener = $stackPtr;
        if ($tokens[$stackPtr]['code'] === T_ARRAY) {
            $opener = $tokens[$stackPtr]['parenthesis_opener'] ?? null;
            if ($opener === null) {
                return;
            }
        }

        $closer = TokenHelper::findCloser($tokens[$opener]);
        if ($closer === null || $tokens[$opener]['line'] === $tokens[$closer]['line']) {
            return;
        }

        $values = $this->findValues($phpcsFile, $opener, $closer);
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

            if ($tokens[($first - 1)]['code'] === T_WHITESPACE) {
                $phpcsFile->fixer->replaceToken(($first - 1), $phpcsFile->eolChar);
            } else {
                $phpcsFile->fixer->addNewlineBefore($first);
            }
        }
    }

    /**
     * Find the first token of each value, or of its key, including any attribute on it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the opening bracket or parenthesis.
     * @param int $closer The position of the closing bracket or parenthesis.
     * @return list<int>|null The positions, or null if every value has a key.
     */
    private function findValues(File $phpcsFile, int $opener, int $closer): ?array
    {
        $tokens = $phpcsFile->getTokens();
        $values = [];
        $keys = 0;
        $start = $opener + 1;
        for ($i = $start; $i <= $closer; $i++) {
            if ($i < $closer && $tokens[$i]['code'] === T_DOUBLE_ARROW) {
                $keys++;
                continue;
            }

            if ($i === $closer || $tokens[$i]['code'] === T_COMMA) {
                $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, $start, $i, true);
                if ($first !== false) {
                    $values[] = $first;
                }

                $start = $i + 1;
                continue;
            }

            // Skip anything in brackets, so only this array's commas and arrows count. A scope closer isn't followed:
            // a closure's body is in braces, and an arrow function's is the comma that ends it, which is this array's.
            $i = TokenHelper::findCloser($tokens[$i]) ?? $i;
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
