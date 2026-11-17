<?php

declare(strict_types=1);

namespace Vendor;

final class Example
{
    // PER Coding Style 3.1, section 2.3: a blank line is allowed between two parameters, or two use variables, and
    // not after an attribute (12.2), after the opening parenthesis or before the closing one.
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

    public function leading(

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
