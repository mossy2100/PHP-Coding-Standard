<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\Operators;

use PERCS31\Helpers\OperatorHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Disallows whitespace, including a line break, between a prefix unary `+`, `~` or `@` and its operand.
 *
 * Inherited sniffs check the other prefix operators: SlevomatCodingStandard.Operators.NegationOperatorSpacing for `-`,
 * Generic.Formatting.SpaceAfterNot for `!`, Generic.WhiteSpace.IncrementDecrementSpacing for `++` and `--`, and
 * Slevomat's ReferenceSpacing and SpreadOperatorSpacing for `&` and `...`. Nothing checks these three. A cast keeps
 * the single space after it that PER Coding Style 3.0, section 6.1, requires.
 */
class UnaryOperatorSpacingSniff implements Sniff
{
    public const string CODE_WHITESPACE_AFTER = 'WhitespaceAfter';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_PLUS, T_BITWISE_NOT, T_ASPERAND];
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
        if ($tokens[($stackPtr + 1)]['code'] !== T_WHITESPACE) {
            return;
        }

        if ($tokens[$stackPtr]['code'] === T_PLUS && !OperatorHelper::isUnary($phpcsFile, $stackPtr)) {
            return;
        }

        $operand = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        $error = 'Expected no whitespace between the unary operator "%s" and its operand';
        $data = [$tokens[$stackPtr]['content']];

        // A comment between them would have to move, so leave that to a person.
        if ($operand === false || $tokens[$operand]['code'] === T_COMMENT) {
            $phpcsFile->addError($error, $stackPtr, self::CODE_WHITESPACE_AFTER, $data);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, self::CODE_WHITESPACE_AFTER, $data);
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($stackPtr + 1); $i < $operand; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }
}
