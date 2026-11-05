<?php

// phpcs:set PERCS31.Operators.OperatorPlacement requireGroupingLineUp true

// === VALID ===

$total = (
    $price
    + $tax
    - $discount
);

// The first value is on the ( line, so the rest is indented once past that line.
$total = ($price
    + $tax);

// Nested in a call argument.
foo(
    (
        $a
        && $b
    ),
    $c,
);

foo(
    ($a
        && $b),
    $c,
);

// An index counts as a group.
$value = $values[
    $a
    + $b
];

// An arrow function's => and body are indented once.
$callback = (
    fn(int $y): int
        => $y
);

$callback = (
    fn(int $y): int => $y
        + 1
);

$callback = (fn(int $y): int => $y
    + 1);

// An arrow function that has ended doesn't count.
$total = (
    array_map(fn($y) => $y, $z)
    + $tax
);

// Not a group, so these rules don't change.
if (
    $a
    && $b
) {
    foo();
}

foo(
    $first
        . $second,
);

$total = $price
    + $tax;

// === INVALID ===

$total = (
    $price
        + $tax
);

$total = (
    $price
+ $tax
);

$total = ($price
         + $tax);

$total = ($price
        + $tax);

foo(
    (
        $a
            && $b
    ),
    $c,
);

$value = $values[
    $a
        + $b
];

// An arrow function's => and body not indented.
$callback = (
    fn(int $y): int
    => $y
);

$callback = (
    fn(int $y): int => $y
    + 1
);

// phpcs:set PERCS31.Operators.OperatorPlacement requireGroupingLineUp false
