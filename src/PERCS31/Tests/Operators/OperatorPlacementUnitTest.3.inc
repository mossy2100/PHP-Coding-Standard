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
$arrowOver = fn(int $x): int
        => $x;

// A by-reference hook's & isn't an operator.
class ReferenceHooks
{
    public array $items = [] {
        &get => $this->items;
    }

    public array $list = [] { &get => $this->list; }
}

// Valid by default: a wrapped line lined up with its list item, which PER doesn't rule out. With
// requireListItemIndent set, as in OperatorPlacementUnitTest.4.inc, they're errors.
foo(
    'first'
    . 'second',
);
$match = match (true) {
    $a
    && $b => 1,
    default => 2,
};

// Valid: grouping parentheses, which aren't a list, are left alone, like a condition.
$grouped = (
    $a
    && $b
);
$groupedIndented = (
    $a
        && $b
);

// A set hook's split parameter type isn't a wrapped operator.
class HookParameterType
{
    public int|string $split {
        set(
            int
            |string $value,
        ) => $value;
    }
}

// A full ternary in an earlier item doesn't stop a later item being a list item, so lined up or indented once passes.
foo(
    $a ? 1 : 2,
    $b ? 3 : 4,
    $c
    ?? 1,
    $d
        ?? 1,
);
$e = [
    $a ? 1 : 2,
    $c
    . 1,
    'f' => $a ? 1 : 2,
    'g' => $c
    . 1,
];
$h = match ($i) {
    1 => $a ? 1 : 2,
    2 => $c
    . 1,
    default => 3,
};

// Neither lined up nor indented once.
foo(
    $a ? 1 : 2,
    $c
            ?? 1,
);

// In a ternary's branch, it isn't a list item, so it's indented once past the branch.
foo(
    $a
        ? $b
        . $c
        : 2,
);
