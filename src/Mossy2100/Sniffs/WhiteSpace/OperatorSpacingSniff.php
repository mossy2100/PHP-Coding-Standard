<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\OperatorSpacingSniff as SquizSniff;

/**
 * Checks the spacing around operators, including the pipe operator `|>`.
 *
 * PER Coding Style 3.1, section 6.2, says the pipe operator (PHP 8.5) is a binary operator, so it needs one space
 * either side. Squiz.WhiteSpace.OperatorSpacing doesn't know it. PHP_CodeSniffer 4.0 doesn't define `T_PIPE` itself,
 * but passes PHP's own token through on PHP 8.5, so this sniff registers it when it's defined, and leaves everything
 * else to the parent.
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
