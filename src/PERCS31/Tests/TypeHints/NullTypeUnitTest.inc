<?php

declare(strict_types=1);

namespace Vendor\Package;

// Valid: null last, or no null.
function valid(int|string|null $a, (A&B)|null $b, ?int $c, int|string $d): (A&B)|C|null
{
    return null;
}

// null first in a DNF type, and in the middle of one.
function dnf(null|(A&B) $a, (A&B)|null|C $b): void
{
}

// null first in a union of two, which Slevomat only reports as ?T.
function two(null|int $a): void
{
}

// null not last in a union of three.
function three(): int|null|string
{
    return 1;
}

final class Example
{
    public null|(A&B) $a;

    // Typed constants: ?T is recommended, and null must be last.
    public const int|null A = 1;
    public const null|int B = 1;
    public const int|null|string C = 1;
    public const int|string|null D = 1;
    public const ?int E = 1;

    // set hook parameters.
    public ?int $c {
        set(null|int $value) {
            $this->c = $value;
        }
    }

    public ?int $d {
        set(int|null $value) {
            $this->d = $value;
        }
    }

    public int|string|null $e {
        set(int|null|string $value) {
            $this->e = $value;
        }
    }

    public int|string|null $f {
        set(int|string|null $value) {
            $this->f = $value;
        }
    }
}

// A split type is reported, but not fixed.
function split(
    null
    |int
    |string $a,
): void {
}

// A comment in the type stops the fix too.
function commented(null/* why */|int|string $a): void
{
}

// Not a type.
$g = $a | $b & $c;
$h = fn(null|(A&B) $x): int => 1;

// ?T is recommended everywhere, not only in a typed constant or a set hook's parameter.
final class Fluent
{
    public int|null $count = null;

    public function __construct(public Fluent|null $next = null) {}

    public function chain(self|null $a, \Vendor\Other|null $b): static|null
    {
        return $this;
    }

    public function back(): null|static
    {
        return $this;
    }

    // Not a single simple type, so these pass.
    public function passes((A&B)|null $a, int|string|null $b): ?static
    {
        return $this;
    }
}

$i = function (int|null $a): string|null {
    return null;
};
$j = fn(int|null $a): string|null => null;
