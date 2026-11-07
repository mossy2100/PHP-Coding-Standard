<?php

declare(strict_types=1);

namespace Jumbo\Tests\PHP;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.PHP.EmbeddedPhp sniff.
 */
class EmbeddedPhpUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        return match ($testFile) {
            'EmbeddedPhpUnitTest.inc' => [
                // The code one level past the tags, on the first line and a later one.
                29 => 1,
                30 => 1,
                36 => 1,
                // Code on a tag's line.
                41 => 1,
                43 => 1,
                46 => 1,
                51 => 1,
                // Markup on a tag's line.
                54 => 1,
                56 => 1,
                // A short echo tag over several lines (not fixable).
                59 => 1,
                64 => 1,
            ],
            'EmbeddedPhpUnitTest.3.inc' => [
                // The only block in the file, in markup, one level past its tags.
                3 => 1,
            ],
            'EmbeddedPhpUnitTest.5.inc' => [
                // A first tag with only an indent before it.
                2 => 1,
            ],
            default => [],
        };
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
