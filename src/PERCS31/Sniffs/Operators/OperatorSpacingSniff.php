<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Operators;

use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\OperatorSpacingSniff as SquizSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks the spacing around binary operators, including the pipe operator `|>`.
 *
 * PER Coding Style 3.1, section 6.2, says all binary operators MUST be preceded and followed by at least one space, and
 * section 6 allows more for readability. This sniff extends Squiz.WhiteSpace.OperatorSpacing, which requires exactly
 * one space, to the operators PSR12.Operators.OperatorSpacing also covers (`&&`, `||`, `and`, `or`, `xor` and `.`),
 * and to `|>` (PHP 8.5). PHP_CodeSniffer 4.0 doesn't define `T_PIPE` itself, but passes PHP's own token through on
 * PHP 8.5, so this sniff registers it when it's defined.
 *
 * The PERCS31 ruleset excludes the parent's `SpacingBefore` and `SpacingAfter` codes, and their `Amp` versions, which
 * report more than one space, so only a missing space is reported. A standard that wants exactly one space, as
 * Jumbo does, turns them back on.
 *
 * The `|` in a multi-catch, e.g. `catch (A|B $e)`, is a union type, not an operator. Section 2.5 says the union symbol
 * MUST NOT have a space either side, so this sniff reports one there instead.
 *
 * The `&` of a by-reference hook, e.g. `&get => $this->items;`, isn't an operator either, so it's skipped.
 *
 * The parent skips any `=` directly followed by `&`, so `$x=&$a` passes. This sniff checks it, and fixes it to
 * `$x = &$a`. It still skips `$x =& $a`, as that has a space either side if `=&` is read as one operator, and PER
 * doesn't say whether it is.
 *
 * `=>` isn't registered, although PHP_CodeSniffer counts it as an assignment token. Section 6.2 doesn't list it, so
 * section 6 leaves its spacing undefined. Each use has its own sniff:
 * - An array: PERCS31.WhiteSpace.ArrowSpacing, in Jumbo only.
 * - `foreach`: Squiz.ControlStructures.ForEachLoopDeclaration.
 * - A `match` arm: PERCS31.WhiteSpace.ArrowSpacing.
 * - An arrow function: SlevomatCodingStandard.Functions.ArrowFunctionDeclaration.
 * - A property hook: PERCS31.Classes.PropertyHookLayout.
 * - `yield`: PERCS31.WhiteSpace.ArrowSpacing, in Jumbo only.
 */
class OperatorSpacingSniff extends SquizSniff
{
    public const string CODE_CATCH_UNION_SPACING = 'CatchUnionSpacing';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        $targets = [...parent::register(), ...array_values(Tokens::BOOLEAN_OPERATORS), T_STRING_CONCAT];
        if (defined('T_PIPE')) {
            $targets[] = T_PIPE;
        }

        // The parent registers PHP_CodeSniffer's assignment tokens, which include `=>`. Section 6.2 doesn't list it,
        // so section 6 leaves it undefined, and its uses are checked by their own sniffs.
        $targets = array_filter($targets, static fn(int|string $target): bool => $target !== T_DOUBLE_ARROW);

        return array_values(array_unique($targets));
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return int|null The position to skip to, as the parent returns, or null.
     */
    public function process(File $phpcsFile, int $stackPtr): ?int
    {
        // The `&` of a by-reference hook, `&get`, isn't an operator.
        if (PropertyHookHelper::isHookReference($phpcsFile, $stackPtr)) {
            return null;
        }

        if ($this->isCatchUnion($phpcsFile, $stackPtr)) {
            $this->checkCatchUnion($phpcsFile, $stackPtr);
            return null;
        }

        $result = parent::process($phpcsFile, $stackPtr);

        return is_int($result) ? $result : null;
    }

    /**
     * Check whether a token is an operator to check, including an `=` directly followed by `&`, unless it's `=&` with a
     * space either side.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token.
     * @return bool True if the token's spacing should be checked.
     */
    protected function isOperator(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] === T_EQUAL && ($tokens[($stackPtr + 1)]['code'] ?? null) === T_BITWISE_AND) {
            return $tokens[($stackPtr - 1)]['code'] !== T_WHITESPACE
                || ($tokens[($stackPtr + 2)]['code'] ?? null) !== T_WHITESPACE;
        }

        return parent::isOperator($phpcsFile, $stackPtr);
    }

    /**
     * Check whether a token is the `|` between the types in a multi-catch.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token.
     * @return bool True if it's a `|` directly inside a catch's parentheses.
     */
    private function isCatchUnion(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] !== T_BITWISE_OR || !isset($tokens[$stackPtr]['nested_parenthesis'])) {
            return false;
        }

        $opener = array_key_last($tokens[$stackPtr]['nested_parenthesis']);
        $owner = $tokens[$opener]['parenthesis_owner'] ?? null;

        return is_int($owner) && $tokens[$owner]['code'] === T_CATCH;
    }

    /**
     * Report whitespace either side of a multi-catch's `|`, and fix it by removing it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the `|`.
     */
    private function checkCatchUnion(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $before = $tokens[($stackPtr - 1)]['code'] === T_WHITESPACE;
        $after = $tokens[($stackPtr + 1)]['code'] === T_WHITESPACE;
        if (!$before && !$after) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'There must be no space around the "|" between the types in a multi-catch',
            $stackPtr,
            self::CODE_CATCH_UNION_SPACING,
        );
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        if ($before) {
            $phpcsFile->fixer->replaceToken(($stackPtr - 1), '');
        }

        if ($after) {
            $phpcsFile->fixer->replaceToken(($stackPtr + 1), '');
        }

        $phpcsFile->fixer->endChangeset();
    }
}
