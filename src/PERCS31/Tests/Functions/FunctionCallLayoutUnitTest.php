<?php

declare(strict_types=1);

namespace PERCS31\Tests\Functions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Functions.FunctionCallLayout sniff.
 */
class FunctionCallLayoutUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Spaces just inside the parentheses.
            23 => 2,
            24 => 2,
            // The first argument on the opening line.
            27 => 1,
            // Two arguments on one line.
            31 => 1,
            // Indented twice.
            34 => 1,
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
