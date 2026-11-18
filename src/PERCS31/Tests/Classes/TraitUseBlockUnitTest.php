<?php

declare(strict_types=1);

namespace PERCS31\Tests\Classes;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Classes.TraitUseBlock sniff.
 */
class TraitUseBlockUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // Two spaces, and a line break, after the visibility.
            14 => 1,
            16 => 1,
            // After final.
            19 => 1,
            // A comment in the way (not fixable).
            22 => 1,
            // A statement on the opening brace's line, and the closing brace on its line.
            34 => 2,
            // Blank lines in a conflict block.
            41 => 1,
            43 => 1,
            45 => 1,
            // No blank line after the last trait use, with a comment at the end of its line, before a comment of its
            // own, and before a statement on the same line. A comment at the end of the line, with the class's closing
            // brace next, passes.
            80 => 1,
            86 => 1,
            92 => 1,
            98 => 1,
            // A blank line between two trait uses, after a statement, after a conflict block, and before a comment.
            105 => 1,
            109 => 1,
            111 => 1,
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
