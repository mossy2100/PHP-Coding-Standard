<?php

declare(strict_types=1);

namespace Jumbo\Tests\Arrays;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Arrays.ElementComma sniff.
 */
class ElementCommaUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // A keyed element.
            25 => 1,
            // An unkeyed element.
            30 => 1,
            // After a multi-line call.
            37 => 1,
            // A long-syntax array.
            41 => 1,
            // A comment between the element and its comma (not fixable).
            45 => 1,
            // A short destructuring list.
            55 => 1,
            // A long-syntax destructuring list.
            60 => 1,
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
