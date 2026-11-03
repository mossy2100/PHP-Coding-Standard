<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\Operators;

use Mossy2100\Helpers\OperatorHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Disallows a binary operator at the end of a line, when a statement wraps at it.
 *
 * PER Coding Style 3.0, section 6.4, says that when a statement is split at an operator, the operator MUST be placed
 * at the beginning of the new line. Section 7.1 says the same of an arrow function's `=>`. This sniff reports an
 * operator that's the last thing on its line, and fixes it by moving the operator to the start of the next line.
 * Operators within a line aren't affected.
 *
 * It covers assignment, arithmetic, comparison, bitwise and logical operators, `??`, `instanceof`, the short ternary
 * `?:`, the arrow function's `=>`, and the pipe operator `|>` (PHP 8.5, PER Coding Style 3.1). Two cases are left to
 * inherited sniffs: `.`, checked by Universal.Operators.ConcatPosition, and `&&` or `||` in a control structure's
 * condition, checked by PSR12.ControlStructures.BooleanOperatorPlacement. A full ternary is checked by
 * Mossy2100.ControlStructures.MultiLineTernaryOperator, and `=>` in an array or `match` arm isn't an operator.
 */
class OperatorPlacementSniff implements Sniff
{
    public const string CODE_AT_END_OF_LINE = 'AtEndOfLine';

    /**
     * Control structures whose condition PSR12.ControlStructures.BooleanOperatorPlacement checks.
     *
     * @var list<int|string>
     */
    private const array CONDITION_OWNERS = [T_IF, T_ELSEIF, T_WHILE, T_SWITCH, T_MATCH];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        $tokens = Tokens::ASSIGNMENT_TOKENS + Tokens::COMPARISON_TOKENS + Tokens::OPERATORS
            + Tokens::BOOLEAN_OPERATORS;
        unset($tokens[T_DOUBLE_ARROW]);

        $targets = [...array_values($tokens), T_INSTANCEOF, T_FN_ARROW, T_INLINE_THEN];
        if (defined('T_PIPE')) {
            $targets[] = T_PIPE;
        }

        return $targets;
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

        // The operator's last token: `?:` is two tokens, and only the short ternary is checked here.
        $lastPtr = $stackPtr;
        if ($code === T_INLINE_THEN) {
            if ($tokens[($stackPtr + 1)]['code'] !== T_INLINE_ELSE) {
                return;
            }

            $lastPtr = $stackPtr + 1;
        }

        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($lastPtr + 1), null, true);
        if ($next === false || $tokens[$next]['line'] === $tokens[$lastPtr]['line']) {
            return;
        }

        if ($this->isLeftToOtherSniffs($phpcsFile, $stackPtr) || $this->isUnaryOrReference($phpcsFile, $stackPtr)) {
            return;
        }

        $operator = $phpcsFile->getTokensAsString($stackPtr, ($lastPtr - $stackPtr + 1));
        $error = 'Operator "%s" must be at the start of the next line, not the end of this one';

        // A comment after the operator would be moved with it, or separated from it, so leave that to a person.
        $afterOperator = $phpcsFile->findNext(T_WHITESPACE, ($lastPtr + 1), null, true);
        if ($afterOperator !== $next) {
            $phpcsFile->addError($error, $stackPtr, self::CODE_AT_END_OF_LINE, [$operator]);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, self::CODE_AT_END_OF_LINE, [$operator]);
        if (!$fix) {
            return;
        }

        $before = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        assert(is_int($before));
        $phpcsFile->fixer->beginChangeset();
        for ($i = ($before + 1); $i <= $lastPtr; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContentBefore($next, $operator . ' ');
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check whether an operator is checked by another sniff in the standard.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @return bool True for `&&` or `||` in a control structure's condition.
     */
    private function isLeftToOtherSniffs(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] !== T_BOOLEAN_AND && $tokens[$stackPtr]['code'] !== T_BOOLEAN_OR) {
            return false;
        }

        foreach (array_keys($tokens[$stackPtr]['nested_parenthesis'] ?? []) as $opener) {
            $owner = $tokens[$opener]['parenthesis_owner'] ?? null;
            if ($owner !== null && in_array($tokens[$owner]['code'], self::CONDITION_OWNERS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether a `+`, `-` or `&` is unary, or a reference, not a binary operator.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     * @return bool True if it isn't a binary operator.
     */
    private function isUnaryOrReference(File $phpcsFile, int $stackPtr): bool
    {
        $code = $phpcsFile->getTokens()[$stackPtr]['code'];
        if ($code === T_BITWISE_AND && $phpcsFile->isReference($stackPtr)) {
            return true;
        }

        return ($code === T_PLUS || $code === T_MINUS || $code === T_BITWISE_AND)
            && OperatorHelper::isUnary($phpcsFile, $stackPtr);
    }
}
