<?php

namespace A;

use B\C;

$f = function () use ($a) {
    foo();
};

final class T
{
    use Tr;
}

namespace D;

use E\F;

echo 1;
