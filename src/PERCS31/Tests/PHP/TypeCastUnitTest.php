<?php

declare(strict_types=1);

namespace PERCS31\Tests\PHP;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.PHP.TypeCast sniff.
 */
class TypeCastUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // The parent's check: a long form, and an alias.
            18 => 1,
            19 => 1,
            20 => 1,
            // A (binary) cast: in lower case, in capitals, with spaces inside, and with none after.
            23 => 1,
            24 => 1,
            25 => 1,
            26 => 1,
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
