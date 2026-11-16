<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Operators;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\OperatorHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Disallows whitespace, including a line break, after a prefix unary operator: a reference `&`, the spread operator
 * `...`, and `+`, `-`, `!`, `~` and `@`.
 *
 * Each operator has its own property. Only the reference `&` and the spread `...` are checked by default, as PER Coding
 * Style 3.1, section 4.5, has a rule for each:
 * - "When using the reference operator `&` before an argument, there MUST NOT be a space after it."
 * - "There MUST NOT be a space between the variadic three dot operator and the argument name."
 *
 * PERCS31 reads each as the rule for the operator, wherever it's used:
 * - A reference `&`: a parameter, a closure's `use` list, an assignment, a `foreach`, an array, a function that returns
 *   by reference and a by-reference hook, e.g. `&get`.
 * - A spread `...`: a variadic parameter, as in `int ...$rest`, a call's arguments, as in `foo(...$args)`, and an
 *   array, as in `[...$a]`.
 *
 * Section 6 leaves the other five undefined, so they're off unless a standard turns them on, as Jumbo does.
 *
 * OperatorHelper::isUnary() tells each from its other use. A bitwise `&`, and a binary `+` or `-`, are skipped, as
 * PERCS31.Operators.OperatorSpacing checks them. The `...` of a first-class callable, as in `foo(...)`, has no operand,
 * so it's skipped too. Section 4.8 sets its spacing, which Universal.WhiteSpace.FirstClassCallableSpacing checks.
 *
 * Generic.WhiteSpace.IncrementDecrementSpacing checks `++` and `--`. A cast keeps the single space after it that
 * section 6.1 requires.
 *
 * A comment between an operator and its operand would have to move, so that's reported with no fix. The whitespace
 * in `- -$a` and `+ +$a` is needed, as without it PHP reads `--` or `++`, so that's left alone.
 */
class UnaryOperatorSpacingSniff implements Sniff
{
    public const string CODE_WHITESPACE_AFTER = 'WhitespaceAfter';

    /**
     * Whether to check the reference operator `&`, as in `&$a`.
     */
    public bool $checkReferenceOperator = true;

    /**
     * Whether to check the spread operator `...`, as in `...$a`. A first-class callable's `...` is never checked.
     */
    public bool $checkSpreadOperator = true;

    /**
     * Whether to check the unary plus operator `+`, as in `+$a`.
     */
    public bool $checkPlusOperator = false;

    /**
     * Whether to check the negation operator `-`, as in `-$a`.
     */
    public bool $checkNegationOperator = false;

    /**
     * Whether to check the logical not operator `!`, as in `!$a`.
     */
    public bool $checkNotOperator = false;

    /**
     * Whether to check the bitwise not operator `~`, as in `~$a`.
     */
    public bool $checkBitwiseNotOperator = false;

    /**
     * Whether to check the error control operator `@`, as in `@foo()`.
     */
    public bool $checkErrorControlOperator = false;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_BITWISE_AND, T_PLUS, T_MINUS, T_BOOLEAN_NOT, T_BITWISE_NOT, T_ASPERAND, T_ELLIPSIS];
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
        if (($tokens[($stackPtr + 1)]['code'] ?? null) !== T_WHITESPACE) {
            return;
        }

        $name = $this->getOperatorName($phpcsFile, $stackPtr);
        if ($name === null) {
            return;
        }

        $operand = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($operand !== false && $this->wouldJoin($tokens[$stackPtr]['code'], $tokens[$operand]['code'])) {
            return;
        }

        $error = 'Expected no whitespace after the %s operator "%s"';
        $data = [$name, $tokens[$stackPtr]['content']];

        // A comment between them would have to move, so leave that to a person.
        $isFixable = $operand !== false && !isset(Tokens::COMMENT_TOKENS[$tokens[$operand]['code']]);
        $code = self::CODE_WHITESPACE_AFTER;
        if (!FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $stackPtr, $code, $data)) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        FixerHelper::blankBetween($phpcsFile, $stackPtr, (int) $operand);

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Get the name of the unary operator a token is, if it's one this sniff is set to check.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token.
     * @return string|null The operator's name, for the message, or null if the token isn't to be checked.
     */
    private function getOperatorName(File $phpcsFile, int $stackPtr): ?string
    {
        // `!`, `~` and `@` are only ever unary. The others have a binary form, or, for `...`, a first-class callable's.
        $code = $phpcsFile->getTokens()[$stackPtr]['code'];
        $isAlwaysUnary = in_array($code, [T_BOOLEAN_NOT, T_BITWISE_NOT, T_ASPERAND], true);
        if (!$isAlwaysUnary && !OperatorHelper::isUnary($phpcsFile, $stackPtr)) {
            return null;
        }

        [$isChecked, $name] = match ($code) {
            T_BITWISE_AND => [$this->checkReferenceOperator, 'reference'],
            T_PLUS => [$this->checkPlusOperator, 'unary plus'],
            T_MINUS => [$this->checkNegationOperator, 'negation'],
            T_BOOLEAN_NOT => [$this->checkNotOperator, 'logical not'],
            T_BITWISE_NOT => [$this->checkBitwiseNotOperator, 'bitwise not'],
            T_ASPERAND => [$this->checkErrorControlOperator, 'error control'],
            T_ELLIPSIS => [$this->checkSpreadOperator, 'spread'],
            default => [false, ''],
        };

        return $isChecked ? $name : null;
    }

    /**
     * Check whether an operator needs the whitespace after it, as without it PHP would read a different operator. The
     * space in `- -$a` keeps it from being `--$a`, and the same goes for `+ +$a`, `- --$a` and `+ ++$a`.
     *
     * @param int|string $operatorCode The operator's token code.
     * @param int|string $operandCode The token code of what follows the whitespace.
     * @return bool True if removing the whitespace would join the two into another operator.
     */
    private function wouldJoin(int|string $operatorCode, int|string $operandCode): bool
    {
        return ($operatorCode === T_MINUS && in_array($operandCode, [T_MINUS, T_DEC], true))
            || ($operatorCode === T_PLUS && in_array($operandCode, [T_PLUS, T_INC], true));
    }
}
