<?php

declare(strict_types=1);

namespace PERCS31\Tests\TypeHints;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.TypeHints.NullType sniff.
 */
class NullTypeUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // null first in a DNF type, and in the middle of one.
            14 => 2,
            // null first in a union of two.
            19 => 1,
            // null in the middle of a union of three.
            24 => 1,
            // A DNF property type.
            31 => 1,
            // Typed constants.
            35 => 1,
            36 => 1,
            // set hook parameters.
            42 => 1,
            54 => 1,
            // A split type, and one with a comment, reported but not fixed.
            68 => 1,
            75 => 1,
            // An arrow function's parameter.
            81 => 1,
        ];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(): array
    {
        return [
            // ?T in a typed constant, and in a set hook's parameter.
            34 => 1,
            35 => 1,
            42 => 1,
            48 => 1,
        ];
    }
}
