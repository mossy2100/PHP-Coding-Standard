<?php

declare(strict_types=1);

namespace PERCS31\Tests\Arrays;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Arrays.OpeningBracketPlacement sniff.
 */
class OpeningBracketPlacementUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // After =>.
            99 => 1,
            // After return.
            105 => 1,
            // After =.
            110 => 1,
            // In a control structure's condition.
            116 => 1,
            // After a comment (not fixable).
            124 => 1,
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
