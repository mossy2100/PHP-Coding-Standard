<?php

declare(strict_types=1);

namespace OceanMoon\Tests\PHP;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for OceanMoon.PHP.DisallowMultiConstantDefinition sniff.
 */
class DisallowMultiConstantDefinitionUnitTest extends AbstractSniffTestCase
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
            // Basic multi-constant definition.
            23 => 1,
            // Still detected after a parenthesized call.
            27 => 1,
            // Still detected after a short array literal.
            30 => 1,
            // Multi-constant definition at file scope.
            37 => 1,
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
