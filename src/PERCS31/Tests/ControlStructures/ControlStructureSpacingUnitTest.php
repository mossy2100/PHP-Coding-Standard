<?php

declare(strict_types=1);

namespace PERCS31\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.ControlStructures.ControlStructureSpacing sniff.
 */
class ControlStructureSpacingUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // An array as the whole condition: the ) not on its own line, and not lined up.
            39 => 1,
            46 => 1,
            // The parent's checks: the first expression on the ( line, twice, and a line not indented.
            52 => 1,
            58 => 1,
            65 => 1,
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
