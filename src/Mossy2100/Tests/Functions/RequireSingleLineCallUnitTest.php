<?php

declare(strict_types=1);

namespace Mossy2100\Tests\Functions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Mossy2100.Functions.RequireSingleLineCall sniff.
 */
class RequireSingleLineCallUnitTest extends AbstractSniffTestCase
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
            // A function call.
            23 => 1,
            // A constructor call.
            28 => 1,
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
