<?php

declare(strict_types=1);

namespace PERCS31\Tests\Operators;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Operators.OperatorSpacing sniff.
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
