<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\TypeHints;

use Jumbo\Helpers\SkipsPromotedPropertyHookTrait;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSpacingSniff as SlevomatSniff;

/**
 * Checks the spacing around parameter type hints, skipping a function with a hook on a promoted constructor parameter.
 *
 * SlevomatCodingStandard.TypeHints.ParameterTypeHintSpacing treats every variable between a function's parentheses as a
 * parameter, so a variable in the body of a hook on a promoted constructor parameter, such as `$value` or `$this`, is
 * reported as a parameter with bad spacing. PER Coding Style 3.1, section 4.9, allows a single short hook there. This
 * sniff skips a function with such a hook, and leaves everything else to the parent. The other parameters of that
 * function aren't checked either.
 */
class ParameterTypeHintSpacingSniff extends SlevomatSniff
{
    use SkipsPromotedPropertyHookTrait;
}
