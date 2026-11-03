<?php

// A use keyword in the wrong case: the file is skipped, so nothing is reported here.

namespace Vendor\Package;

use Function Vendor\Helpers\helper;
use Vendor\Other\Unused;

function run(): string
{
    return helper();
}
