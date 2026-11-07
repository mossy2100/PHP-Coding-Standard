<?php

declare(strict_types=1);

namespace PERCS31\Tests\Attributes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Attributes.AttributePlacement sniff.
 */
class AttributePlacementUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
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
            // A closure argument: two spaces in a single-line list, inline in a split list, and mis-indented.
            149 => 1,
            151 => 1,
            155 => 1,
            // A blank line, and a comment, between the last attribute and its target.
            180 => 1,
            184 => 1,
            // A blank line after an attribute with a comment at the end of its line.
            199 => 1,
            // A constant, unlike an enum case.
            237 => 1,
            // A comment before a parameter, when set.
            245 => 1,
            // A hook's parameter: two spaces, and inline in a split list.
            293 => 1,
            309 => 1,
            // A method called get.
            316 => 1,
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
