<?php

declare(strict_types=1);

namespace Jumbo\Tests\WhiteSpace;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.WhiteSpace.ArrowSpacing sniff.
 */
class ArrowSpacingUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        if ($testFile === 'ArrowSpacingUnitTest.2.inc') {
            // With checkArray and checkYield set.
            return [
                // No spaces, and two spaces, in a single-line array.
                26 => 4,
                // Aligned arrows in a multi-line array.
                30 => 1,
                // No spaces after a closure element.
                34 => 2,
                // A long array.
                38 => 2,
                // Destructuring.
                39 => 2,
                // A yield.
                44 => 2,
                // A long-syntax destructuring list.
                53 => 2,
            ];
        }

        return [
            // Aligned arrows.
            11 => 1,
            12 => 1,
            // Extra space after the arrow.
            18 => 1,
            // No spaces before or after.
            24 => 2,
        ];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(string $testFile = ''): array
    {
        return [];
    }
}
