<?php

declare(strict_types=1);

namespace PERCS31\Tests\Files;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Files.FileHeader sniff.
 */
class FileHeaderUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        if ($testFile === 'FileHeaderUnitTest.5.inc') {
            // A statement after a block declare, where section 3 wants a blank line.
            return [6 => 1];
        }

        if ($testFile === 'FileHeaderUnitTest.8.inc' || $testFile === 'FileHeaderUnitTest.9.inc') {
            // A file with markup, declaring strict types below the first line.
            return [3 => 1];
        }

        if ($testFile === 'FileHeaderUnitTest.7.inc') {
            // No blank line after a use statement with several imports.
            return [7 => 1];
        }

        if ($testFile === 'FileHeaderUnitTest.2.inc') {
            // A blank line between a declare statement and a block declare.
            return [3 => 1];
        }

        if ($testFile !== 'FileHeaderUnitTest.1.inc') {
            // The other files use a declare statement between PHP tags on the first line, which is allowed, several
            // imports in one use statement, or markup with no declare statement, or start with markup, so have no
            // file header.
            return [];
        }

        return [
            // An ordinary header with no blank lines, which the parent still checks.
            1 => 1,
            2 => 1,
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
