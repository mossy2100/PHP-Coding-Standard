<?php

declare(strict_types=1);

namespace Jumbo\Tests\NamingConventions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.NamingConventions.ValidVariableName sniff.
 */
class ValidVariableNameUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(): array
    {
        return [
            // Constructor parameter that isn't promoted.
            23 => 1,
            // Method parameter.
            27 => 1,
            // Local variable.
            30 => 1,
            // Closure parameter, closure local, and both used.
            39 => 1,
            40 => 1,
            41 => 2,
            // Foreach variable.
            43 => 1,
            // Uses of $_methodParam, $_local and $_item.
            51 => 3,
            // Enum method parameter, and its use.
            57 => 1,
            59 => 1,
            // Trait method local.
            67 => 1,
        ];
    }
}
