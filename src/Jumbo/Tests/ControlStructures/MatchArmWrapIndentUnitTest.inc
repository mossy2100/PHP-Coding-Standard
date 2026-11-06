<?php

// === VALID ===

$a = match ($x) {
    1 => 2,
    3, 4 => 5,
    6,
        7 => 8,
    9 =>
        10,
    11
        => 12,
    13,
        14
        => 15,
    16 => foo(
        17,
    ),
    18 => [
        19,
    ],
    20 => $y
        + 21,
    default => 22,
};

function f(int $x): int
{
    return match ($x) {
        1,
            2 => 3,
        default => 4,
    };
}

// === INVALID ===

$b = match ($x) {
    1,
    2 => 3,
    4,
            5 => 6,
    7 =>
    8,
    9 =>
            10,
    11
    => 12,
    default => 13,
};

function g(int $x): int
{
    return match ($x) {
        1,
        2 => 3,
        default => 4,
    };
}
