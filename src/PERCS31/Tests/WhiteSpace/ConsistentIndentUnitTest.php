<?php

declare(strict_types=1);

namespace PERCS31\Tests\WhiteSpace;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.WhiteSpace.ConsistentIndent sniff.
 */
class ConsistentIndentUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        if ($testFile === 'ConsistentIndentUnitTest.3.inc') {
            return [
                // A closure: assigned, a call's only argument, in a split call, and in an array.
                5 => 1,
                9 => 1,
                15 => 1,
                31 => 1,
                // An anonymous class: in a method, and a property.
                23 => 1,
                26 => 1,
                // A switch: a case's body, and a default label.
                37 => 1,
                43 => 1,
                // A closure in a match arm, in an if in a closure, and in a switch in a closure.
                50 => 1,
                57 => 1,
                62 => 1,
                // A closure returned by an arrow function.
                68 => 1,
            ];
        }

        if ($testFile === 'ConsistentIndentUnitTest.2.inc') {
            return [
                // After a PHP opening tag at the start of its line, in a file with markup.
                29 => 1,
            ];
        }

        return [
            // A statement indented further than the function body.
            27 => 1,
            // A statement indented further than an if body.
            30 => 1,
            // A statement indented further than a property hook's body.
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
