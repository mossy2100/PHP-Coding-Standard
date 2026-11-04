<?php

declare(strict_types=1);

namespace Jumbo\Tests\Files;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Files.DeclareStatement sniff.
 */
class DeclareStatementUnitTest extends AbstractSniffTestCase
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
            // Several directives: a space after the comma.
            10 => 1,
            // Several directives: a space after declare.
            11 => 1,
            // Several directives: spaces throughout the parentheses, reported once.
            12 => 1,
            // One directive, left to the parent: spaces around the equals sign.
            13 => 2,
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
