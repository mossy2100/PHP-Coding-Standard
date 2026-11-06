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
            37 => 1,
            39 => 1,
            42 => 1,
            44 => 1,
            46 => 1,
            // Too long for one line, and not indented once: conditions, a value after =>, and a line starting with =>.
            51 => 1,
            52 => 1,
            54 => 1,
            56 => 1,
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
