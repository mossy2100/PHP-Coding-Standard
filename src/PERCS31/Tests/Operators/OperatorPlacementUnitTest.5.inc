<?php

// === VALID ===

// Each line in grouping parentheses is indented once past the line with the (.
$total = (
    $price
    + $tax
    - $discount
);

// The first value is on the ( line, so the rest is indented once past that line.
$total = ($price
    + $tax);

// A group that continues a statement.
$valid = $a
    && (
        $b
        || $c
    );

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

// An index isn't a group, so the line is indented once past the line its expression starts on.
$value = $values[
    $a
        + $b
];

// An arrow function's => and body are indented once past the line with fn, in a group too.
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

$total = (
    array_map(fn($y) => $y, $z)
    + $tax
);

// === INVALID ===

// Indented twice past the ( line, and not indented.
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

$invalid = $a
    && (
        $b
            || $c
    );

foo(
    (
        $a
            && $b
    ),
    $c,
);

// An index, lined up with its first line.
$value = $values[
    $a
    + $b
];

// An arrow function's => and body lined up with fn.
$callback = (
    fn(int $y): int
    => $y
);

$callback = (
    fn(int $y): int => $y
    + 1
);
