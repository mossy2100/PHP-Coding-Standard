<?php

declare(strict_types=1);

namespace Jumbo\Tests\Arrays;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Arrays.ArrayDeclaration sniff.
 */
class ArrayDeclarationUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // List.
            8 => 1,
            // Associative array with aligned arrows.
            15 => 1,
            // Empty array.
            21 => 1,
            // Destructuring.
            25 => 1,
            // Nested arrays: outer array only.
            31 => 1,
            // Nested array inside a too-long array.
            42 => 1,
            // Call with trailing commas.
            49 => 1,
            // Array passed to a call.
            57 => 1,
            // Arrow function.
            89 => 1,
            // Wrapped method chain.
            94 => 1,
            // Two arrays on one line: the first only, as the joined line is too long.
            123 => 1,
            // A too-long array, then one that fits on the joined line.
            135 => 1,
            // Inner array after a call and an index, and inside a call: outer array only.
            140 => 1,
            // Inner array after a brace group.
            151 => 1,
            // Configured maximum line length.
            164 => 1,
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
