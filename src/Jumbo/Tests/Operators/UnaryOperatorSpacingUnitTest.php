<?php

declare(strict_types=1);

namespace Jumbo\Tests\Operators;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Operators.UnaryOperatorSpacing sniff.
 */
class UnaryOperatorSpacingUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // A space after +.
            14 => 1,
            // A line break after +.
            15 => 1,
            // A space after ~.
            17 => 1,
            // A space after @.
            18 => 1,
            // A comment between + and its operand (not fixable).
            19 => 1,
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
