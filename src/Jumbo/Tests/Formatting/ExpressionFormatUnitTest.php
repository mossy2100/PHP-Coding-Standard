<?php

declare(strict_types=1);

namespace Jumbo\Tests\Formatting;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Formatting.ExpressionFormat sniff.
 */
class ExpressionFormatUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the file being tested.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        if ($testFile === 'ExpressionFormatUnitTest.2.inc') {
            // String expressions, from line 20 to line 46, then too long from line 49, then string conversions.
            $lines = [...range(20, 46), 49, 50, 51, 52, 57, 60, 63, 65, 67];

            return array_fill_keys($lines, 1) + [64 => 2];
        }

        return [
            // Fits on one line.
            46 => 1,
            48 => 1,
            50 => 1,
            54 => 1,
            // Split at a tighter operator, or not split at all.
            61 => 1,
            68 => 1,
            69 => 1,
            // A line broken before an operand that fits on it.
            71 => 1,
            // An operand split while the level around it isn't.
            74 => 1,
            76 => 1,
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
