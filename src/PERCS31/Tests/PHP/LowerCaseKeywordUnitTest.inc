<?php

declare(strict_types=1);

namespace Vendor\Package;

// Valid.
final class Valid
{
    public int $a {
        get => 1;
        set(int $value) => $value;
    }
}

// The parent's check.
IF ($a) {
    ECHO 1;
}

// A hook's name, which the parent doesn't see.
final class Hooks
{
    public int $a {
        GET => 1;
        Set(int $value) => $value;
    }

    public int $b {
        #[Label] Get {
            return GET();
        }
        final SET {
            SET($value);
            $this->b = [Get, SET];
        }
    }

    public array $c {
        &GET => $this->c;
    }

    // A method named get or set isn't a hook.
    public function GET(): int
    {
        return Set(1);
    }
}

interface HasHooks
{
    public int $d { GET; SET; }
}
