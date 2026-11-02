<?php

declare(strict_types=1);

namespace Mossy2100\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Mossy2100.Classes.ClassDeclaration sniff.
 */
class ClassDeclarationUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        return [
            // A non-empty class with its brace on the same line.
            17 => 1,
            // Two spaces after the class keyword.
            21 => 1,
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
