<?php

declare(strict_types=1);

namespace PERCS31\Tests\TypeHints;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.TypeHints.CompoundTypeLayout sniff.
 */
class CompoundTypeLayoutUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Two types of a union on one line.
            28 => 1,
            // A DNF type's intersection split across lines.
            35 => 1,
            // Two types of an intersection on one line.
            40 => 1,
            // Two union segments on one line.
            44 => 1,
            // Two types on one line in a set hook's parameter type.
            61 => 1,
            // Two union segments on one line, with a type that starts with a DNF group.
            107 => 1,
            110 => 1,
            // An intersection split inside the first group, or a later one.
            116 => 1,
            120 => 1,
            // Two types on the first line of a half-split property type.
            133 => 1,
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
