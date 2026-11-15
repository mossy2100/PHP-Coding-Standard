<?php

declare(strict_types=1);

namespace PERCS31\Tests\WhiteSpace;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.WhiteSpace.ObjectOperatorSpacing sniff.
 */
class ObjectOperatorSpacingUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // A space around ->, each side and both.
            26 => 1,
            27 => 1,
            28 => 2,
            // A space around ?->.
            31 => 1,
            32 => 1,
            // A space around ::, each side and both.
            35 => 1,
            36 => 1,
            37 => 2,
            38 => 2,
            // A line break before ::, and after it.
            42 => 1,
            43 => 1,
            // A space around :: in a trait use block.
            50 => 2,
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
