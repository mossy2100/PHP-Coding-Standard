<?php

declare(strict_types=1);

namespace Mossy2100\Tests\Operators;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Mossy2100.Operators.OperatorPlacement sniff.
 */
class OperatorPlacementUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
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
        ];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(string $testFile = ''): array
    {
        return [];
    }
}
