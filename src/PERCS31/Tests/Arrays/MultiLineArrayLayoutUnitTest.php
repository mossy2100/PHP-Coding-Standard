<?php

declare(strict_types=1);

namespace PERCS31\Tests\Arrays;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Arrays.MultiLineArrayLayout sniff.
 */
class MultiLineArrayLayoutUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the fixture file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        // splitForeachAndMatch: an array that starts a split foreach or match header may start a line with it on,
        // and not with it off. An array after `=` is reported either way.
        if ($testFile === 'MultiLineArrayLayoutUnitTest.3.inc') {
            return [27 => 1, 36 => 1, 46 => 1];
        }

        // Each value on a line of its own.
        if ($testFile === 'MultiLineArrayLayoutUnitTest.2.inc') {
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

        return [
            // After =>.
            128 => 1,
            // After return.
            134 => 1,
            // After =.
            139 => 1,
            // In a foreach's parentheses.
            145 => 1,
            // After a comment (not fixable).
            153 => 1,
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
