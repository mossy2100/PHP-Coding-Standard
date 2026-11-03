<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Operators;

use PHP_CodeSniffer\Standards\PSR12\Sniffs\Operators\OperatorSpacingSniff as Psr12Sniff;

/**
 * Requires at least one space around binary operators, including the pipe operator `|>`.
 *
 * PER Coding Style 3.1, section 6.2, says all binary operators MUST be preceded and followed by at least one space, and
 * section 6 allows more for readability. PSR12.Operators.OperatorSpacing checks that, but doesn't know `|>` (PHP 8.5).
 * PHP_CodeSniffer 4.0 doesn't define `T_PIPE` itself, but passes PHP's own token through on PHP 8.5, so this sniff
 * registers it when it's defined, and leaves everything else to the parent.
 */
class OperatorSpacingSniff extends Psr12Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        $targets = parent::register();
        if (defined('T_PIPE')) {
            $targets[] = T_PIPE;
        }

        return $targets;
    }
}
