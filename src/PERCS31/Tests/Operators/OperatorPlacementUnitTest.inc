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

// A unary operator at the start of a block or statement isn't a wrapped operator.
function unary(): void
{
    -$x->y();
}
if ($a) {
    -$k->d();
} else {
    +$k->e();
}

// An assignment before a multi-line array's [ on the next line is left to PERCS31.Arrays.MultiLineArrayLayout.
$leftToArraySniff =
    [
        1,
        2,
    ];

// A single-line array on the next line is still reported.
$reported =
    [1, 2];
