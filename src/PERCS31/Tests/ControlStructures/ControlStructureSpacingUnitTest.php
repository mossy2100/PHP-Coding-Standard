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
            // A space after the ( and before the ) of a multi-line foreach.
            66 => 1,
            74 => 1,
            // The parent's checks: spaces in a single-line foreach, and the first expression on the ( line.
            80 => 2,
            84 => 1,
            90 => 1,
            // A space after the ( and before the ) of a multi-line match.
            98 => 1,
            100 => 1,
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
