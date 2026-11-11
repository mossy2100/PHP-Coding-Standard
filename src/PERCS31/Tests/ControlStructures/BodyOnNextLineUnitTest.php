<?php

declare(strict_types=1);

namespace PERCS31\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.ControlStructures.BodyOnNextLine sniff.
 */
class BodyOnNextLineUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // The body on the brace's line: an if, a foreach, a do, and a try, catch and finally.
            35 => 1,
            38 => 1,
            40 => 1,
            43 => 1,
            44 => 1,
            45 => 1,
            // A space between the braces.
            49 => 1,
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
