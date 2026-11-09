<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Classes;

use Jumbo\Helpers\SkipsPromotedPropertyHookTrait;
use SlevomatCodingStandard\Sniffs\Classes\RequireSingleLineMethodSignatureSniff as SlevomatSniff;

/**
 * Requires a method signature that fits on one line to be written on one line, skipping one with a hook on a promoted
 * constructor parameter.
 *
 * SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature takes the `;` in a hook on a promoted constructor
 * parameter, e.g. `public string $name { set => ucfirst($value); }`, as the end of the signature, so its fix joins only
 * part of it, and phpcbf can't finish. PER Coding Style 3.1, section 4.9, allows a single short hook there. This sniff
 * skips a method with such a hook, and leaves everything else to the parent.
 */
class RequireSingleLineMethodSignatureSniff extends SlevomatSniff
{
    use SkipsPromotedPropertyHookTrait;
}
