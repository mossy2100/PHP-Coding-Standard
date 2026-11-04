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

// === Bodiless lists (PER Coding Style 3.1, section 4.10) ===

interface Bodiless
{
    public string $valid { get; set; }

    public string $noSpaces {get;}

    public string $spaceBeforeSemicolon { get ; }

    public string $doubleSpaces {  get;  set;  }

    public string $multiLine {
        get;
        set;
    }
}

abstract class MixedBodies
{
    abstract public string $valid {
        get => ucfirst($this->valid);
        set;
    }

    abstract public string $spaceBeforeSemicolon {
        get => ucfirst($this->spaceBeforeSemicolon);
        set ;
    }
}

class ReferenceHooks
{
    // PER says nothing about a space after a by-reference hook's &, so it's allowed by default.
    public array $allowed = [] {
        & get => $this->allowed;
    }

    // phpcs:set Jumbo.Classes.PropertyHookLayout checkReferenceSpacing true
    public array $spaced = [] {
        & get => $this->spaced;
    }

    public array $inline = [] { & get => $this->inline; }

    public array $unspaced = [] {
        &get => $this->unspaced;
    }
    // phpcs:set Jumbo.Classes.PropertyHookLayout checkReferenceSpacing false
}
