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

// A set hook's nullable type isn't a ternary, and checking carries on after it.
class HookParameterType
{
    public ?int $nullable {
        set(?int $value) => $value;
    }
}

$a = $b ? 1
    : 2;

// A value or condition split over lines doesn't split the ternary, with no line break next to ? or :.
$a = $b ? [
    1,
] : [];
$a = $b ? foo(
    1,
) : 2;
$a = $b ? function () {
    return 1;
} : null;
$a = $b ? match ($c) {
    1 => 2,
    default => 3,
} : 4;
$a = foo(
    1,
) ? 1 : 2;
$a = $b ? 1 : foo(
    1,
);
$a = $b ? 1 : [
    1,
];
$a = $b ? foo(
    1,
) : bar(
    2,
);
$a = ($b
    && $c) ? 1 : 2;
foo($b ? [
    1,
] : []);
$a = $b ? <<<'X'
    c
    X : 4;

// The three-line form, with a value split over lines.
$a = $b
    ? [
        1,
    ]
    : [];

// A line break next to one operator splits the ternary.
$a = $b ? [
    1,
]
    : [];
$a = $b
    ? [
        1,
    ] : [];
$a = $b ? 1 // Comment.
    : 2;

// A ternary nested in the middle operand, indented once past its own line, and a short ternary there.
$a = $b
    ? $c
        ? 1
        : 2
    : 3;
$a = $b
    ? $c ?: $d
    : 3;
$a = $b
    ? ($c ? 1 : 2)
    : 3;

// A nested ternary not indented once past its own line.
$a = $b
    ? $c
    ? 1
    : 2
    : 3;
