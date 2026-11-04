<?php

declare(strict_types=1);

namespace Jumbo\Tests\Operators;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Operators.OperatorPlacement sniff.
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

        if ($testFile === 'OperatorPlacementUnitTest.3.inc') {
            return [
                // Indented twice.
                61 => 1,
                // Not indented.
                63 => 1,
                // Lined up with an argument, not indented once past it.
                66 => 1,
                // An arrow function's => indented twice.
                69 => 1,
                // Lined up with a match arm's condition.
                72 => 1,
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
