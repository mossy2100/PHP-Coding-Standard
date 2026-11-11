<?php

declare(strict_types=1);

namespace PERCS31\Tests\PHP;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.PHP.LowerCaseKeyword sniff.
 */
class LowerCaseKeywordUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // The parent's check.
            17 => 1,
            18 => 1,
            // A hook's name: with a short body, and with a parameter list.
            25 => 1,
            26 => 1,
            // After an attribute, and after final. A call in a hook's body isn't a hook.
            30 => 1,
            33 => 1,
            // After a by-reference &.
            40 => 1,
            // Hooks with no body.
            52 => 2,
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
