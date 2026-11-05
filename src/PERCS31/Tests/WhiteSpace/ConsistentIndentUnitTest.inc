<?php

// === VALID: continuation lines starting with an operator ===

function continuations(): void
{
    $piped = '<foo>'
        |> strtoupper(...)
        |> htmlspecialchars(...);
    $remainder = $total
        % $size;
    $isUser = $value
        instanceof User;
    $mask = $flags
        | $extra;
    $same = $left
        === $right;
    $square = fn(int $x): int
        => $x ** 2;
}

// === INVALID: indented further than the scope ===

function overIndented(): void
{
    foo();
        bar();
    if ($ready) {
        baz();
            qux();
    }
}

class User
{
    public string $name {
        get {
            $name = 'x';
                return $name;
        }
    }
}
