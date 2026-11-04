<?php

declare(strict_types=1);

namespace PERCS31\Tests\TypeHints;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.TypeHints.CompoundTypeLayout sniff.
 */
class CompoundTypeLayoutUnitTest extends AbstractSniffTestCase
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
            // Two types of a union on one line.
            28 => 1,
            // A DNF type's intersection split across lines.
            35 => 1,
            // Two types of an intersection on one line.
            40 => 1,
            // Two union segments on one line.
            44 => 1,
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
