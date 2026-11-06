<?php

// phpcs:set PERCS31.Formatting.TrailingComma disallowInSplitMatchConditions true
$a = match ($x) {
    1,
    2,
    => 3,
    4,
    5, => 6,
    7,
    8 => 9,
    default => 10,
};

// phpcs:set PERCS31.Formatting.TrailingComma disallowInSplitMatchConditions false
