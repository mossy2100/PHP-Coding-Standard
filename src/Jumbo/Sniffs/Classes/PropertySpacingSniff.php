<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Classes;

use Jumbo\Helpers\SkipsMemberOnLastLineTrait;
use SlevomatCodingStandard\Sniffs\Classes\PropertySpacingSniff as SlevomatSniff;

/**
 * Checks the blank lines after a property, skipping one on the file's last line, which crashes the parent.
 */
class PropertySpacingSniff extends SlevomatSniff
{
    use SkipsMemberOnLastLineTrait;
}
