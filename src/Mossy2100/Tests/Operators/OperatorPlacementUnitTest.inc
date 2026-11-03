<?php

// === VALID ===

$single = $a + $b * $c;

$wrapped = $a * $b
    + $c
    ?? $d;

$list = [
    'key' =>
        $a,
];

$negative = max(
    -1,
    -$a,
);

$reference = static function (
    &$value,
): void {
};

$concat = $s .
    $t;

if (
    $p &&
    $q
) {
}

$full = $a ? $b :
    $c;

// === INVALID ===

$arithmetic = $a * $b +
    $c;
$coalesce = $n ??
    $a;
$comparison = $a ===
    $b;
$logical = $p &&
    $q;
$instance = $o instanceof
    Example;
$short = $n ?:
    $a;
$assign =
    $a + $b;
$a +=
    $b;
$arrow = static fn(int $x): int =>
    $x * 2;
$commented = $a + // Why.
    $b;
