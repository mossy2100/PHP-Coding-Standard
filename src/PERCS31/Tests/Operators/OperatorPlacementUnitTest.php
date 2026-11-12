<?php

declare(strict_types=1);

namespace PERCS31\Tests\Operators;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Operators.OperatorPlacement sniff.
 */
class OperatorPlacementUnitTest extends AbstractSniffTestCase
{
    /**
     * Get the test files, leaving out the pipe operator's before PHP 8.5, which has no `T_PIPE` token.
     *
     * @param string $testFileBase The base path that the unit tests files will have.
     * @return list<string>
     */
    protected function getTestFiles(string $testFileBase): array
    {
        $testFiles = parent::getTestFiles($testFileBase);
        if (defined('T_PIPE')) {
            return array_values($testFiles);
        }

        return array_values(array_filter($testFiles, static fn(string $file): bool => !str_contains($file, '.2.inc')));
    }

    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        if ($testFile === 'OperatorPlacementUnitTest.2.inc') {
            // The pipe operator.
            return [13 => 1];
        }

        if ($testFile === 'OperatorPlacementUnitTest.5.inc') {
            return [
                // In grouping parentheses, indented twice past the ( line, and not indented.
                68 => 1,
                73 => 1,
                // Not indented once past the ( line.
                77 => 1,
                80 => 1,
                // In a group that continues a statement, and one nested in a call argument, indented twice.
                85 => 1,
                91 => 1,
                // An index, lined up with its first line.
                99 => 1,
                // An arrow function's => and body indented twice.
                105 => 1,
                110 => 1,
            ];
        }

        if ($testFile === 'OperatorPlacementUnitTest.3.inc') {
            return [
                // Indented twice.
                73 => 1,
                // Not indented.
                75 => 1,
                // An arrow function's => indented twice.
                77 => 1,
                // Lined up with a call argument and a match arm's condition, and indented twice in a group.
                92 => 1,
                96 => 1,
                107 => 1,
                // Lined up with a list item, after a full ternary in an earlier item.
                126 => 1,
                133 => 1,
                136 => 1,
                141 => 1,
                // Indented twice.
                149 => 1,
                // In a ternary's branch, lined up with it.
                156 => 1,
            ];
        }

        if ($testFile === 'OperatorPlacementUnitTest.6.inc') {
            return [
                // In a condition, not indented at least once.
                43 => 1,
                49 => 1,
                // Nested in a call in the condition, so the general rule.
                57 => 1,
                // With requireExactConditionIndent: a boolean operator indented twice, and others not indented once
                // past their operand's line.
                90 => 1,
                96 => 1,
                103 => 1,
            ];
        }

        return [
            // Arithmetic, after another operator on the same line.
            40 => 1,
            // Null coalescing.
            42 => 1,
            // Comparison.
            44 => 1,
            // Boolean, outside a control structure's condition.
            46 => 1,
            // instanceof.
            48 => 1,
            // Short ternary.
            50 => 1,
            // Assignment.
            52 => 1,
            // Compound assignment.
            54 => 1,
            // Arrow function.
            56 => 1,
            // A comment after the operator (not fixable).
            58 => 1,
            // A short ternary split between ? and :, by a space, a line break or a comment.
            73 => 1,
            74 => 1,
            77 => 1,
            78 => 1,
            79 => 1,
            80 => 1,
            // An assignment before a single-line array on the next line. Before a multi-line one, it's left alone.
            91 => 1,
        ];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(): array
    {
        return [];
    }
}
