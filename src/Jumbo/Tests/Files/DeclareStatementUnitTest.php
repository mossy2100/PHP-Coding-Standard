<?php

declare(strict_types=1);

namespace Jumbo\Tests\Files;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Files.DeclareStatement sniff.
 */
class DeclareStatementUnitTest extends AbstractSniffTestCase
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
            // A space after the comma.
            11 => 1,
            // A space after declare.
            12 => 1,
            // Spaces everywhere inside the parentheses: around each directive, equals sign, value and comma.
            13 => 6,
            // Spaces around the equals sign, with an integer value and with a string value.
            14 => 2,
            15 => 2,
            // A block declare with several directives: two spaces before the brace, and the brace not aligned.
            21 => 1,
            23 => 1,
            // A space before the semicolon, with a string value.
            24 => 1,
            // No space before the brace, with one directive and with several, and the brace on the next line.
            27 => 1,
            30 => 1,
            34 => 1,
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
