<?php

namespace Vendor\Package;

use Vendor\Package\Zeta;
use Vendor\Package\Alpha;

/**
 * Code that follows PER Coding Style 3.1, but not the house rules the Mossy2100 standard adds on top of it.
 */
final class HouseStyle
{
    public function describe(bool $flag, Alpha $alpha, Zeta $zeta): string
    {
        $short_name = "plain";
        $total  = 1;

        if (! $flag) {
            return $short_name;
        }

        return $alpha->name() . $zeta->name() . $total;
    }
}
