<?php

declare(strict_types=1);

namespace OceanMoon\Tests\Arrays;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for OceanMoon.Arrays.ArrayDeclaration sniff.
 */
class ArrayDeclarationUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        return [
            // Simple list: trailing comma.
            26  => 1,
            // Simple list: should be single line (trailing comma handled by fix).
            29  => 1,
            // Complex list (nested arrays): missing trailing comma.
            50  => 1,
            // Complex list (nested arrays): first element on same line.
            54  => 1,
            // Complex list (nested arrays): element on same line.
            61  => 1,
            // Complex list (nested arrays): closing bracket on same line.
            69  => 1,
            // Complex list (nested arrays): wrong indentation.
            73  => 1,
            // Complex list (nested arrays): wrong closing bracket indentation.
            83  => 1,
            // Complex list (nested arrays): all on one line.
            86  => 5,
            // Complex list (function calls): all on one line.
            91  => 5,
            // Complex list (new expressions): all on one line.
            94  => 5,
            // Complex list (enum case access): all on one line.
            97  => 4,
            // Complex list (arithmetic expressions): all on one line.
            100 => 4,
            // Complex list (function calls): too long for single line, forced one per line unconditionally.
            103 => 8,
            // Assoc: missing trailing comma.
            135 => 1,
            // Assoc: first element on same line.
            139 => 1,
            // Assoc: element on same line + arrow alignment.
            146 => 2,
            147 => 1,
            // Assoc: closing bracket on same line.
            154 => 1,
            // Assoc: arrow alignment.
            158 => 1,
            160 => 1,
            // Assoc: wrong indentation.
            165 => 1,
            // Assoc: wrong closing bracket indentation.
            175 => 1,
            // Assoc: value not on arrow line.
            180 => 1,
            // Grid: one-per-line list that should be grid.
            198 => 1,
            // Grid: single long line that should be grid.
            217 => 1,
            // Grid: grid with wrong padding.
            220 => 1,
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
