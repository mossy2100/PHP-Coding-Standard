<?php

declare(strict_types=1);

namespace PERCS31\Tests\Attributes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Attributes.DisallowMultipleAttributesPerLine sniff.
 */
class DisallowMultipleAttributesPerLineUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Two blocks before a class, and before an enum case.
            24 => 1,
            29 => 1,
            // Two blocks before a hook, which the parent sniff misses, and with a comment between (not fixable).
            35 => 1,
            40 => 1,
            // Code before the blocks.
            46 => 1,
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
