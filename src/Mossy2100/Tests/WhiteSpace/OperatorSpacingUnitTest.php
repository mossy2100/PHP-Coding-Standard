<?php

declare(strict_types=1);

namespace Mossy2100\Tests\WhiteSpace;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Mossy2100.WhiteSpace.OperatorSpacing sniff.
 */
class OperatorSpacingUnitTest extends AbstractSniffTestCase
{
    /**
     * Skip the tests before PHP 8.5, which has no `T_PIPE` token.
     *
     * @return bool
     */
    protected function shouldSkipTest(): bool
    {
        return !defined('T_PIPE');
    }

    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        return [
            // No space either side.
            13 => 2,
            // Two spaces either side.
            14 => 2,
        ];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(string $testFile = ''): array
    {
        return [];
    }
}
