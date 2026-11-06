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
            // File-scope constant.
            27 => 1,
            // Typed class constant.
            31 => 1,
            // Untyped protected class constant.
            33 => 1,
            // Two underscores.
            35 => 1,
            // The second constant in a statement.
            37 => 1,
            // Interface constant.
            42 => 1,
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
