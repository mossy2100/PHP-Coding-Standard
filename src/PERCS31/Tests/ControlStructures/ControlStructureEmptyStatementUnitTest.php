<?php

declare(strict_types=1);

namespace PERCS31\Tests\ControlStructures;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.ControlStructures.ControlStructureEmptyStatement sniff.
 */
class ControlStructureEmptyStatementUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // A while, for, foreach and if with no body.
            25 => 1,
            26 => 1,
            27 => 1,
            28 => 1,
            // An elseif with no body.
            32 => 1,
            // An else with no body, without and with a space.
            36 => 1,
            40 => 1,
            // An else with a comment before the semicolon: not fixable.
            44 => 1,
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
