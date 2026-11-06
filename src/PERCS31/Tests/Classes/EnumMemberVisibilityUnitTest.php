<?php

declare(strict_types=1);

namespace PERCS31\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Classes.EnumMemberVisibility sniff.
 */
class EnumMemberVisibilityUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // A protected method.
            41 => 1,
            // A protected static method, after an attribute.
            47 => 1,
            // A final protected method; the fix also removes final.
            52 => 1,
            // A protected constant.
            56 => 1,
            // A final protected constant; the fix also removes final.
            58 => 1,
            // A protected constant, after an attribute.
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
