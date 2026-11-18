<?php

declare(strict_types=1);

namespace PERCS31\Tests\Operators;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Operators.UnaryOperatorSpacing sniff.
 */
class UnaryOperatorSpacingUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        // The default settings, which only check the reference operator.
        if ($testFile === 'UnaryOperatorSpacingUnitTest.1.inc') {
            return [
                // A function that returns by reference, a by-reference parameter and a by-reference variadic.
                58 => 3,
                // A reference assignment, with a space and with a line break.
                63 => 1,
                64 => 1,
                // A foreach value, without and with a key.
                66 => 1,
                69 => 1,
                // An array's values, and a destructuring list's.
                72 => 2,
                73 => 2,
                // A closure that returns by reference, and its use list.
                74 => 2,
                // An arrow function that returns by reference, and its parameter.
                77 => 2,
                // A comment between & and its operand (not fixable).
                78 => 1,
                // A reference assignment written `=& $a`.
                79 => 1,
                // A by-reference hook, long and inline.
                84 => 1,
                87 => 1,
            ];
        }

        // Every operator's property on.
        return [
            // A space after +.
            33 => 1,
            // A line break after +.
            34 => 1,
            // A space after ~.
            36 => 1,
            // A space after @.
            37 => 1,
            // A comment between + and its operand (not fixable).
            38 => 1,
            // A space after + following a cast, and following a concatenation.
            39 => 1,
            40 => 1,
            // A space after -, and a line break.
            41 => 1,
            42 => 1,
            // Two negations: only the space after the second can go.
            44 => 1,
            // A negation after a binary -, in a ternary, in an array and after a comparison.
            45 => 1,
            46 => 2,
            47 => 2,
            48 => 1,
            // A space after !, and a line break.
            49 => 1,
            50 => 1,
            // Two logical nots.
            52 => 2,
            // A comment between ! and its operand (not fixable).
            53 => 1,
            // The reference operator, which is on by default.
            54 => 1,
            // Default values.
            56 => 2,
            // A condition, a return and a match arm.
            58 => 1,
            59 => 1,
            63 => 2,
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
