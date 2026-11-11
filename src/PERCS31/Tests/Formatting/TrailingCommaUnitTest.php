<?php

declare(strict_types=1);

namespace PERCS31\Tests\Formatting;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Formatting.TrailingComma sniff.
 */
class TrailingCommaUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        if ($testFile === 'TrailingCommaUnitTest.2.inc') {
            // With disallowInSplitMatchConditions, a comma before => in a split list of conditions.
            return [6 => 1, 9 => 1];
        }

        return [
            // A multi-line group use without a trailing comma.
            21 => 1,
            // A single-line group use with one.
            23 => 1,
            // A multi-line match without a trailing comma.
            27 => 1,
            // A single-line match with one.
            29 => 1,
            // A multi-line match without one, before a comment.
            32 => 1,
            // Anonymous class arguments: a comma on one line, then none on several, with and without a comment.
            78 => 1,
            81 => 1,
            85 => 1,
            // A comma in a match arm's conditions on one line: alone, in a one-line match, before a comment, default.
            91 => 1,
            94 => 1,
            97 => 1,
            98 => 1,
            // list(): a trailing comma on one line, a missing one on several, and one in a nested list.
            112 => 1,
            115 => 1,
            117 => 1,
            // clone() and exit() with a trailing comma on one line, and clone(), exit() and die() missing one on
            // several.
            137 => 1,
            140 => 1,
            142 => 1,
            144 => 1,
            147 => 1,
            // A comma after a single argument split over lines, in clone() and an anonymous class.
            162 => 1,
            165 => 1,
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
