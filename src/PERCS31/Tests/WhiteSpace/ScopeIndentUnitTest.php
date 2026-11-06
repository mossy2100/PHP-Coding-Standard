<?php

declare(strict_types=1);

namespace PERCS31\Tests\WhiteSpace;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.WhiteSpace.ScopeIndent sniff.
 *
 * Focuses on PHP 8.4 property hook indentation, which is the reason this
 * sniff was forked from Generic.WhiteSpace.ScopeIndent.
 */
class ScopeIndentUnitTest extends AbstractSniffTestCase
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
            // Hook body under-indented.
            89 => 1,
            // Code after hooks over-indented.
            94 => 1,
            // An anonymous class with attributes, not indented from new: the class, its body and its closing brace.
            129 => 1,
            130 => 1,
            131 => 1,
            // A match arm indented too far: first, later, nested, in a function.
            244 => 1,
            250 => 1,
            255 => 1,
            264 => 1,
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
