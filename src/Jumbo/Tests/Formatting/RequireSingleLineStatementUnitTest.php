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
            73 => 1,
            75 => 1,
            77 => 1,
            80 => 1,
            83 => 1,
            86 => 1,
            88 => 1,
            90 => 1,
            94 => 1,
            97 => 1,
            101 => 1,
            106 => 1,
            // The lines around a body, or a call or array too long to collapse.
            112 => 1,
            116 => 1,
            119 => 1,
            121 => 1,
            125 => 1,
            132 => 1,
            134 => 1,
            140 => 1,
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
