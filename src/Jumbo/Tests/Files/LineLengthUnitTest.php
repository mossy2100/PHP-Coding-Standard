<?php

declare(strict_types=1);

namespace Jumbo\Tests\Files;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Files.LineLength sniff.
 */
class LineLengthUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        return [];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(string $testFile = ''): array
    {
        // Lines 9 to 11 are over the recommended 80, but that warning's severity of 4 hides it at the default
        // severity of 5, which these tests run at. RulesetUnitTest checks it with --warning-severity=4.
        return [
            // Over the soft limit of 120.
            16 => 1,
        ];
    }
}
