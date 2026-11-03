<?php

// === VALID ===

class Valid
{
    // A hook on a promoted parameter: its $value isn't another parameter.
    public function __construct(public string $name { set => ucfirst($value); })
    {
    }
}

// === INVALID: the parent's checks still run ===

function untyped($value): void
{
}
