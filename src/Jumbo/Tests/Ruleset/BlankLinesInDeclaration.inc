<?php

declare(strict_types=1);

namespace Vendor;

final class Example
{
    // PER Coding Style 3.1, section 12.2: one blank line is allowed before a parameter's attributes, and no other.
    public function blanks(
        string $a,

        string $b,


        #[Decl]
        string $c,
        #[Decl]

        string $d,
    ): string {
        return $a . $b . $c . $d;
    }

    public function closure(): callable
    {
        return static function (
            int $a,
        ) use (
            $x,

            $y,
        ): int {
            return $a;
        };
    }
}
