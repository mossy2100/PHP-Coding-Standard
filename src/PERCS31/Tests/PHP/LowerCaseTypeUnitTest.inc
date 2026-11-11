<?php

declare(strict_types=1);

namespace Vendor\Package;

// Valid.
final class Valid
{
    public int|string|null $a {
        set(int|string|null $value) => $value;
    }

    public function set(int $a): string
    {
        return (string) $a;
    }
}

// The parent's checks: a parameter, a return type, a property, a typed constant and a cast.
final class Declarations
{
    public const INT LIMIT = 1;

    public Bool $flag = false;

    public function convert(INT $a, ?Float $b): STRING
    {
        return (STRING) $a;
    }
}

// A set hook's parameter, which the parent doesn't see.
final class Hooks
{
    public int|string $a {
        set(INT|String $value) => $value;
    }

    public ?int $b {
        set(?Int $value) {
            $this->b = $value;
        }
    }

    public array|iterable $c {
        set(ARRAY|Iterable|\Vendor\INT $value) => $value;
    }

    // After an attribute, the parent reads the parameter as a property. It's reported once, as a parameter.
    public mixed $d {
        set(#[Sensitive] Mixed $value) => $value;
    }

    // A method named set, and a call to a function named set, aren't hooks.
    public function set(int $a): int
    {
        return set($a);
    }
}
