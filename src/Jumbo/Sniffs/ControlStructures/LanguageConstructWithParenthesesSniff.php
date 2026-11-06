<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\ControlStructures;

use SlevomatCodingStandard\Sniffs\ControlStructures\LanguageConstructWithParenthesesSniff as SlevomatSniff;

/**
 * Disallows parentheses around the argument of a language construct, such as `echo('x')`, except `exit` and `die`.
 *
 * SlevomatCodingStandard.ControlStructures.LanguageConstructWithParentheses reports `die()` and fixes it to `die`.
 * PER Coding Style 3.1, section 4.7, says `exit()` and `die()` SHOULD always have parentheses, which
 * Universal.PHP.RequireExitDieParentheses checks, so the two sniffs would undo each other's fixes. This sniff
 * registers every token the parent does except `exit` and `die`, and leaves everything else to the parent.
 */
class LanguageConstructWithParenthesesSniff extends SlevomatSniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int, int|string>
     */
    public function register(): array
    {
        return array_values(array_filter(parent::register(), static fn(int|string $token): bool => $token !== T_EXIT));
    }
}
