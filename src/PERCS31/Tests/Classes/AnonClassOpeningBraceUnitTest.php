<?php

declare(strict_types=1);

namespace PERCS31\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Classes.AnonClassOpeningBrace sniff.
 *
 * The fixture turns off the parent's BraceOnNewLine code on its first line, as the PERCS31 ruleset excludes it, and
 * unit tests ignore a ruleset's excludes.
 */
class AnonClassOpeningBraceUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // No space, and two spaces, before the brace.
            22 => 1,
            25 => 1,
            // Code, and a comment, after the brace.
            30 => 1,
            33 => 1,
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
