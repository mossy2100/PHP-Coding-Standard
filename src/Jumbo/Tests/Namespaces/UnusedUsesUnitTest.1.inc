<?php

// No miscased keyword: the parent's checks run.

namespace Vendor\Package;

use function Vendor\Helpers\helper;
use Vendor\Other\Unused;

function run(): string
{
    return helper();
}
