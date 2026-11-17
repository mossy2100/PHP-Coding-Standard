<?php

// === VALID ===

declare(ticks=1) {
    $a = 1;

    $b = 2;
}

declare(ticks=1);

class Valid
{
    public string $a {
        get {
            $b = 'a';

            return $b;
        }

        set {
            $this->a = $value;
        }
    }

    public string $c { get => 'c'; }

    public string $d {
        get => 'd';
    }
}

interface ValidInterface
{
    public string $e { get; set; }
}

// === INVALID ===

// A block declare.
declare(ticks=1) {

    $a = 1;

}

class Invalid
{
    // A hook list, and a hook's body.
    public string $a {

        get {

            return 'a';

        }

        set {


            $this->a = $value;
        }

    }

    // A hook list with short hooks.
    public string $b {

        get => 'b';

    }
}
