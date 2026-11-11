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
            28 => 1,
            // All upper case.
            29 => 1,
            // snake_case.
            30 => 1,
            // An underscore.
            31 => 1,
            // camelCase.
            32 => 1,
            // No lower-case letter: two capitals, and capitals before a digit.
            33 => 1,
            34 => 1,
            // No lower-case letter: single capitals, alone or with digits.
            35 => 1,
            36 => 1,
            37 => 1,
            38 => 1,
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
