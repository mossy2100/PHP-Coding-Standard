<?php

declare(strict_types=1);

namespace PERCS31\Tests\Arrays;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Arrays.ArrayBracketSpacing sniff.
 */
class ArrayBracketSpacingUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // One blank line, then two.
            54 => 1,
            61 => 1,
            // A keyed array, and the long array syntax.
            66 => 1,
            71 => 1,
            // A nested array.
            77 => 1,
            // After a comment (not fixable).
            84 => 1,
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
