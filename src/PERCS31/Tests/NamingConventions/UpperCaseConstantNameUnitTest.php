<?php

declare(strict_types=1);

namespace PERCS31\Tests\NamingConventions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.NamingConventions.UpperCaseConstantName sniff.
 */
class UpperCaseConstantNameUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // A class constant not in UPPER_CASE, left to the parent.
            37 => 1,
            // An enum constant in neither PascalCase nor UPPER_CASE.
            45 => 1,
            48 => 1,
            // Global constants and aliases not in UPPER_CASE, with checkGlobalConstants set.
            61 => 1,
            62 => 1,
            63 => 1,
            79 => 1,
            80 => 1,
            // The second constant in a statement declaring several.
            87 => 1,
            // With requireUpperSnakeCase: global constants with a double leading underscore, a double underscore
            // between words, and a trailing one, and the same from define().
            108 => 1,
            109 => 1,
            110 => 1,
            112 => 1,
            113 => 1,
            // Class constants, with two in one statement, and one in lower case, which only gets the case error.
            119 => 1,
            120 => 2,
            121 => 1,
            // An enum constant in upper case that isn't in strict UPPER_SNAKE_CASE.
            130 => 1,
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
            // An enum constant in UPPER_CASE, as PascalCase is recommended.
            21 => 1,
            51 => 1,
            52 => 1,
            53 => 1,
            // Enum constants in upper case, with requireUpperSnakeCase on.
            129 => 1,
            130 => 1,
        ];
    }
}
