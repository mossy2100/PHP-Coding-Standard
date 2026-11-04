<?php

// === VALID ===

class Valid
{
    public string $a = 'x' {
        get => $this->a;
    }

    public string $b {
        get {
            return $this->b;
        }

        set => $value;
    }

    public string $c { get => strtoupper($this->c); }

    public string $d {
        set => array_map(fn($item) => trim($item), $value);
    }

    public function __construct(public string $e { set => trim($value); }) {}
}

// === INVALID ===

class Invalid
{
    public string $a
    {
        get => $this->a;
    }

    public string $b  {
        get => $this->b;
    }

    public string $c {
        get => $this->c; }

    public string $d {
        get => $this->d;
    } // Why.

    public string $e {
        get  =>  $this->e;
    }

    public string $f {
        get =>
            $this->f;
    }

    public string $g {get => $this->g;}
}
