<?php

declare(strict_types=1);

namespace PERCS31\Tests\Arrays;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Arrays.ValuePerLine sniff.
 */
class ValuePerLineUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // The first value on the bracket's line.
            42 => 1,
            // Two values on a line, in a short array, a long array and a destructuring list.
            47 => 1,
            52 => 1,
            56 => 1,
            // The first value after a comment on the bracket's line.
            59 => 1,
            // A value after an arrow function, and an arrow function as the first value.
            65 => 1,
            69 => 1,
            // Mixed keyed and unkeyed values: two on a line, three on a line, and the first on the bracket's line.
            75 => 1,
            76 => 2,
            79 => 1,
            // A single value on the bracket's line.
            90 => 1,
            93 => 1,
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
