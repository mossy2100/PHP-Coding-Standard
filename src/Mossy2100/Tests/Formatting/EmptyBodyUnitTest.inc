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
