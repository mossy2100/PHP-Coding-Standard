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
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        // With splitForeachAndMatch on.
        if ($testFile === 'ControlStructureSpacingUnitTest.2.inc') {
            return [
                // A foreach's expression on the ( line, with `as` and ) on the expression's last line.
                71 => 1,
                73 => 3,
                // `as` on the expression's last line.
                81 => 1,
                // An array on the ( line.
                87 => 1,
                90 => 3,
                // `as` indented twice, and its variables split over lines.
                97 => 2,
                // A comment before the break after `as` (not fixable).
                106 => 1,
                // A match's subject on the ( line.
                113 => 1,
                116 => 2,
            ];
        }

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
            // A split header's ) on the last expression's line.
            108 => 1,
            // A blank line before a split header's ) in an if, a while, a for, a switch and a do while. PERCS31
            // excludes this code, and Jumbo turns it on.
            116 => 1,
            123 => 1,
            132 => 1,
            139 => 1,
            149 => 1,
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
