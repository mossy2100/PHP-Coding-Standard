<?php

declare(strict_types=1);

namespace Mossy2100\Tests\PHP;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Mossy2100.PHP.RequireCloneParentheses sniff.
 */
class RequireCloneParenthesesUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        return [];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(string $testFile = ''): array
    {
        return [
            // A variable.
            12 => 1,
            // A property.
            13 => 1,
            // A nullsafe call and an array access, before ??.
            14 => 1,
            // A static property.
            15 => 1,
            // A static call on a fully qualified name.
            16 => 1,
            // A new expression.
            17 => 1,
            // A space before the parentheses.
            18 => 1,
            // A space before the parentheses, then a dereference: not fixable.
            19 => 1,
            // A variable variable: not fixable.
            20 => 1,
            // A comment: not fixable.
            21 => 1,
        ];
    }
}
