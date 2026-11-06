<?php

declare(strict_types=1);

namespace PERCS31\Tests\Functions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.Functions.MultiLineFunctionDeclaration sniff.
 */
class MultiLineFunctionDeclarationUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [
            // A non-empty single-line declaration.
            22 => 1,
            // A multi-line declaration with its brace on the next line.
            30 => 1,
            // Arrow functions: the first parameter on the opening line, two parameters on one line, a double indent,
            // and the closing parenthesis after the last parameter.
            41 => 1,
            45 => 1,
            48 => 1,
            52 => 1,
            // A parameter indented too far, after a valid one-line promoted hook.
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
