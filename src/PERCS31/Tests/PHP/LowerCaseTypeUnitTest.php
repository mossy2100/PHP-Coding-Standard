<?php

declare(strict_types=1);

namespace PERCS31\Tests\PHP;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.PHP.LowerCaseType sniff.
 */
class LowerCaseTypeUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // The parent's checks: a typed constant, a property, two parameters and a return type, and a cast.
            23 => 1,
            25 => 1,
            27 => 3,
            29 => 1,
            // A set hook's parameter: a union, a nullable type, and one with a type read as a name.
            37 => 2,
            41 => 1,
            47 => 2,
            // After an attribute, once.
            52 => 1,
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
