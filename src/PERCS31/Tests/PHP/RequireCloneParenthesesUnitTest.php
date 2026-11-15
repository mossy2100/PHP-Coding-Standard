<?php

declare(strict_types=1);

namespace PERCS31\Tests\PHP;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.PHP.RequireCloneParentheses sniff.
 */
class RequireCloneParenthesesUnitTest extends AbstractSniffTestCase
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
            // A variable.
            15 => 1,
            // A property.
            16 => 1,
            // A nullsafe call and an array access, before ??.
            17 => 1,
            // A static property.
            18 => 1,
            // A static call on a fully qualified name.
            19 => 1,
            // A new expression.
            20 => 1,
            // A space before the parentheses, then a dereference.
            21 => 1,
            // A variable variable.
            22 => 1,
            // A comment: not fixable.
            23 => 1,
            // Parentheses that hold part of what is cloned: a simple expression, three times, then an array access.
            26 => 1,
            27 => 1,
            28 => 1,
            29 => 1,
            // An expression that keeps its own parentheses, twice.
            30 => 1,
            31 => 1,
            // A member named in braces, with and without parentheses, and by a variable variable.
            32 => 1,
            33 => 1,
            34 => 1,
            // A comment: not fixable.
            35 => 1,
            // A variable variable with a member, in braces, and in parentheses that hold part of what is cloned.
            38 => 1,
            39 => 1,
            40 => 1,
        ];
    }
}
