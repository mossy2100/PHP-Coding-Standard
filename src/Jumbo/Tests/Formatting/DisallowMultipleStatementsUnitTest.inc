<?php

// === VALID ===

interface HasName
{
    public string $name { get; set; }

    public string $label { get; }
}

abstract class Base
{
    abstract public string $title { get; set; }

    public string $hooked {
        get => 'x';
        set {
            $this->hooked = $value;
        }
    }
}

// === INVALID ===

$a = 1; $b = 2;

abstract class Invalid
{
    public string $body {
        get { $x = 1; return 'x'; }
    }

    abstract public string $mixed { get => 'x'; set; }
}
