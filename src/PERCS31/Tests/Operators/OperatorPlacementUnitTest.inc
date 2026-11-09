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

// A short ternary split between ? and :. PER doesn't say whether they may be apart, so only a ? at the end of a line is
// reported, and a comment after it isn't fixable.
$g = $h ? : $i;
$g = $h ?
    : $i;
$g = $h
    ? : $i;
$g = $h ? /* c */ : $i;
$g = $a ? $b ? : $c : $d;
$g = $h ? // Why.
    : $i;

// phpcs:set PERCS31.Operators.OperatorPlacement checkSplitShortTernary true
// With checkSplitShortTernary on, anything between ? and : is reported, and a comment isn't fixable.
$g = $h ? : $i;
$g = $h ?
    : $i;
$g = $h
    ? : $i;
$g = $h ? /* c */ : $i;
$g = $a ? $b ? : $c : $d;
// phpcs:set PERCS31.Operators.OperatorPlacement checkSplitShortTernary false
