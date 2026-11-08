<?php

// === VALID ===

$a = match ($b) {
    1, 2, 3 => 'a',
    foo(1,2), [4,5] => 'b',
    6 => [1,2],
    7 => bar(1 ,2),
    8,
    9 => 'c',
    default => 'd',
};

// === INVALID ===

$a = match ($b) {
    1,2 => 'a',
    3 , 4 => 'b',
    5,  6 => 'c',
    7 ,8 => 'd',
    default => 'e',
};
