<?php

declare(strict_types=1);

namespace PERCS31\Tests\Files;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Files.Encoding sniff.
 */
class EncodingUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        // A file in ISO-8859-1, reported once, at its first invalid line.
        if ($testFile === 'EncodingUnitTest.2.inc') {
            return [6 => 1];
        }

        // Invalid bytes in markup before the PHP opening tag.
        if ($testFile === 'EncodingUnitTest.3.inc') {
            return [1 => 1];
        }

        // A file in UTF-8, with characters of two and three bytes.
        return [];
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
