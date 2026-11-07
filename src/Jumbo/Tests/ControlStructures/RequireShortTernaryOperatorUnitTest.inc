<?php

$a = $b ?: 1;
$a = $b ? $b : 1;

// A set hook's nullable type isn't a ternary, and checking carries on after it.
class HookParameterType
{
    public ?int $nullable {
        set(?int $value) => $value;
    }
}

$a = $b ? $b : 2;
