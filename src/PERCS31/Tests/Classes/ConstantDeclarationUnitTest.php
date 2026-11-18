<?php

declare(strict_types=1);

namespace PERCS31\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Classes.ConstantDeclaration sniff.
 */
class ConstantDeclarationUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        if ($testFile === 'ConstantDeclarationUnitTest.2.inc') {
            // Global constants, with checkGlobalConstants on: two spaces after const, a line break, and two constants
            // in one statement.
            return [17 => 1, 19 => 1, 22 => 1];
        }

        return [
            // Two spaces after const, before a name and before a type.
            18 => 1,
            19 => 1,
            // Two spaces after the type.
            20 => 1,
            // Both.
            21 => 2,
            // A line break after const, and after the type.
            23 => 1,
            25 => 1,
            // Three spaces after a union type.
            26 => 1,
            // In an interface and an enum.
            32 => 1,
            39 => 1,
            // An enum case: two spaces after case, and a line break.
            46 => 1,
            48 => 1,
            // Two constants in one statement: in the valid class above, whose spacing is right, and then in a class
            // (twice), an interface, a trait, an enum and an anonymous class.
            13 => 1,
            62 => 1,
            63 => 1,
            69 => 1,
            74 => 1,
            79 => 1,
            83 => 1,
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
