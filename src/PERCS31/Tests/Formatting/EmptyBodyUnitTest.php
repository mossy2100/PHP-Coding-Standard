<?php

declare(strict_types=1);

namespace PERCS31\Tests\Formatting;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Formatting.EmptyBody sniff.
 */
class EmptyBodyUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Closure with the braces on separate lines.
            50 => 1,
            // Anonymous class with the braces on separate lines.
            64 => 1,
            // Anonymous class with no space before the braces.
            66 => 1,
            // Closure with a space inside the braces.
            78 => 1,
            // Closure with no space before the braces.
            79 => 1,
            // Closure with a use list, and the braces on separate lines.
            80 => 1,
            // Closure with a multi-line signature.
            84 => 1,
        ];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(): array
    {
        return [
            // Class with the braces on separate lines.
            10 => 1,
            // No space before the braces.
            13 => 1,
            // Two spaces before the braces.
            15 => 1,
            // A space inside the braces.
            17 => 1,
            // Promoted constructor.
            26 => 1,
            // Multi-line signature.
            32 => 1,
            // Function.
            56 => 1,
        ];
    }
}
