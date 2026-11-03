<?php

declare(strict_types=1);

namespace PERCS31\Tests\Attributes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Attributes.DisallowMultiLineAttributesJoining sniff.
 */
class DisallowMultiLineAttributesJoiningUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        return [
            // A block split over lines, one attribute per line.
            19 => 1,
            // A block split by an attribute's argument list.
            25 => 1,
            // A method's attributes, with arguments.
            32 => 1,
            // A comment between the attributes (not fixable).
            39 => 1,
        ];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(string $testFile = ''): array
    {
        return [];
    }
}
