<?php

declare(strict_types=1);

namespace PERCS31\Tests\NamingConventions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.NamingConventions.ValidConstantName sniff.
 */
class ValidConstantNameUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Typed class constant.
            32 => 1,
            // Untyped protected class constant.
            34 => 1,
            // The second constant in a statement.
            38 => 1,
            // Interface constant.
            43 => 1,
            // A global constant, with checkGlobalConstants on.
            61 => 1,
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
