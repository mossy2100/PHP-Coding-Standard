<?php

declare(strict_types=1);

namespace Jumbo\Tests\Attributes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Attributes.AttributePlacement sniff.
 */
class AttributePlacementUnitTest extends AbstractSniffTestCase
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
            // Class.
            42 => 1,
            // Constant.
            44 => 1,
            // Property.
            46 => 1,
            // Method, after a second attribute.
            48 => 1,
            // Parameter in a multi-line parameter list.
            53 => 1,
            // A comment between the attribute and its target (not fixable).
            58 => 1,
            // Function.
            63 => 1,
            // Anonymous class: an attribute on the line with new.
            80 => 1,
            // Anonymous class: the attribute and class not indented.
            82 => 1,
            // Anonymous class: class on the attribute's line.
            86 => 1,
            // Anonymous class: two attributes and class on the line with new.
            89 => 1,
            // Anonymous class: a comment (not fixable).
            91 => 1,
            // Anonymous class: a function call's argument.
            93 => 1,
            // A blank line after a docblock, and between two attribute blocks.
            122 => 1,
            129 => 1,
            // Two spaces, and no space, between an attribute and its parameter.
            134 => 2,
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
