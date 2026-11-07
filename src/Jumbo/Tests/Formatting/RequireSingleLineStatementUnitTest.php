<?php

declare(strict_types=1);

namespace Jumbo\Tests\Formatting;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Formatting.RequireSingleLineStatement sniff.
 */
class RequireSingleLineStatementUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Every statement or case label that fits on one line.
            54 => 1,
            56 => 1,
            58 => 1,
            61 => 1,
            64 => 1,
            66 => 1,
            68 => 1,
            70 => 1,
            73 => 1,
            75 => 1,
            77 => 1,
            79 => 1,
            83 => 1,
            88 => 1,
            91 => 1,
            94 => 1,
            98 => 1,
            103 => 1,
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
