<?php

declare(strict_types=1);

namespace Jumbo\Tests\Commenting;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Commenting.TrailingComment sniff.
 */
class TrailingCommentUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // No space, or several, before a comment after a statement, an argument and a brace.
            16 => 1,
            17 => 1,
            18 => 1,
            20 => 1,
            21 => 1,
            23 => 1,
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
