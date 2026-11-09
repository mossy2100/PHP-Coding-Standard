<?php

declare(strict_types=1);

namespace PERCS31\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Classes.PropertyDeclaration sniff.
 */
class PropertyDeclarationUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @param string $testFile The name of the test input file.
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(string $testFile = ''): array
    {
        // PropertyDeclarationUnitTest.1.inc is a live-coding case with an unterminated property, which must produce no
        // errors.
        if ($testFile !== 'PropertyDeclarationUnitTest.inc') {
            return [];
        }

        return [
            // VarUsed + ScopeMissing.
            61 => 2,
            // Underscore: promoted constructor parameters.
            127 => 1,
            128 => 1,
            // HookOrder: bodiless, with &, and with an attribute.
            141 => 1,
            144 => 1,
            147 => 1,
            // HookOrder with orderHooksWithBodies on: arrow hooks, block hooks, and a comment between the hooks.
            165 => 1,
            171 => 1,
            181 => 1,
            // ScopeMissing: promoted constructor parameters with readonly or final, and no visibility.
            196 => 1,
            197 => 1,
            198 => 1,
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
        if ($testFile !== 'PropertyDeclarationUnitTest.inc') {
            return [];
        }

        return [
            // Underscore, which the parent reports as a warning, and the rulesets make an error.
            58 => 1,
        ];
    }
}
