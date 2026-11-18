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

        $belowFirstLine = ['FileHeaderUnitTest.8.inc', 'FileHeaderUnitTest.9.inc', 'FileHeaderUnitTest.23.inc'];
        if (in_array($testFile, $belowFirstLine, true)) {
            // A file with markup, declaring strict types below the first line. File 23 sets strict_types to 0, and
            // file 24 has that on the first line, so nothing is reported.
            return [3 => 1];
        }

        if ($testFile === 'FileHeaderUnitTest.19.inc' || $testFile === 'FileHeaderUnitTest.20.inc') {
            // The same, after a shebang line. File 21 has the declaration on the second line, and file 22 has a
            // shebang line and no markup, so nothing is reported.
            return [4 => 1];
        }

        if ($testFile === 'FileHeaderUnitTest.7.inc') {
            // No blank line after a use statement with several imports.
            return [7 => 1];
        }

        if ($testFile === 'FileHeaderUnitTest.13.inc' || $testFile === 'FileHeaderUnitTest.14.inc') {
            // An import after the rest of the code, in a PHP file and in a file with markup. File 15 has a closure's
            // and a trait's use, and a second namespace with its own imports, so nothing is reported.
            return $testFile === 'FileHeaderUnitTest.13.inc' ? [7 => 1] : [9 => 1];
        }

        if ($testFile === 'FileHeaderUnitTest.12.inc' || $testFile === 'FileHeaderUnitTest.18.inc') {
            // An import in a file that starts with markup, so it can't be at the top of the file. The rest of the
            // header in file 12 isn't checked.
            return [4 => 1];
        }

        if ($testFile === 'FileHeaderUnitTest.25.inc') {
            // An import after markup, in a file with a declare statement between PHP tags on the first line. File 26
            // has its imports in the next PHP block, before any markup, so nothing is reported.
            return [5 => 1];
        }

        if ($testFile === 'FileHeaderUnitTest.27.inc') {
            // The same kind of file, with an import after code in the next PHP block, before any markup.
            return [8 => 1];
        }

        if ($testFile === 'FileHeaderUnitTest.28.inc') {
            // The same kind of file, with an import after a <?= block, in the PHP block after it.
            return [5 => 1];
        }

        if ($testFile === 'FileHeaderUnitTest.30.inc') {
            // A comment at the end of a block's last line, with no blank line after it, or two. File 29 has a comment
            // at the end of every block, each with one blank line after it, so nothing is reported.
            return [3 => 1, 4 => 1];
        }

        if ($testFile === 'FileHeaderUnitTest.2.inc') {
            // A blank line between a declare statement and a block declare.
            return [3 => 1];
        }

        if ($testFile !== 'FileHeaderUnitTest.1.inc') {
            // The other files use a declare statement between PHP tags on the first line, which is allowed, several
            // imports in one use statement, or markup with no declare statement, or start with markup, so have no
            // file header. Files 16 and 17 are templates whose only PHP tag shares the first line with a statement
            // or a docblock, which section 3 allows in a file with markup.
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
