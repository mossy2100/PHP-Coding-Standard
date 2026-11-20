<?php

declare(strict_types=1);

namespace Jumbo\Tests\Strings;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Strings.RequireBracedVariables sniff.
 */
class RequireBracedVariablesUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [17 => 1, 18 => 2, 19 => 4, 20 => 1, 21 => 1, 22 => 2, 25 => 2];
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
