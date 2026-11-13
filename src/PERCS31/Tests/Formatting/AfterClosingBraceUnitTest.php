<?php

declare(strict_types=1);

namespace PERCS31\Tests\Formatting;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Formatting.AfterClosingBrace sniff.
 */
class AfterClosingBraceUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        return match ($testFile) {
            // Classes, functions and property hooks.
            'AfterClosingBraceUnitTest.1.inc' => [
                // A comment after a class's, a method's and a function's brace, and a statement after a function's.
                62 => 1,
                69 => 1,
                76 => 1,
                81 => 1,
                // An empty statement, then a comment with a statement after it.
                87 => 1,
                92 => 2,
                // A comment after a hook body's and a hook list's brace, and after a promoted property's comma.
                101 => 1,
                105 => 1,
                107 => 1,
                120 => 1,
                // A statement after a trait use block's and a hook list's brace, one with a comment before it.
                130 => 1,
                134 => 1,
                138 => 2,
                // A method after a method's brace.
                151 => 1,
            ],
            // A statement after a function's brace, then after each kind of control structure's, including a
            // declare outside the header.
            'AfterClosingBraceUnitTest.2.inc' => [
                43 => 1,
                47 => 1,
                51 => 1,
                57 => 1,
                63 => 1,
                67 => 1,
                71 => 1,
                75 => 1,
                80 => 1,
                86 => 1,
                92 => 1,
                97 => 1,
            ],
            // A namespace after a namespace's brace.
            'AfterClosingBraceUnitTest.4.inc' => [9 => 1],
            // A block declare in the header, after a docblock and a declare statement: FileHeader reports it.
            default => [],
        };
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
