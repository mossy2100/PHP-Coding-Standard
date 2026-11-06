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
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
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
            // Bodiless lists: no spaces, a space before ;, double spaces, and over several lines.
            66 => 1,
            68 => 1,
            70 => 1,
            72 => 1,
            // A space before a bodiless hook's ; in a list with bodies.
            87 => 1,
            // With checkReferenceSpacing, a space after a by-reference hook's &, long and inline.
            100 => 1,
            103 => 1,
            // A hook body on one line.
            115 => 2,
            // The whole list on one line: the hook, its body, and both closing braces.
            119 => 4,
            // A hook's closing brace after the body.
            124 => 1,
            // A hook's body after its opening brace.
            129 => 1,
            // A hook with parameters and a body on one line.
            135 => 2,
            // Two hooks on one line.
            140 => 5,
            // A short hook and a hook with a body, inline.
            144 => 5,
            // An empty body, inline: the hook, and the list's closing brace.
            147 => 2,
            // A body of only a comment.
            151 => 1,
            // A comment before the body, in the way of the fix (not fixable).
            156 => 1,
            // The space before a hook's own brace, when set.
            219 => 1,
            222 => 1,
            229 => 1,
            233 => 1,
            239 => 1,
            243 => 1,
            249 => 1,
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
