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
        return [
            // A final default with no break.
            64 => 1,
            // A final case with no break.
            69 => 1,
            // A final case whose if has no else.
            74 => 1,
            // The } follows code on its line: not fixable.
            81 => 1,
            // The ) isn't on a line of its own.
            86 => 1,
            // Whitespace before the ).
            90 => 1,
            // No parentheses.
            93 => 1,
            // No parentheses, and a trailing comment: not fixable.
            97 => 1,
            // A comment before the ): not fixable.
            102 => 1,
            // A default that falls through with no comment, alone, after an if with no else, and nested.
            157 => 1,
            168 => 1,
            180 => 1,
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
