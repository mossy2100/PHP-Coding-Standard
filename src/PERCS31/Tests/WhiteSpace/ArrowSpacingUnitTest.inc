<?php
// phpcs:set PERCS31.WhiteSpace.ArrowSpacing allowSeveralSpacesInArray false
// Correct spacing.
$label = match ($size) {
    1 => 'one',
    default => 'many',
};

// Aligned arrows.
$label = match ($size) {
    1       => 'one',
    2       => 'two',
    default => 'many',
};

// Extra space after the arrow.
$label = match ($size) {
    1 =>  'one',
    default => 'many',
};

// No spaces.
$label = match ($size) {
    1=>'one',
    default => 'many',
};

// A wrapped arm is fine.
$label = match ($size) {
    1 =>
        'one',
    default
        => 'many',
};

// An array inside a match arm is checked as an array.
$label = match ($size) {
    1 => ['a'  => 1],
    default => [],
};

// phpcs:set PERCS31.WhiteSpace.ArrowSpacing checkYield true
$array = ['a'=>1, 'b'  =>  2];
function generate(): iterable
{
    yield 'a'=>1;
}

// phpcs:set PERCS31.WhiteSpace.ArrowSpacing allowSeveralSpacesInArray true
// phpcs:set PERCS31.WhiteSpace.ArrowSpacing checkYield false
