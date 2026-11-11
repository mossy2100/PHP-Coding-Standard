<?php

declare(strict_types=1);

namespace PERCS31\Tests\NamingConventions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.NamingConventions.ValidClassName sniff.
 */
class ValidClassNameUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Not a capital first, or an underscore.
            49 => 1,
            51 => 1,
            53 => 1,
            55 => 1,
            57 => 1,
            59 => 1,
            // No lower-case letter.
            62 => 1,
            64 => 1,
            66 => 1,
            68 => 1,
            70 => 1,
            72 => 1,
            74 => 1,
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
