<?php

declare(strict_types=1);

namespace Jumbo\Tests\Operators;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Operators.OperatorSpacing sniff.
 */
class OperatorSpacingUnitTest extends AbstractSniffTestCase
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
        if ($testFile === 'OperatorSpacingUnitTest.2.inc') {
            return [
                // No space either side of the pipe operator.
                13 => 2,
                // Two spaces either side.
                14 => 2,
            ];
        }

        return [
            // No space either side of &&.
            15 => 2,
            // Two spaces either side of the concatenation operator.
            16 => 2,
            // Two spaces either side of or.
            17 => 2,
            // Spaces around a multi-catch's |.
            24 => 1,
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
