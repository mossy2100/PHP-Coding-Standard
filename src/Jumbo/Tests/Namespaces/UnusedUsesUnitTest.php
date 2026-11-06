<?php

declare(strict_types=1);

namespace Jumbo\Tests\Namespaces;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.Namespaces.UnusedUses sniff.
 */
class UnusedUsesUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        // UnusedUsesUnitTest.inc has a miscased keyword, so the whole file is skipped.
        if ($testFile !== 'UnusedUsesUnitTest.1.inc') {
            return [];
        }

        return [
            // An unused class import.
            8 => 1,
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
