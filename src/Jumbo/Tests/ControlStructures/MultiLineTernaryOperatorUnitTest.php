<?php

declare(strict_types=1);

namespace Jumbo\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.ControlStructures.MultiLineTernaryOperator sniff.
 */
class MultiLineTernaryOperatorUnitTest extends AbstractSniffTestCase
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
