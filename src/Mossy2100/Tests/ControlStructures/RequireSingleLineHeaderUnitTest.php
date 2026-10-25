<?php

declare(strict_types=1);

namespace Mossy2100\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Mossy2100.ControlStructures.RequireSingleLineHeader sniff.
 */
class RequireSingleLineHeaderUnitTest extends AbstractSniffTestCase
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
            // foreach.
            9 => 1,
            // foreach containing a multi-line array.
            16 => 1,
            // for.
            26 => 1,
            // switch.
            35 => 1,
            // match.
            43 => 1,
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
