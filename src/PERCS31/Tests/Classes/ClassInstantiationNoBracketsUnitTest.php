<?php

declare(strict_types=1);

namespace PERCS31\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Classes.ClassInstantiationNoBrackets sniff.
 */
class ClassInstantiationNoBracketsUnitTest extends AbstractSniffTestCase
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
            // ->.
            16 => 1,
            // ?->.
            17 => 1,
            // ::.
            18 => 1,
            // Spaces inside the parentheses, and a fully qualified name.
            19 => 1,
            // A variable class name.
            20 => 1,
            // An anonymous class.
            21 => 1,
            // A comment between the parentheses and the expression (not fixable).
            26 => 1,
            27 => 1,
            // A keyword straight before the parentheses: echo, print, yield, throw and return.
            33 => 1,
            34 => 1,
            35 => 1,
            36 => 1,
            37 => 1,
            // After a block's closing brace.
            55 => 1,
            // After include, include_once, require and require_once.
            58 => 1,
            59 => 1,
            60 => 1,
            61 => 1,
        ];
    }
}
