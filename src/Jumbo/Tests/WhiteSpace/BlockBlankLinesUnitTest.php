<?php

declare(strict_types=1);

namespace Jumbo\Tests\WhiteSpace;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for Jumbo.WhiteSpace.BlockBlankLines sniff.
 */
class BlockBlankLinesUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // A block declare: after its opening brace, and before its closing one.
            43 => 1,
            45 => 1,
            // A hook list's opening brace, then a hook body's braces.
            52 => 1,
            54 => 1,
            56 => 1,
            // Two blank lines after a hook body's opening brace.
            60 => 1,
            61 => 1,
            // The hook list's closing brace.
            64 => 1,
            // A hook list with a short hook.
            69 => 1,
            71 => 1,
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
