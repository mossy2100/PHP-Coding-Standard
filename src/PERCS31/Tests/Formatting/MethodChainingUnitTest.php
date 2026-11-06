<?php

declare(strict_types=1);

namespace PERCS31\Tests\Formatting;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Formatting.MethodChaining sniff.
 */
class MethodChainingUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // The first method on the object's line.
            31 => 1,
            // The first method after a property access.
            35 => 1,
            // The first method after a nullsafe operator.
            38 => 1,
            // The first method on $this's line.
            43 => 1,
            // A chain inside a function call's argument.
            47 => 1,
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
