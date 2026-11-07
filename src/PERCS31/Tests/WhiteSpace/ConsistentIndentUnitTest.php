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
