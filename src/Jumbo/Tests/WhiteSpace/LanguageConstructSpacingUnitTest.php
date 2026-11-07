<?php

declare(strict_types=1);

namespace Jumbo\Tests\WhiteSpace;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.WhiteSpace.LanguageConstructSpacing sniff.
 */
class LanguageConstructSpacingUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Return followed by a line break, two spaces, and a line break before a heredoc.
            47 => 1,
            49 => 1,
            50 => 1,
            // The other constructs, followed by a line break.
            54 => 1,
            56 => 1,
            58 => 1,
            60 => 1,
            62 => 1,
            // Use in a closure, with two spaces.
            64 => 1,
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
