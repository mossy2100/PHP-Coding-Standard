<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\TernaryOperatorHelper;

/**
 * Requires a ternary that spans lines to take three, with `?` and `:` each starting a line, indented once.
 *
 * PER Coding Style 3.0, section 6.4, says a multi-line ternary MUST occupy 3 lines, never 2, with each operator at the
 * start of its line, indented once. SlevomatCodingStandard.ControlStructures.DisallowTrailingMultiLineTernaryOperator
 * only reports a `?` at the end of a line, so this sniff replaces it. It also reports a ternary on two lines, a
 * trailing `:`, and `?` or `:` indented wrongly, and fixes each to the three-line layout.
 *
 * A short ternary (`?:`) is a binary operator in PER, so it isn't checked here.
 *
 * Finding where a ternary starts and ends uses Slevomat's TernaryOperatorHelper, which is marked internal, so check
 * this sniff after a Slevomat upgrade.
 */
class MultiLineTernaryOperatorSniff implements Sniff
{
    public const string CODE_INCORRECT_LAYOUT = 'IncorrectLayout';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_INLINE_THEN];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $inlineThenPointer The position of the `?`.
     */
    public function process(File $phpcsFile, int $inlineThenPointer): void
    {
        $tokens = $phpcsFile->getTokens();

        // A short ternary, which PER treats as a binary operator.
        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($inlineThenPointer + 1), null, true);
        if ($next === false || $tokens[$next]['code'] === T_INLINE_ELSE) {
            return;
        }

        $inlineElsePointer = TernaryOperatorHelper::getElsePointer($phpcsFile, $inlineThenPointer);
        $startPointer = TernaryOperatorHelper::getStartPointer($phpcsFile, $inlineThenPointer);
        $endPointer = TernaryOperatorHelper::getEndPointer($phpcsFile, $inlineThenPointer, $inlineElsePointer);
        if ($tokens[$startPointer]['line'] === $tokens[$endPointer]['line']) {
            return;
        }

        // One level past the indentation of the line the ternary starts on. A level is always 4 spaces, as PER Coding
        // Style 3.0, section 2.4, requires, so ScopeIndent's indent property isn't used.
        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $startPointer, true);
        assert(is_int($firstOnLine));
        $indentation = str_repeat(' ', ($tokens[$firstOnLine]['column'] - 1 + 4));

        if (
            $this->startsLineWithIndentation($phpcsFile, $inlineThenPointer, $indentation)
            && $this->startsLineWithIndentation($phpcsFile, $inlineElsePointer, $indentation)
        ) {
            return;
        }

        $error = 'A ternary that spans lines must take three, with "?" and ":" each starting a line, indented once';
        $operators = [$inlineThenPointer, $inlineElsePointer];
        if ($this->hasCommentAround($phpcsFile, $operators)) {
            $phpcsFile->addError($error, $inlineThenPointer, self::CODE_INCORRECT_LAYOUT);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $inlineThenPointer, self::CODE_INCORRECT_LAYOUT);
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        foreach ($operators as $operator) {
            // A new line and the indentation before the operator, and one space after it.
            $before = $phpcsFile->findPrevious(T_WHITESPACE, ($operator - 1), null, true);
            $after = $phpcsFile->findNext(T_WHITESPACE, ($operator + 1), null, true);
            assert(is_int($before) && is_int($after));
            for ($i = ($before + 1); $i < $operator; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }

            for ($i = ($operator + 1); $i < $after; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }

            $phpcsFile->fixer->addContentBefore($operator, $phpcsFile->eolChar . $indentation);
            $phpcsFile->fixer->addContent($operator, ' ');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check whether an operator is the first token on its line, with the given indentation.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $operator The position of the `?` or `:`.
     * @param string $indentation The expected indentation.
     * @return bool True if the operator starts its line with that indentation.
     */
    private function startsLineWithIndentation(File $phpcsFile, int $operator, string $indentation): bool
    {
        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $operator, true);

        return $firstOnLine === $operator && $phpcsFile->getTokens()[$operator]['column'] - 1 === strlen($indentation);
    }

    /**
     * Check whether a comment is next to any of the operators, so moving them could move it to the wrong place.
     *
     * @param File $phpcsFile The file being scanned.
     * @param list<int> $operators The positions of the `?` and `:`.
     * @return bool True if a comment comes right before or after an operator.
     */
    private function hasCommentAround(File $phpcsFile, array $operators): bool
    {
        $tokens = $phpcsFile->getTokens();
        foreach ($operators as $operator) {
            $before = $phpcsFile->findPrevious(T_WHITESPACE, ($operator - 1), null, true);
            $after = $phpcsFile->findNext(T_WHITESPACE, ($operator + 1), null, true);
            foreach ([$before, $after] as $pointer) {
                if ($pointer !== false && isset(Tokens::COMMENT_TOKENS[$tokens[$pointer]['code']])) {
                    return true;
                }
            }
        }

        return false;
    }
}
