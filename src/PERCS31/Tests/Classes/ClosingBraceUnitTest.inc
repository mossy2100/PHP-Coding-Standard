<?php

// === VALID ===

$a = new class {
    public int $x = 1;
};

$b = foo(new class {
    public int $x = 1;
});

$c = (new class {
    public int $x = 1;
})->x;

$d = new class {
    public int $x = 1;
}; // phpcs:ignore Generic.Foo

$e = array_map(function ($v) {
    return $v;
}, $list); // A closure isn't a class.

$f = new class {}; $g = 1;

// === INVALID ===

$h = new class {
    public int $x = 1;
}; // Comment.

$i = foo(new class {
    public int $x = 1;
}); // Comment.

$j = foo(
    new class {
        public int $x = 1;
    }, // Comment.
    2,
);

$k = new class {
    public int $x = 1;
} /* Comment */;

$l = new class {}; // Comment.

final class Named
{
    public int $x = 1;
} // Comment.

final class WithMethod
{
    public function method(): void
    {
        $m = 1;
    } // Comment.
}

// A function outside a class isn't checked by default.
function named(): void
{
    $m = 1;
} $n = 1;

function commented(): void
{
    $m = 1;
} // Comment.

// phpcs:set PERCS31.Classes.ClosingBrace checkFunctions true
function checked(): void
{
    $m = 1;
} $n = 1;

function checkedComment(): void
{
    $m = 1;
} // Comment.
// phpcs:set PERCS31.Classes.ClosingBrace checkFunctions false
