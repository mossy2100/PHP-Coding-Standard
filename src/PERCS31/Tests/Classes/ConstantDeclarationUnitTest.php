<?php

declare(strict_types=1);

namespace PERCS31\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Classes.ConstantDeclaration sniff.
 */
class ConstantDeclarationUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Two spaces after const, before a name and before a type.
            18 => 1,
            19 => 1,
            // Two spaces after the type.
            20 => 1,
            // Both.
            21 => 2,
            // A line break after const, and after the type.
            23 => 1,
            25 => 1,
            // Three spaces after a union type.
            26 => 1,
            // In an interface and an enum.
            32 => 1,
            39 => 1,
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
