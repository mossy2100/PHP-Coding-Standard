<?php

declare(strict_types=1);

namespace Jumbo\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.ControlStructures.StatementAfterClosingBrace sniff.
 */
class StatementAfterClosingBraceUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        if ($testFile === 'StatementAfterClosingBraceUnitTest.1.inc') {
            // A block declare in the header, after a docblock and a declare statement: FileHeader reports it.
            return [];
        }

        return [
            // A statement after each kind of control structure, including a declare outside the header.
            48 => 1,
            52 => 1,
            58 => 1,
            64 => 1,
            68 => 1,
            72 => 1,
            76 => 1,
            81 => 1,
            87 => 1,
            93 => 1,
            98 => 1,
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
