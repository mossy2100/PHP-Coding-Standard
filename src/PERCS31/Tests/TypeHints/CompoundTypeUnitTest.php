<?php

declare(strict_types=1);

namespace PERCS31\Tests\TypeHints;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.TypeHints.CompoundType sniff.
 */
class CompoundTypeUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the fixture file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        if ($testFile === 'CompoundTypeUnitTest.2.inc') {
            return [
                // Spaces either side, before only, and after only.
                21 => 3,
                // A property's type.
                27 => 1,
                // The symbol at the end of a line.
                31 => 1,
                // A set hook's parameter type: a union, an intersection, a DNF type, inside its parentheses, and at a
                // line end.
                44 => 1,
                48 => 1,
                52 => 1,
                56 => 1,
                61 => 1,
                // A multi-catch: spaces around the |, and the | at a line end.
                84 => 1,
                87 => 1,
            ];
        }

        // The checks on null.
        if ($testFile === 'CompoundTypeUnitTest.3.inc') {
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
                // null before static in a return type.
                95 => 1,
                // Static properties: null first, in the middle, and first in a DNF type.
                115 => 1,
                116 => 1,
                118 => 1,
            ];
        }

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
            // A property's and a constant's type lines, level with the type's line, and indented too far.
            144 => 1,
            147 => 1,
            // A parameter's type lines, indented too little and too far, and a promoted one's, level with its line.
            167 => 1,
            169 => 1,
            171 => 1,
            // A catch's type line indented too far.
            177 => 1,
            // A return type's line, level with its function.
            184 => 1,
        ];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @param string $testFile The name of the fixture file.
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(string $testFile = ''): array
    {
        if ($testFile !== 'CompoundTypeUnitTest.3.inc') {
            return [];
        }

        return [
            // ?T for null|int.
            19 => 1,
            // ?T in a typed constant, and in a set hook's parameter.
            34 => 1,
            35 => 1,
            42 => 1,
            48 => 1,
            // ?T in a property, a promoted parameter, parameters and a static return type.
            86 => 1,
            88 => 1,
            90 => 3,
            95 => 1,
            // ?T in a closure and an arrow function.
            107 => 2,
            110 => 2,
            // ?T in a static property.
            117 => 1,
        ];
    }
}
