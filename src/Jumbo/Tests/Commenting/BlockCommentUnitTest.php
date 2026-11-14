<?php

declare(strict_types=1);

namespace Jumbo\Tests\Commenting;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Commenting.BlockComment sniff.
 */
class BlockCommentUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // A line indented too little, a closer not lined up, twice, and a closer's line indented too little.
            // Then the spaces inside a comment on one line, and before and after it.
            44 => 1,
            50 => 1,
            55 => 1,
            59 => 1,
            63 => 1,
            64 => 1,
            65 => 1,
            66 => 1,
            67 => 2,
            68 => 2,
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
