<?php

declare(strict_types=1);

namespace PERCS31\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.ControlStructures.SwitchDeclaration sniff.
 */
class SwitchDeclarationUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        if ($testFile === 'SwitchDeclarationUnitTest.2.inc') {
            return [
                // A blank line after a label: one, two, before a comment, before another label, and on a default.
                8 => 1,
                13 => 1,
                18 => 1,
                23 => 1,
                36 => 1,
                39 => 1,
                // Code on the label's line.
                47 => 1,
                // A comment on the label's line.
                49 => 1,
                52 => 1,
                55 => 1,
                // A comment then code: the comment's error isn't fixable, but the parent's for the code is.
                58 => 2,
                // A comment on the line, and a blank line after it.
                60 => 2,
                // A default's.
                66 => 1,
            ];
        }

        if ($testFile === 'SwitchDeclarationUnitTest.3.inc') {
            return [
                // A blank line after a label: one, two, before a comment, and before another label.
                8 => 1,
                12 => 1,
                17 => 1,
                22 => 1,
                // Before a break, on a default.
                33 => 1,
            ];
        }

        return [
            // The first condition is on the ( line.
            42 => 1,
            // A final default with no break.
            64 => 1,
            // A final case with no break.
            69 => 1,
            // A final case whose if has no else.
            74 => 1,
            // The } follows code on its line: not fixable.
            81 => 1,
            // The ) isn't on a line of its own, and the first condition is on the ( line.
            85 => 1,
            86 => 1,
            // Whitespace before the ), and the first condition is on the ( line.
            89 => 1,
            90 => 1,
            // No parentheses.
            93 => 1,
            // No parentheses, and a trailing comment: not fixable.
            97 => 1,
            // A comment before the ): not fixable. The first condition is on the ( line: fixable.
            101 => 1,
            102 => 1,
            // A default that falls through with no comment, alone, after an if with no else, and nested.
            157 => 1,
            168 => 1,
            180 => 1,
            // A split condition: the first condition on the ( line, then lines not indented.
            224 => 1,
            228 => 1,
            232 => 1,
            237 => 1,
            238 => 1,
            246 => 1,
            247 => 1,
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
