<?php

declare(strict_types=1);

namespace Jumbo\Tests\Functions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Functions.RequireSingleLineSignature sniff.
 */
class RequireSingleLineSignatureUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Every function or closure signature that fits on one line.
            30 => 1,
            35 => 1,
            41 => 1,
            45 => 1,
            50 => 1,
            55 => 1,
            62 => 1,
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
