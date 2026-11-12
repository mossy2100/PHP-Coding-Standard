<?php

declare(strict_types=1);

namespace PERCS31\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.ControlStructures.ForEachSpacing sniff.
 */
class ForEachSpacingUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Two spaces each side of as, and of =>.
            52 => 2,
            56 => 2,
            // No space each side of =>, and of as.
            60 => 2,
            64 => 2,
            // In a split foreach: two spaces each side of as, and after a => that starts a line.
            69 => 2,
            70 => 1,
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
