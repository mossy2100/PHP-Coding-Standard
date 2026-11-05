<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Classes;

use Jumbo\Helpers\SkipsMemberOnLastLineTrait;
use SlevomatCodingStandard\Sniffs\Classes\EnumCaseSpacingSniff as SlevomatSniff;

/**
 * Checks the blank lines after an enum case, skipping one on the file's last line, which crashes the parent.
 */
class EnumCaseSpacingSniff extends SlevomatSniff
{
    use SkipsMemberOnLastLineTrait;
}
