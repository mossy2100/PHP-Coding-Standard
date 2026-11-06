<?php

declare(strict_types=1);

namespace Vendor;

function none(int $a=1): int
{
    return $a;
}

function aligned(
    int $a     = 1,
    int $bcdef =  2,
): int {
    return $a + $bcdef;
}

$closure = static function (int $a  = 1): int {
    return $a;
};
