<?php

declare(strict_types=1);

namespace Jumbo\Tests\TypeHints;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.TypeHints.DeclareStrictTypes sniff.
 */
class DeclareStrictTypesUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        if ($testFile === 'DeclareStrictTypesUnitTest.3.inc' || $testFile === 'DeclareStrictTypesUnitTest.6.inc') {
            // A block declare: the fix adds a separate statement above it.
            return [1 => 1];
        }

        // A block declare after a shebang line, which isn't fixable, then a file with markup before its opening tag,
        // where the fix adds the declare between its own PHP tags on the first line.
        $markupBefore = [
            'DeclareStrictTypesUnitTest.7.inc',
            'DeclareStrictTypesUnitTest.8.inc',
            'DeclareStrictTypesUnitTest.9.inc',
            'DeclareStrictTypesUnitTest.10.inc',
        ];
        if (in_array($testFile, $markupBefore, true)) {
            return [2 => 1];
        }

        if ($testFile === 'DeclareStrictTypesUnitTest.14.inc') {
            // A file with markup after its PHP: the fix adds the declare on the first line too.
            return [1 => 1];
        }

        if ($testFile === 'DeclareStrictTypesUnitTest.13.inc') {
            // A blank line between two declare statements.
            return [3 => 1];
        }

        if ($testFile === 'DeclareStrictTypesUnitTest.11.inc' || $testFile === 'DeclareStrictTypesUnitTest.12.inc') {
            // Two declare statements with no blank line, as PER requires.
            return [];
        }

        if ($testFile === 'DeclareStrictTypesUnitTest.4.inc') {
            // No strict_types: the fix adds it with no spaces, as PER requires.
            return [3 => 1];
        }

        if ($testFile === 'DeclareStrictTypesUnitTest.5.inc') {
            // Spaces around the =.
            return [3 => 1];
        }

        if ($testFile !== 'DeclareStrictTypesUnitTest.2.inc') {
            // The other files use a declare statement between PHP tags on the first line, which is allowed.
            return [];
        }

        return [
            // strict_types=0, which the parent still reports.
            1 => 1,
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
