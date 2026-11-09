<?php

declare(strict_types=1);

namespace PERCS31\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Classes.ClosingBrace sniff.
 */
class ClosingBraceUnitTest extends AbstractSniffTestCase
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
            35 => 1,
            40 => 1,
            46 => 1,
            48 => 1,
            53 => 1,
            60 => 1,
            78 => 1,
            83 => 1,
            92 => 1,
            96 => 1,
            98 => 1,
            111 => 1,
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
