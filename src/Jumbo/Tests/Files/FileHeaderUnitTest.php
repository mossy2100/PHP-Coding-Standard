<?php

declare(strict_types=1);

namespace Jumbo\Tests\Files;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Files.FileHeader sniff.
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
        if ($testFile !== 'FileHeaderUnitTest.1.inc') {
            // The other files use a declare statement between PHP tags on the first line, which is allowed.
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
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(string $testFile = ''): array
    {
        return [];
    }
}
