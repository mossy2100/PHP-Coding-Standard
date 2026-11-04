<?php

declare(strict_types=1);

namespace PERCS31\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Classes.PropertyHookLayout sniff.
 */
class PropertyHookLayoutUnitTest extends AbstractSniffTestCase
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
            // The opening brace on the next line.
            33 => 1,
            // Two spaces before the opening brace.
            37 => 1,
            // The closing brace after code.
            42 => 1,
            // A comment after the closing brace (not fixable).
            46 => 1,
            // Two spaces either side of =>.
            49 => 1,
            // The short hook's body on the next line.
            53 => 1,
            // An inline hook with no spaces inside its braces.
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
