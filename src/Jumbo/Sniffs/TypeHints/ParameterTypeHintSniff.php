<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\TypeHints;

use Jumbo\Helpers\SkipsPromotedPropertyHookTrait;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff as SlevomatSniff;

/**
 * Requires a type hint on each parameter, skipping a function with a hook on a promoted constructor parameter.
 *
 * SlevomatCodingStandard.TypeHints.ParameterTypeHint treats every variable between a function's parentheses as a
 * parameter, so a variable in the body of a hook on a promoted constructor parameter, such as `$value` or `$this`, is
 * reported as a parameter with no type hint. PER Coding Style 3.1, section 4.9, allows a single short hook there. This
 * sniff skips a function with such a hook, and leaves everything else to the parent. The other parameters of that
 * function aren't checked either.
 */
class ParameterTypeHintSniff extends SlevomatSniff
{
    use SkipsPromotedPropertyHookTrait;
}
