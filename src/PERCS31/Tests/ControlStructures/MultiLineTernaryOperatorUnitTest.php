<?php

declare(strict_types=1);

namespace PERCS31\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.ControlStructures.MultiLineTernaryOperator sniff.
 */
class MultiLineTernaryOperatorUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Two lines, with ? leading.
            35 => 1,
            // Two lines, with : leading.
            37 => 1,
            // Trailing ? and :.
            40 => 1,
            // Trailing :.
            44 => 1,
            // Not indented.
            48 => 1,
            // Indented twice.
            52 => 1,
            // A comment next to an operator (not fixable).
            56 => 1,
            // In a match arm: not indented past the arm, and on two lines.
            74 => 1,
            76 => 1,
            // A compound assignment on two lines.
            109 => 1,
            // After a set hook's nullable type.
            120 => 1,
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
