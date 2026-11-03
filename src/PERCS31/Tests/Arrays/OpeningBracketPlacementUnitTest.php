<?php

declare(strict_types=1);

namespace PERCS31\Tests\Arrays;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Arrays.OpeningBracketPlacement sniff.
 */
class OpeningBracketPlacementUnitTest extends AbstractSniffTestCase
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
            // The only argument of a call, with a trailing comma.
            35 => 1,
            // The only argument of a call, without a trailing comma.
            42 => 1,
            // After =>.
            49 => 1,
            // After return.
            55 => 1,
            // After another argument (not fixable).
            61 => 1,
            // Before another argument (not fixable).
            67 => 1,
            // After a comment (not fixable).
            74 => 1,
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
