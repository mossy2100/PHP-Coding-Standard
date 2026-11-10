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

// A function's closing brace is checked too.
function checked(): void
{
    $m = 1;
} $n = 1;

function checkedComment(): void
{
    $m = 1;
} // Comment.
// End of the functions.

// A property hook body or hook list.
class B
{
    public int $a {
        get {
            return 1;
        } // Body comment.
        set {
            $this->a = $value;
        }
    } // List comment.

    public int $b { get => 1; } // Short list comment.

    public int $c {
        get => 1; // Fine: after a semicolon, not a brace.
    }

    public int $d {
        get {
            return 1;
        } // phpcs:ignore Generic.Foo
    }

    public function __construct(
        public string $e { set => trim($value); }, // Promoted comment.
        public string $f { set => trim($value); },
    ) {}
}
