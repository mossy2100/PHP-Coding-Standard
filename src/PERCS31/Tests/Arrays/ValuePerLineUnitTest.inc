<?php

// === VALID ===

$single = [1, 2, 3];

$list = [
    1,
    2,
];

$attributes = [
    #[Pure] static fn() => 1,
    #[Pure] static function () {
        return 2;
    },
];

$nested = [
    [1, 2],
    fn() => [3, 4],
    foo(5, 6),
];

// Keyed arrays are left to Squiz.Arrays.ArrayDeclaration's IndexNoNewline check.
$keyed = [
    'a' => 1, 'b' => 2,
];

// A single value is left alone too.
$one = [
    1,
];

foreach ($rows as [
    $a, $b,
]) {
}

// === INVALID ===

$first = [1,
    2,
];

$second = [
    1, 2,
    3,
];

$long = array(
    1, 2,
);

[
    $c, $d,
] = $pair;

$commented = [ /* A comment. */ 1,
    2,
];
