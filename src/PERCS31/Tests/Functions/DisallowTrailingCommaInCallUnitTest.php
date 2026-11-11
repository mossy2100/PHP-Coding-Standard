<?php

declare(strict_types=1);

namespace PERCS31\Tests\Functions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Functions.DisallowTrailingCommaInCall sniff.
 */
class DisallowTrailingCommaInCallUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // A comma in a list on one line.
            27 => 1,
            28 => 1,
            // A comma after a single argument split over lines: an array, a closure and an arrow function.
            34 => 1,
            37 => 1,
            40 => 1,
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
