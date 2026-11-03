<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\OperatorSpacingSniff as SquizSniff;

/**
 * Requires exactly one space around operators, including the pipe operator `|>`.
 *
 * PER Coding Style allows more than one space around an operator, which PERCS31.Operators.OperatorSpacing checks. This
 * standard allows only one, so assignments and arrows aren't aligned. Squiz.WhiteSpace.OperatorSpacing doesn't know
 * `|>` (PHP 8.5). PHP_CodeSniffer 4.0 doesn't define `T_PIPE` itself, but passes PHP's own token through on PHP 8.5,
 * so this sniff registers it when it's defined, and leaves everything else to the parent.
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
        $targets = parent::register();
        if (defined('T_PIPE')) {
            $targets[] = T_PIPE;
        }

        return $targets;
    }
}
