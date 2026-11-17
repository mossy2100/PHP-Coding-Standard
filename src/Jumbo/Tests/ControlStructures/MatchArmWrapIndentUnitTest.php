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
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Would fit on one line up to the value: conditions, conditions and =>, a value after =>, a line starting
            // with =>, and a value that continues over lines.
            38 => 1,
            40 => 1,
            43 => 1,
            45 => 1,
            47 => 1,
            // Too long for one line: conditions not level with the first, and a => on the last condition's line.
            52 => 1,
            53 => 2,
            // A value after =>, and a line starting with =>, not indented once.
            55 => 1,
            57 => 1,
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
