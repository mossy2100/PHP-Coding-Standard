<?php

declare(strict_types=1);

namespace Jumbo\Tests\NamingConventions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.NamingConventions.UpperCaseConstantName sniff.
 */
class UpperCaseConstantNameUnitTest extends AbstractSniffTestCase
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
            // A global constant not in UPPER_CASE, left to the parent.
            26 => 1,
            // A class constant not in UPPER_CASE, left to the parent.
            30 => 1,
            // An enum constant in neither PascalCase nor UPPER_CASE.
            38 => 1,
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
        // Line 21's warning has a severity of 4, so it's hidden at the default severity of 5, which these tests run
        // at. RulesetUnitTest checks it with --warning-severity=4.
        return [];
    }
}
