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
            // A space before the parentheses of clone(), and just inside them.
            22 => 3,
            // Spaces just inside the parentheses.
            25 => 2,
            26 => 2,
            // The first argument on the opening line.
            29 => 1,
            // Two arguments on one line.
            33 => 1,
            // Indented twice.
            36 => 1,
            // A comment before the parentheses, reported with no fix.
            77 => 1,
            78 => 1,
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
