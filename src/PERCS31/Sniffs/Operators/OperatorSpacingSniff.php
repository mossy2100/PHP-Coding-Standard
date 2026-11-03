<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Operators;

use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\OperatorSpacingSniff as SquizSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks the spacing around binary operators, including the pipe operator `|>`.
 *
 * PER Coding Style 3.1, section 6.2, says all binary operators MUST be preceded and followed by at least one space, and
 * section 6 allows more for readability. This sniff extends Squiz.WhiteSpace.OperatorSpacing, which requires exactly
 * one space, to the operators PSR12.Operators.OperatorSpacing also covers (`&&`, `||`, `and`, `or`, `xor` and `.`),
 * and to `|>` (PHP 8.5). PHP_CodeSniffer 4.0 doesn't define `T_PIPE` itself, but passes PHP's own token through on
 * PHP 8.5, so this sniff registers it when it's defined.
 *
 * The PERCS31 ruleset excludes the parent's `SpacingBefore` and `SpacingAfter` codes, and their `Amp` versions, which
 * report more than one space, so only a missing space is reported. A standard that wants exactly one space, as
 * Mossy2100 does, turns them back on.
 */
class OperatorSpacingSniff extends SquizSniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        $targets = [...parent::register(), ...array_values(Tokens::BOOLEAN_OPERATORS), T_STRING_CONCAT];
        if (defined('T_PIPE')) {
            $targets[] = T_PIPE;
        }

        return array_values(array_unique($targets));
    }
}
