<?php

declare(strict_types=1);

namespace Vendor\Package;

use Exception;

final class SplitException extends Exception
{
}

final class NoSpace extends Exception{}

final class TwoSpaces extends Exception  {}

final class Inner extends Exception { }

final class NotEmpty {
    public int $a = 1;
}

final class Holder
{
    public function __construct(private int $a)
    {
    }

    public function multi(
        int $aLongParameterNameThatPushesTheSignatureOverTheLineLengthLimitSoItStaysMultiLine,
        int $b,
    ): void {
    }

    public function commented(): void
    {
        // Intentionally empty.
    }

    public function lineComment(): void // Why.
    {
    }

    public function notEmpty(): int {
        return 1;
    }

    public function closure(): callable
    {
        return static function (): void {
        };
    }
}

function splitFunction(): void
{
}

function anonymousClasses(): array
{
    return [
        new class {},
        new readonly class {},
        new class {
        },
        new class{},
        new class {
            public int $x = 1;
        },
    ];
}

function closures(): array
{
    return [
        static function (): void {},
        static function () use ($a): void {},
        static function (): void { },
        static function (): void{},
        static function () use ($a): void {
        },
        static function (
            int $aLongParameterNameThatPushesTheSignatureOverTheLineLengthLimitSoItStaysMultiLine,
        ): void {
        },
        static function (): void {
            // Intentionally empty.
        },
    ];
}

function commentedClosures(): array
{
    return [
        static function () /* Why. */ {},
        static function () /* Why. */ {
        },
        static function () // Why.
        {
        },
    ];
}

// A control structure's empty body isn't checked by default.
try {
    run();
} catch (Exception $e) {
}

// phpcs:set PERCS31.Formatting.EmptyBody checkControlStructures true
try {
    run();
} catch (Exception $e) {
} finally {
}

while (next($items)) {
}

do {
} while (next($items));

if ($first) { }
if ($first){}

// Already abbreviated, and a body with a comment, which isn't empty.
if ($first) {} else {
    // Why.
}
// phpcs:set PERCS31.Formatting.EmptyBody checkControlStructures false
