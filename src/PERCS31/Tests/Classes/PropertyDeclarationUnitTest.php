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
            // LeadingUnderscore.
            58 => 1,
            // VarUsed + ScopeMissing.
            61 => 2,
            // LeadingUnderscore: promoted constructor parameters.
            127 => 1,
            128 => 1,
            // ScopeMissing: promoted constructor parameters with readonly or final, and no visibility.
            139 => 1,
            140 => 1,
            141 => 1,
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
            // The parent's Underscore warning. It can't skip a double underscore, so the PERCS31 ruleset excludes it.
            58 => 1,
            152 => 1,
        ];
    }
}
