<?php

// === VALID ===

function oneLine(int|string $a): ArrayAccess&Traversable {}

function unionPerLine(
    \ReflectionObject
    |\ReflectionClass
    |\ReflectionMethod $reflect,
) {}

function segmentPerLine(
    array
    |(ArrayAccess&Traversable)
    |(Traversable&Countable) $input,
) {}

class NotSplitAtSymbol
{
    public const int|string
        VALUE = 1;
}

// === INVALID ===

function twoTypesOnLine(
    \ReflectionObject|\ReflectionClass
    |\ReflectionMethod $reflect,
) {}

function splitIntersection(
    array
    |(ArrayAccess
    &Traversable) $input,
) {}

function intersectionPerLine(
    Countable
    &Traversable&Stringable $input,
) {}

function twoSegmentsOnLine(
    array|(ArrayAccess&Traversable)
    |(Traversable&Countable) $input,
) {}

// A set hook's parameter type.
class HookParameterTypes
{
    public int|string|array $valid {
        set(
            int
            |string
            |array $value,
        ) => $value;
    }

    public int|string|float $twoOnLine {
        set(
            int|string
            |float $value,
        ) => $value;
    }

    public int $operators {
        set(int $value) {
            foo(
                $value
                | 1|2,
            );
        }
    }
}
