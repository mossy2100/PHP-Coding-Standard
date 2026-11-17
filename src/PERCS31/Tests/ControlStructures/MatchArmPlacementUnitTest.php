<?php

declare(strict_types=1);

namespace PERCS31\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.ControlStructures.MatchArmPlacement sniff.
 */
class MatchArmPlacementUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Two arms, three arms, after a call, nested, a whole match on one line, and a comment in the way.
            39 => 1,
            43 => 2,
            47 => 1,
            52 => 1,
            58 => 1,
            62 => 1,
            // An arm's conditions split over lines: two on a line, three after a comment, and one after an array that
            // spans lines, twice.
            79 => 1,
            82 => 2,
            85 => 2,
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
