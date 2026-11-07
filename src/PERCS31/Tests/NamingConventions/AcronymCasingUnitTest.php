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
            60 => 1,
            62 => 1,
            64 => 1,
            66 => 1,
            69 => 1,
            71 => 1,
            73 => 1,
            76 => 1,
            78 => 1,
            80 => 1,
            82 => 1,
            106 => 1,
            108 => 1,
            112 => 1,
            114 => 1,
            116 => 2,
            118 => 2,
            120 => 2,
            121 => 1,
            126 => 1,
            154 => 1,
            155 => 1,
            156 => 2,
            157 => 2,
            169 => 1,
            171 => 1,
            186 => 1,
            188 => 1,
            190 => 1,
            192 => 1,
            200 => 1,
            211 => 1,
            213 => 1,
            215 => 1,
            217 => 1,
            221 => 1,
            223 => 1,
            232 => 1,
            234 => 1,
            236 => 1,
            247 => 1,
            248 => 1,
            249 => 1,
            250 => 1,
        ];
    }
}
