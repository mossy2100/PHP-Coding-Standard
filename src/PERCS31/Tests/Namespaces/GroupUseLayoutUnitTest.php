<?php

declare(strict_types=1);

namespace PERCS31\Tests\Namespaces;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Namespaces.GroupUseLayout sniff.
 */
class GroupUseLayoutUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // The first name on the opening brace's line.
            18 => 1,
            // Two names on one line.
            24 => 1,
            // The closing brace after the last name.
            31 => 1,
            // All three: the first name and a second on the brace's line, and the closing brace after the last.
            34 => 2,
            35 => 1,
            // A comment before the first name, which isn't fixed.
            38 => 1,
            // A space inside both braces of a group use on one line, before the closing one, and after the opening one.
            43 => 2,
            44 => 1,
            45 => 1,
            // A blank line after the opening brace, and before the closing one, after a name and after a comment.
            53 => 1,
            56 => 1,
            60 => 1,
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
