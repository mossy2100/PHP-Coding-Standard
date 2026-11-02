<?php

declare(strict_types=1);

namespace Mossy2100\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Mossy2100.Classes.EmptyLinesAroundClassBraces sniff.
 */
class EmptyLinesAroundClassBracesUnitTest extends AbstractSniffTestCase
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
            // A blank line after the opening brace.
            21 => 1,
            // A blank line before the closing brace.
            30 => 1,
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
