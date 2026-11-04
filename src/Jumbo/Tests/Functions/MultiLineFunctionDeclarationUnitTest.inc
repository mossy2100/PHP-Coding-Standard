<?php

// === VALID: an empty body on one line ===

function emptyFunction(): void {}

final class Holder
{
    public function __construct(private int $a) {}

    public function multi(
        int $a,
        int $b,
    ): void {}
}

$closure = static function (): void {};
$closureWithUse = static function () use ($a): void {};

// === INVALID: the parent's checks still run ===

function notEmpty(): int {
    return 1;
}

function badMulti(
    int $a,
    int $b,
)
{
    return;
}
