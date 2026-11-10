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
            59 => 1,
            60 => 1,
            61 => 1,
            77 => 1,
            78 => 1,
            // The second constant in a statement declaring several.
            85 => 1,
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
        ];
    }
}
