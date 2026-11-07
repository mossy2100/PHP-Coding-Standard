<?php

declare(strict_types=1);

namespace PERCS31\Tests\NamingConventions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.NamingConventions.AcronymCasing sniff.
 */
class AcronymCasingUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(): array
    {
        return [
            40 => 1,
            42 => 1,
            44 => 1,
            46 => 1,
            48 => 1,
            51 => 1,
            53 => 1,
            55 => 1,
            58 => 1,
            60 => 1,
            62 => 1,
            64 => 1,
            66 => 1,
            68 => 1,
            70 => 1,
            72 => 1,
            77 => 1,
            115 => 1,
            117 => 1,
            127 => 1,
            129 => 1,
            131 => 2,
            133 => 2,
            135 => 2,
            136 => 1,
            141 => 1,
            153 => 1,
            155 => 1,
            165 => 1,
            170 => 1,
            172 => 1,
            174 => 1,
            182 => 1,
            193 => 1,
            195 => 1,
            197 => 1,
            199 => 1,
            203 => 1,
            205 => 1,
            214 => 1,
            216 => 1,
            218 => 1,
        ];
    }
}
