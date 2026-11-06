<?php

declare(strict_types=1);

namespace PERCS31\Tests\Strings;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Strings.HeredocIndentation sniff.
 */
class HeredocIndentationUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Not indented, as in PER's counterexample.
            36 => 1,
            // Indented too far.
            40 => 1,
            // Not indented enough, with interpolation.
            44 => 1,
            // Not on the line of its context: after =, with = on the next line, after yield and return.
            92 => 1,
            97 => 1,
            102 => 1,
            107 => 1,
            // In an array: after =>, and with => on the next line.
            114 => 1,
            118 => 1,
            // After a match arm's =>, and an arrow function's =>.
            125 => 1,
            132 => 1,
            // A comment in the way (not fixable).
            138 => 1,
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
