<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Classes;

use Jumbo\Helpers\SkipsMemberOnLastLineTrait;
use SlevomatCodingStandard\Sniffs\Classes\ConstantSpacingSniff as SlevomatSniff;

/**
 * Checks the blank lines after a constant, skipping one on the file's last line, which crashes the parent.
 */
class ConstantSpacingSniff extends SlevomatSniff
{
    use SkipsMemberOnLastLineTrait;
}
