<?php

// === VALID ===

$single = $a ? $b : $c;

$threeLines = $a
    ? $b
    : $c;

$inCall = max(
    $a
        ? $b
        : $c,
    1,
);

$longCondition = $a
    && $b
    ? $c
    : $d;

$multiLineElse = $a
    ? []
    : [
        'first' => $c,
    ];

$short = $a
    ?: $c;

// === INVALID ===

$twoLinesThenLeading = $a
    ? $b : $c;

$twoLinesElseLeading = $a ? $b
    : $c;

$trailingBoth = $a ?
    $b :
    $c;

$trailingElse = $a ? $b :
    $c;

$notIndented = $a
? $b
: $c;

$indentedTwice = $a
        ? $b
        : $c;

$commented = $a // Why.
    ? $b : $c;

// A ternary in a match arm starts at the arm, not at the match.
$a = match ($x) {
    1 => $b ? 2 : 3,
    2 => foo(1, 2) ? 3 : 4,
    3 => $b
        ? 2
        : 3,
    $b ? 4 : 5 => 6,
    $b
        ? 7
        : 8 => 9,
    default => [1, $b ? 2 : 3],
};

$c = match ($x) {
    1 => $b
    ? 2
    : 3,
    2 => $b ? 3
        : 4,
    default => 5,
};

// A ternary's start is the start of its expression, whatever comes before it.
$first = 1;
$a += $b ? 1 : 2;
$a .= $b ? 1 : 2;
$a -= $b ? 1 : 2;
$a ??= $b ? 1 : 2;
$c->d += $b ? 1 : 2;
echo $b ? 1 : 2;
print $b ? 1 : 2;
$b ? foo() : bar();
$a[$c] = foo($d, $e) ? 1 : 2;
$a += $c->{$b} ? 1 : 2;
$c->{$b} ? foo() : bar();

function ternaries()
{
    yield $b ? 1 : 2;
    yield from $b ? $c : $d;

    if ($c) {
        $a += $b ? 1 : 2;
    }
}

// A wrapped one is still checked.
$a += $b
    ? 1
    : 2;
$a += $b ? 1
    : 2;
