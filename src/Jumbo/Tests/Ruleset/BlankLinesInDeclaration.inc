<?php

declare(strict_types=1);

namespace Vendor;

final class Example
{
    // PER Coding Style 3.1, sections 2.3 and 12.4: one blank line is allowed between parameters, with or without
    // attributes, and no other.
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

    public function trailing(
        string $a,

    ): string {
        return $a;
    }

    public function comments(
        string $a, // A comment.

        string $b, // A comment.

        #[Decl]
        string $c,
    ): string {
        return $a . $b . $c;
    }
}
