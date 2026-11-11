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

// An array whose values all have keys is left to Squiz.Arrays.ArrayDeclaration's IndexNoNewline check.
$keyed = [
    'a' => 1, 'b' => 2,
];

// A single value on a line of its own.
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

// A value after an arrow function, whose scope ends at the comma.
$arrow = [
    fn($x) => $x, 2,
    3,
];

$arrowFirst = [fn($x) => $x,
    2,
];

// A mix of keyed and unkeyed values, which no Squiz check covers.
$mixed = [
    'a' => 1, 2,
    3, 'b' => fn($x) => $x, ...$rest,
];

$mixedFirst = ['a' => 1,
    2,
];

$mixedValid = [
    'a' => 1,
    2,
    ...$rest,
];

// A single value on the bracket's line.
$oneFirst = [1,
];

$oneClosure = [function () {
    return 1;
},
];
