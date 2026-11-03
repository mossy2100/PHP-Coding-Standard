<?php

// === VALID: an empty body on one line ===

final class EmptyException extends Exception {}

function emptyFunction(): void {}

final class Holder
{
    public function __construct(private int $a) {}

    public function noop(): void {}
}

$closure = static function (): void {};
$closureWithUse = static function () use ($a): void {};

// === INVALID: the parent's checks still run ===

if (true) {
    echo 1; }

function notEmpty(): int
{
    return 1; }

function anonymous(): object
{
    return new class {};
}
