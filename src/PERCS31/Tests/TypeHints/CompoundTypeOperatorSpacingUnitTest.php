<?php

declare(strict_types=1);

namespace PERCS31\Tests\TypeHints;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.TypeHints.CompoundTypeOperatorSpacing sniff.
 */
class CompoundTypeOperatorSpacingUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Spaces either side, before only, and after only.
            21 => 3,
            // A property's type.
            27 => 1,
            // The symbol at the end of a line.
            31 => 1,
            // A set hook's parameter type: a union, an intersection, a DNF type, inside its parentheses, and at a
            // line end.
            44 => 1,
            48 => 1,
            52 => 1,
            56 => 1,
            61 => 1,
            // A multi-catch: spaces around the |, and the | at a line end.
            84 => 1,
            87 => 1,
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
