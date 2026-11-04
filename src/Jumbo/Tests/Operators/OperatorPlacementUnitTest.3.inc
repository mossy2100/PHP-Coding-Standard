<?php

// === VALID ===

$sum = $a
    + $b
    + $c;
$call = foo($a
    + $b);
foo(
    'first'
        . 'second',
    $c
        + $d,
);
$array = [
    'key' => $a
        + $b,
];
$nested = $a
    + ($b
        - $c);
$ternary = $p
    ? $q
        . 'r'
    : $s;
$elvis = $a
    ?: $b
    ?: $c;
$arrow = fn(int $x): int
    => $x;
$multiLineArrow = fn(
    int $x,
): int
    => $x;
if (
    $a
    && ($b
    || $c)
) {
    $coalesce = $x
        ?? $y;
}
switch ($a) {
    case 1:
        $case = $b
            + $c;
        break;
}
$closure = array_map(function ($v) {
    return $v;
}, $list)
    + [1];
foo(
    -1,
);
switch (true) {
    case (
        $a === 10
        && $b === 20
    ):
        break;
    case (
            $c
            && $d
    ):
        break;
}

// === INVALID ===

$over = $a
        + $b;
$under = $a
+ $b;
foo(
    'first'
    . 'second',
);
$arrowOver = fn(int $x): int
        => $x;
$match = match (true) {
    $a
    && $b => 1,
    default => 2,
};

// A by-reference hook's & isn't an operator.
class ReferenceHooks
{
    public array $items = [] {
        &get => $this->items;
    }

    public array $list = [] { &get => $this->list; }
}
