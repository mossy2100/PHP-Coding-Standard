<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Operators;

use PERCS31\Helpers\OperatorHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\OperatorSpacingSniff as SquizSniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Parentheses;

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
 * The `|` in a multi-catch, e.g. `catch (A|B $e)`, is a union type, not an operator, so it's skipped. Section 2.5 says
 * the union symbol MUST NOT have a space either side, which PhpCollective.WhiteSpace.CatchTypeSeparatorSpacing checks.
 *
 * The `&` of a by-reference hook, e.g. `&get => $this->items;`, isn't an operator either, so it's skipped. So are the
 * `|`, `&` and `?` of a `set` hook's parameter type, e.g. `set(int|string $value)`, which PHP_CodeSniffer reads as
 * operators. PERCS31.TypeHints.CompoundTypeOperatorSpacing checks them.
 *
 * The parent skips any `=` directly followed by `&`, so `$x=&$a` passes. This sniff checks it, and fixes it to
 * `$x = &$a`. It still skips `$x =& $a`, as that has a space either side if `=&` is read as one operator, and PER
 * doesn't say whether it is.
 *
 * The parent skips a parameter's default value, as Squiz.Functions.FunctionDeclarationArgumentSpacing requires exactly
 * one space there. Its `=` is a binary operator, so section 6.2 applies: the PERCS31 ruleset excludes that sniff's
 * codes, and this sniff checks it, unless checkDefaultValues is off. A standard that wants exactly one space, as Jumbo
 * does, turns those codes back on and this property off.
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
    /**
     * Whether to check the `=` of a parameter's default value.
     */
    public bool $checkDefaultValues = true;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        $targets = [
            ...parent::register(),
            ...array_values(Tokens::BOOLEAN_OPERATORS),
            T_STRING_CONCAT,
            ...OperatorHelper::pipeOperator(),
        ];

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
        // The `&` of a by-reference hook, `&get`, and the symbols of a `set` hook's parameter type aren't operators.
        if (
            PropertyHookHelper::isHookReference($phpcsFile, $stackPtr)
            || PropertyHookHelper::isHookParameterType($phpcsFile, $stackPtr)
        ) {
            return null;
        }

        if ($this->isCatchUnion($phpcsFile, $stackPtr)) {
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

        if ($this->checkDefaultValues && $this->isDefaultValueEquals($phpcsFile, $stackPtr)) {
            return true;
        }

        // The parent reads a `+` or `-` that starts a statement, as in `foo();` then `-$a->b();`, as binary.
        $isPlusMinus = in_array($tokens[$stackPtr]['code'], [T_PLUS, T_MINUS], true);
        if ($isPlusMinus && OperatorHelper::isUnary($phpcsFile, $stackPtr)) {
            return false;
        }

        return parent::isOperator($phpcsFile, $stackPtr);
    }

    /**
     * Check whether a token is the `=` of a parameter's default value.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token.
     * @return bool True if it's an `=` directly inside a function's parameter list.
     */
    private function isDefaultValueEquals(File $phpcsFile, int $stackPtr): bool
    {
        return $phpcsFile->getTokens()[$stackPtr]['code'] === T_EQUAL
            && Parentheses::lastOwnerIn($phpcsFile, $stackPtr, [T_FUNCTION, T_CLOSURE, T_FN]) !== false;
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
        return $phpcsFile->getTokens()[$stackPtr]['code'] === T_BITWISE_OR
            && Parentheses::lastOwnerIn($phpcsFile, $stackPtr, [T_CATCH]) !== false;
    }
}
