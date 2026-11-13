<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Formatting;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\OperatorHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks a method chain split across lines: the first method is on a line of its own, each `->` or `?->` starts its
 * line, and each of those lines is indented once past the line the chain starts on.
 *
 * PER Coding Style 3.1, section 4.7, says method chaining MAY be put on separate lines, each indented once, and that
 * when it is, the first method MUST be on the next line. Its example starts each wrapped line with the `->`.
 *
 * A chain is every `->` and `?->` in one expression: the part of a statement between the nearest operators, commas or
 * enclosing brackets, at the same parenthesis depth. So in `$a->isValid() && $b->check()`, each call is a chain of its
 * own. A chain is split if any of its operators starts a line. Property accesses before the first method, as in
 * `$this->query`, can stay on the first line.
 *
 * The indent is measured from the line the chain starts on, the one with its object. When the chain is a ternary's
 * branch, or follows a wrapped operator, that's a continuation line, and the chain's lines are indented once past it,
 * as section 6.4 indents any line that continues another.
 *
 * This replaces PEAR.WhiteSpace.ObjectOperatorIndent, which PERCS31 used for the indent. That sniff has no property for
 * any of these, and its checks are in one method, so a subclass would have nothing of it left to call:
 * - It takes every `->` in a statement as one chain, so it measures the indent from the line the statement starts on.
 *   It reported a chain in a ternary's branch that was indented past its object, and its fix lined the two up.
 * - It lets the first method stay on the line with the object.
 * - Its StartOfLine check skips a statement's first operator, and so every statement with only one, and its fix leaves
 *   a blank line when the operator is alone on its line.
 */
class MethodChainingSniff implements Sniff
{
    public const string CODE_FIRST_METHOD_ON_SAME_LINE = 'FirstMethodOnSameLine';

    public const string CODE_OPERATOR_AT_END_OF_LINE = 'OperatorAtEndOfLine';

    public const string CODE_INDENT = 'Indent';

    /**
     * The object operators that link a chain.
     */
    private const array OPERATORS = [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return self::OPERATORS;
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $this->checkEndOfLine($phpcsFile, $stackPtr);
        $chain = $this->findChain($phpcsFile, $stackPtr);

        // Check each chain once, from its first operator.
        if ($chain[0] !== $stackPtr || !$this->isSplit($phpcsFile, $chain)) {
            return;
        }

        $this->checkIndents($phpcsFile, $chain);
        $tokens = $phpcsFile->getTokens();
        foreach ($chain as $operator) {
            $name = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($operator + 1), null, true);
            if ($name === false) {
                return;
            }

            $paren = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($name + 1), null, true);
            if ($paren === false || $tokens[$paren]['code'] !== T_OPEN_PARENTHESIS) {
                // A property access, not a method call.
                continue;
            }

            // An operator at the end of its line has its own error, whose fix puts the method on the next line.
            $endsLine = $tokens[$name]['line'] !== $tokens[$operator]['line'];
            if (!$endsLine && !LayoutHelper::startsLine($phpcsFile, $operator)) {
                $this->report($phpcsFile, $operator);
            }

            return;
        }
    }

    /**
     * Report an object operator at the end of a line, and fix it by moving it to the start of the next line. After a
     * comment, it's left to a person.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $operator The position of the object operator.
     */
    private function checkEndOfLine(File $phpcsFile, int $operator): void
    {
        $tokens = $phpcsFile->getTokens();
        $name = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($operator + 1), null, true);
        if ($name === false || $tokens[$name]['line'] === $tokens[$operator]['line']) {
            return;
        }

        $error = 'An object operator must be at the start of a line, not the end';
        $isFixable = $phpcsFile->findNext(T_WHITESPACE, ($operator + 1), $name, true) === false;
        $code = self::CODE_OPERATOR_AT_END_OF_LINE;
        if (!FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $operator, $code)) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        if (LayoutHelper::startsLine($phpcsFile, $operator)) {
            // The operator is alone on its line, so the name joins it.
            FixerHelper::setBetween($phpcsFile, $operator, $name, '');
        } else {
            if ($tokens[($operator - 1)]['code'] === T_WHITESPACE) {
                $phpcsFile->fixer->replaceToken(($operator - 1), '');
            }

            $phpcsFile->fixer->replaceToken($operator, '');
            $phpcsFile->fixer->addContentBefore($name, $tokens[$operator]['content']);
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Find the object operators in the same statement, and at the same parenthesis depth, as the given one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of an object operator.
     * @return non-empty-list<int> The positions of the operators, in order.
     */
    private function findChain(File $phpcsFile, int $stackPtr): array
    {
        $tokens = $phpcsFile->getTokens();
        [$start, $end] = $this->findChainBounds($phpcsFile, $stackPtr);
        $brackets = $tokens[$stackPtr]['nested_parenthesis'] ?? null;
        $conditions = $tokens[$stackPtr]['conditions'];

        $chain = [];
        for ($i = $start; $i <= $end; $i++) {
            if (
                in_array($tokens[$i]['code'], self::OPERATORS, true)
                && ($tokens[$i]['nested_parenthesis'] ?? null) === $brackets
                && $tokens[$i]['conditions'] === $conditions
            ) {
                $chain[] = $i;
            }
        }

        return $chain === [] ? [$stackPtr] : $chain;
    }

    /**
     * Find the first and last tokens of the expression an object operator's chain is in: the part of its statement
     * between the nearest operators, commas or enclosing brackets either side of it. So in `$a->b() && $c->d()`, each
     * call is a chain of its own.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of an object operator.
     * @return array{int, int} The positions of the first and last tokens.
     */
    private function findChainBounds(File $phpcsFile, int $stackPtr): array
    {
        $tokens = $phpcsFile->getTokens();
        $start = $phpcsFile->findStartOfStatement($stackPtr);
        $end = $phpcsFile->findEndOfStatement($stackPtr);
        $boundaries = [
            ...OperatorHelper::binaryOperators(),
            ...array_values(Tokens::ASSIGNMENT_TOKENS),
            T_INLINE_THEN,
            T_INLINE_ELSE,
            T_COMMA,
            T_MATCH_ARROW,
            T_FN_ARROW,
        ];

        // Anything in brackets is part of the chain, such as a call's arguments, so it's passed over.
        for ($i = ($stackPtr - 1); $i >= $start; $i--) {
            $opener = TokenHelper::findOpener($tokens[$i]);
            if ($opener !== null && $opener < $i) {
                $i = $opener;
                continue;
            }

            if (in_array($tokens[$i]['code'], $boundaries, true) || TokenHelper::findCloser($tokens[$i]) !== null) {
                $start = $i + 1;
                break;
            }
        }

        for ($i = ($stackPtr + 1); $i <= $end; $i++) {
            $closer = TokenHelper::findCloser($tokens[$i]);
            if ($closer !== null && $closer > $i) {
                $i = $closer;
                continue;
            }

            if (in_array($tokens[$i]['code'], $boundaries, true) || TokenHelper::findOpener($tokens[$i]) !== null) {
                $end = $i - 1;
                break;
            }
        }

        return [$start, $end];
    }

    /**
     * Check if any operator in a chain starts a line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param list<int> $chain The positions of the chain's operators.
     */
    private function isSplit(File $phpcsFile, array $chain): bool
    {
        foreach ($chain as $operator) {
            if (LayoutHelper::startsLine($phpcsFile, $operator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Report a first method on the same line as the object, and fix it by moving it to the next line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $operator The position of the first method's object operator.
     */
    private function report(File $phpcsFile, int $operator): void
    {
        $fix = $phpcsFile->addFixableError(
            'The first method of a chain split across lines must be on the next line',
            $operator,
            self::CODE_FIRST_METHOD_ON_SAME_LINE,
        );
        if (!$fix) {
            return;
        }

        FixerHelper::breakLineBefore($phpcsFile, $operator, $this->findChainIndent($phpcsFile, $operator));
    }

    /**
     * Report each operator in a chain that starts a line with the wrong indent, and fix it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param non-empty-list<int> $chain The positions of the chain's operators.
     */
    private function checkIndents(File $phpcsFile, array $chain): void
    {
        $tokens = $phpcsFile->getTokens();
        $expected = $this->findChainIndent($phpcsFile, $chain[0]);
        foreach ($chain as $operator) {
            $found = $tokens[$operator]['column'] - 1;
            if (!LayoutHelper::startsLine($phpcsFile, $operator) || $found === $expected) {
                continue;
            }

            $fix = $phpcsFile->addFixableError(
                'Object operator not indented correctly; expected %s spaces but found %s',
                $operator,
                self::CODE_INDENT,
                [$expected, $found],
            );
            if ($fix) {
                FixerHelper::setIndent($phpcsFile, $operator, $expected);
            }
        }
    }

    /**
     * Find the indent of a chain's wrapped lines: once past the line the chain starts on. That's the line with its
     * object, which is a continuation line itself when the chain is a ternary's branch, or follows a wrapped operator.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $operator The position of one of the chain's operators.
     * @return int The number of spaces.
     */
    private function findChainIndent(File $phpcsFile, int $operator): int
    {
        [$start] = $this->findChainBounds($phpcsFile, $operator);
        $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, $start, null, true);

        return LayoutHelper::lineIndent($phpcsFile, ($first === false ? $operator : $first)) + LayoutHelper::INDENT;
    }
}
