<?php

declare(strict_types=1);

namespace PERCS31\Tests\Functions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Functions.NullableTypeDeclaration sniff.
 */
class NullableTypeDeclarationUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // A constant and a property.
            41 => 1,
            43 => 1,
            // A property with a hook, and a set hook's parameter.
            45 => 1,
            46 => 1,
            // A set hook's parameter of type array.
            50 => 1,
            // A method's parameter and return type.
            55 => 2,
            // A set hook's parameter, over a line break, and with a comment (not fixable).
            61 => 1,
            67 => 1,
            // A closure's and an arrow function's parameter and return type.
            75 => 2,
            78 => 2,
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
