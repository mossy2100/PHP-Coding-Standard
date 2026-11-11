<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;
use PHPCSUtils\Utils\Operators;
use SlevomatCodingStandard\Helpers\TernaryOperatorHelper;

/**
 * Requires a ternary split at `?` or `:` to take three lines, with `?` and `:` each starting a line, indented once.
 *
 * PER Coding Style 3.1, section 6.4, says a multi-line ternary MUST occupy 3 lines, never 2, with each operator at the
 * start of its line, indented once. SlevomatCodingStandard.ControlStructures.DisallowTrailingMultiLineTernaryOperator
 * only reports a `?` at the end of a line, so this sniff replaces it. It also reports a ternary on two lines, a
 * trailing `:`, and `?` or `:` indented wrongly, and fixes each to the three-line layout.
 *
 * A ternary is split if there's a line break right before or after its `?` or `:`. A value or condition split over
 * lines doesn't split it, as in `$a = $b ? [` over several lines to `] : [];`, so it's left alone, as other binary
 * operators are.
 *
 * A short ternary (`?:`) is a binary operator in PER, so it isn't checked here.
 *
 * Finding where a ternary starts uses Slevomat's TernaryOperatorHelper, which is marked internal, so check this sniff
 * after a Slevomat upgrade. It only stops at some tokens, such as `=` and `return`, so it can walk back past the start
 * of the ternary's statement or `match` arm to an earlier line, which would give the wrong indent. This sniff also
 * stops at anything else that comes before a ternary's expression, including an enclosing ternary's `?` or `:`. It
 * finds the `:` itself, as the helper takes the first one, which is a nested ternary's.
 *
 * A ternary nested in another's middle operand is checked as its own ternary, indented once past the line it starts
 * on, as in `? $b` then `? 1` and `: 2` indented once more.
 *
 * PHP_CodeSniffer reads the `?` of a nullable type in a `set` hook's parameter, as in `set(?int $value)`, as a
 * ternary's. The helper would crash on it, as it has no `:`, so it's skipped.
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
        if (PropertyHookHelper::isHookParameterType($phpcsFile, $inlineThenPointer)) {
            return;
        }

        // A short ternary, which PER treats as a binary operator.
        if (Operators::isShortTernary($phpcsFile, $inlineThenPointer)) {
            return;
        }

        $inlineElsePointer = $this->findElse($phpcsFile, $inlineThenPointer);
        if ($inlineElsePointer === null) {
            return;
        }

        $startPointer = $this->findStart(
            $phpcsFile,
            $inlineThenPointer,
            TernaryOperatorHelper::getStartPointer($phpcsFile, $inlineThenPointer),
        );
        if (
            !$this->isSplitAtOperator($phpcsFile, $inlineThenPointer)
            && !$this->isSplitAtOperator($phpcsFile, $inlineElsePointer)
        ) {
            return;
        }

        // One level past the indentation of the line the ternary starts on. A level is always 4 spaces, as PER Coding
        // Style 3.1, section 2.4, requires, so ScopeIndent's indent property isn't used.
        $indentation = str_repeat(' ', LayoutHelper::lineIndent($phpcsFile, $startPointer) + LayoutHelper::INDENT);

        if (
            $this->startsLineWithIndentation($phpcsFile, $inlineThenPointer, $indentation)
            && $this->startsLineWithIndentation($phpcsFile, $inlineElsePointer, $indentation)
        ) {
            return;
        }

        $error = 'A ternary split at "?" or ":" must take three lines, with each starting a line, indented once';
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
            FixerHelper::blankBetween($phpcsFile, $before, $operator);

            FixerHelper::blankBetween($phpcsFile, $operator, $after);

            $phpcsFile->fixer->addContentBefore($operator, $phpcsFile->eolChar . $indentation);
            $phpcsFile->fixer->addContent($operator, ' ');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check whether there's a line break right before or after an operator, ignoring comments.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $operator The position of the `?` or `:`.
     * @return bool True if the code before or after the operator is on another line.
     */
    private function isSplitAtOperator(File $phpcsFile, int $operator): bool
    {
        $tokens = $phpcsFile->getTokens();
        $before = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($operator - 1), null, true);
        $after = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($operator + 1), null, true);

        return ($before !== false && $tokens[$before]['line'] !== $tokens[$operator]['line'])
            || ($after !== false && $tokens[$after]['line'] !== $tokens[$operator]['line']);
    }

    /**
     * Find where a ternary starts: no earlier than the token that comes before its expression.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $inlineThenPointer The position of the `?`.
     * @param int $startPointer The position Slevomat's helper found.
     * @return int The position of the ternary's first token.
     */
    private function findStart(File $phpcsFile, int $inlineThenPointer, int $startPointer): int
    {
        $tokens = $phpcsFile->getTokens();
        $boundaries = [
            ...array_values(Tokens::ASSIGNMENT_TOKENS),
            T_SEMICOLON,
            T_OPEN_CURLY_BRACKET,
            T_CLOSE_CURLY_BRACKET,
            T_COMMA,
            T_MATCH_ARROW,
            T_ECHO,
            T_PRINT,
            T_YIELD,
            T_YIELD_FROM,
        ];

        // A ternary nested in another's branch starts after the enclosing ternary's `?` or `:`.
        $closers = [T_CLOSE_PARENTHESIS, T_CLOSE_SQUARE_BRACKET, T_CLOSE_SHORT_ARRAY];
        for ($i = ($inlineThenPointer - 1); $i >= $startPointer; $i--) {
            // Brackets before the `?` are part of the condition, whatever's inside them.
            $code = $tokens[$i]['code'];
            $isDynamicName = $code === T_CLOSE_CURLY_BRACKET && !isset($tokens[$i]['scope_condition']);
            if ($isDynamicName || in_array($code, $closers, true)) {
                $jumpTo = TokenHelper::findOpener($tokens[$i]);
                if (is_int($jumpTo)) {
                    $i = $jumpTo;
                    continue;
                }
            }

            $isTernary = isset(Collections::ternaryOperators()[$code]);
            $isEnclosingTernary = $isTernary && !Operators::isShortTernary($phpcsFile, $i);
            if ($isEnclosingTernary || in_array($code, $boundaries, true)) {
                $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), $inlineThenPointer, true);

                return $first === false ? $startPointer : $first;
            }
        }

        return $startPointer;
    }

    /**
     * Find a ternary's `:`, skipping any ternary nested in its middle operand, and anything in brackets or a body.
     *
     * Slevomat's TernaryOperatorHelper::getElsePointer() takes the first `:`, which is a nested ternary's.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $inlineThenPointer The position of the `?`.
     * @return int|null The position of the `:`, or null if there isn't one.
     */
    private function findElse(File $phpcsFile, int $inlineThenPointer): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $depth = 0;
        for ($i = ($inlineThenPointer + 1); $i < $phpcsFile->numTokens; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_SEMICOLON || in_array($code, [T_CLOSE_PARENTHESIS, T_CLOSE_SQUARE_BRACKET], true)) {
                return null;
            }

            $closer = TokenHelper::findCloser($tokens[$i]);
            if (is_int($closer) && $closer > $i) {
                $i = $closer;
                continue;
            }

            if (($code !== T_INLINE_THEN && $code !== T_INLINE_ELSE) || Operators::isShortTernary($phpcsFile, $i)) {
                continue;
            }

            if ($code === T_INLINE_THEN) {
                $depth++;
                continue;
            }

            if ($depth === 0) {
                return $i;
            }

            $depth--;
        }

        return null;
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
        return LayoutHelper::startsLine($phpcsFile, $operator)
            && $phpcsFile->getTokens()[$operator]['column'] - 1 === strlen($indentation);
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
