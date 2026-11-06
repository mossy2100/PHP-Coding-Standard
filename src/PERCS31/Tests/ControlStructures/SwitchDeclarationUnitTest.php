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
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
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
            229 => 1,
            233 => 1,
            237 => 1,
            242 => 1,
            243 => 1,
            251 => 1,
            252 => 1,
            // A condition that only starts with an array isn't exempt.
            259 => 1,
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
