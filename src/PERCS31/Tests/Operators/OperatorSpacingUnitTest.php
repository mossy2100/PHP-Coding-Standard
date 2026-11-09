<?php

declare(strict_types=1);

namespace PERCS31\Tests\Operators;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Operators.OperatorSpacing sniff.
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
            // A reference assignment with no space around its =.
            46 => 2,
            47 => 1,
            48 => 1,
            49 => 2,
            50 => 2,
            51 => 2,
            // A parameter's default value: none either side, none after, and in an arrow function.
            65 => 3,
            67 => 2,
            // A real operator in a hook, including in an attribute on a set hook's parameter.
            104 => 2,
            105 => 2,
            106 => 2,
            107 => 2,
            108 => 2,
            109 => 2,
            // No space after a binary - that follows a match's and a variable variable's closing brace.
            124 => 1,
            125 => 1,
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
