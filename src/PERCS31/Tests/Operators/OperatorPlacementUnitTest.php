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
        // A full ternary split at its ? or :.
        if ($testFile === 'OperatorPlacementUnitTest.4.inc') {
            return [
                // Two lines, with ? leading.
                35 => 1,
                // Two lines, with : leading.
                37 => 1,
                // Trailing ? and :.
                40 => 1,
                // Trailing :.
                44 => 1,
                // Not indented.
                48 => 1,
                // Indented twice.
                52 => 1,
                // A comment next to an operator (not fixable).
                56 => 1,
                // In a match arm: not indented past the arm, and on two lines.
                74 => 1,
                76 => 1,
                // A compound assignment on two lines.
                109 => 1,
                // After a set hook's nullable type.
                120 => 1,
                // A line break next to one operator: with a value split over lines, and after a comment (not fixable).
                168 => 1,
                173 => 1,
                176 => 1,
                // A nested ternary not indented once past its own line.
                195 => 1,
            ];
        }

        if ($testFile === 'OperatorPlacementUnitTest.2.inc') {
            // The pipe operator.
            return [13 => 1];
        }

        if ($testFile === 'OperatorPlacementUnitTest.5.inc') {
            return [
                // In grouping parentheses, indented twice past the ( line, and not indented.
                86 => 1,
                91 => 1,
                // Not indented once past the ( line.
                95 => 1,
                98 => 1,
                // In a group that continues a statement, and one nested in a call argument, indented twice.
                103 => 1,
                109 => 1,
                // An index, lined up with its first line.
                117 => 1,
                // An arrow function's => and body lined up with fn.
                123 => 1,
                128 => 1,
                // A nested arrow function's => lined up with the outer one.
                134 => 1,
                // A third nested arrow function's => lined up with the second.
                140 => 1,
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

        if ($testFile === 'OperatorPlacementUnitTest.7.inc') {
            // A walk back over fn with requireExactConditionIndent, which once never finished.
            return [];
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
            37 => 1,
            // Null coalescing.
            39 => 1,
            // Comparison.
            41 => 1,
            // Boolean, outside a control structure's condition.
            43 => 1,
            // instanceof.
            45 => 1,
            // Short ternary.
            47 => 1,
            // Assignment.
            49 => 1,
            // Compound assignment.
            51 => 1,
            // Arrow function.
            53 => 1,
            // A comment after the operator (not fixable).
            55 => 1,
            // An assignment before a single-line array on the next line. Before a multi-line one, it's left alone.
            77 => 1,
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
