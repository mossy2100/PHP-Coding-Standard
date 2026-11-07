<?php

declare(strict_types=1);

namespace PERCS31\Tests\NamingConventions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.NamingConventions.ValidEnumCaseName sniff.
 */
class ValidEnumCaseNameUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // All lower case.
            25 => 1,
            // All upper case.
            26 => 1,
            // snake_case.
            27 => 1,
            // An underscore.
            28 => 1,
            // camelCase.
            29 => 1,
            // Two capitals.
            30 => 1,
            // A run of capitals.
            31 => 1,
            32 => 1,
            // A one-letter word before a capital.
            33 => 1,
            34 => 1,
            // A run of capitals before a digit.
            35 => 1,
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
