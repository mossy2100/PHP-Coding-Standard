<?php

declare(strict_types=1);

namespace Jumbo\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.ControlStructures.MatchArmWrapIndent sniff.
 */
class MatchArmWrapIndentUnitTest extends AbstractSniffTestCase
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
            // A later condition, a value after =>, and a line starting with =>, not indented once.
            41 => 1,
            43 => 1,
            45 => 1,
            47 => 1,
            49 => 1,
            57 => 1,
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
