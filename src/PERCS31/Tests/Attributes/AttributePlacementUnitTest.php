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
            // An enum case, as for a constant: one attribute, several in a block, and one not fixable.
            223 => 1,
            228 => 1,
            230 => 1,
            235 => 1,
            // A constant in an enum.
            237 => 1,
            // A comment on its own line before a parameter.
            241 => 1,
            245 => 1,
            // A hook with a body in braces, unlike a short hook.
            270 => 1,
            // Two spaces after a closure's attribute outside a call.
            161 => 1,
            171 => 1,
            // A hook's parameter: two spaces, and inline in a split list.
            293 => 1,
            309 => 1,
            // A method called get.
            316 => 1,
            // Two spaces before a short hook on the same line.
            322 => 1,
            327 => 1,
            331 => 1,
            // An enum case on its attribute's line, after two spaces, and after one.
            350 => 1,
            352 => 1,
            // Two spaces before a closure outside a call.
            358 => 1,
            363 => 1,
            // An enum case on its attribute's line, and two spaces after an attribute on a closure outside a call.
            369 => 1,
            372 => 1,
            // Two or more attribute blocks on an enum case, one block holding two attributes, and one not fixable.
            378 => 1,
            381 => 1,
            383 => 1,
            385 => 1,
            // Two attribute blocks on a short hook.
            391 => 1,
            // Two spaces between inline blocks in a single-line parameter list, and three in an argument list.
            402 => 1,
            404 => 1,
            // Code before the blocks.
            408 => 1,
            // A readonly anonymous class, with its attribute on the line of `new`, and on the line of `readonly`.
            417 => 1,
            421 => 1,
            // A hook with a body in braces: one attribute block on its line, and two.
            432 => 1,
            445 => 1,
            // An attribute with no space before its target.
            457 => 1,
            459 => 1,
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
