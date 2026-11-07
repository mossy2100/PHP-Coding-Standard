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
            31 => 1,
            33 => 1,
            35 => 1,
            37 => 1,
            39 => 1,
            41 => 1,
            43 => 1,
            45 => 1,
            47 => 1,
            49 => 1,
            51 => 1,
            53 => 1,
            55 => 1,
            57 => 1,
            59 => 1,
            61 => 1,
            63 => 1,
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
