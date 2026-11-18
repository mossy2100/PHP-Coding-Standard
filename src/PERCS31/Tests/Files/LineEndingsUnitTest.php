<?php

declare(strict_types=1);

namespace PERCS31\Tests\Files;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Files.LineEndings sniff.
 */
class LineEndingsUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        // A CRLF after an LF first line: after code, at the end of a comment and of two docblock lines, in a single-
        // and a double-quoted string, in a heredoc and a nowdoc, and in markup outside the PHP tags.
        if ($testFile === 'LineEndingsUnitTest.2.inc') {
            return [4 => 1, 5 => 1, 6 => 1, 7 => 1, 10 => 1, 12 => 1, 15 => 1, 18 => 1, 21 => 1];
        }

        // Every line ends in CRLF, so the parent reports the file once, on line 1.
        if ($testFile === 'LineEndingsUnitTest.3.inc') {
            return [1 => 1];
        }

        // Every line ends in LF.
        return [];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(string $testFile = ''): array
    {
        // PHP_CodeSniffer's own warning that the file has mixed line endings, which the PERCS31 ruleset turns off.
        return $testFile === 'LineEndingsUnitTest.2.inc' ? [1 => 1] : [];
    }
}
