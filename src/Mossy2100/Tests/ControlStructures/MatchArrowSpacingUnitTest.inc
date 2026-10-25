<?php

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

// Array arrows inside a match arm aren't checked by this sniff.
$label = match ($size) {
    1 => ['a'  => 1],
    default => [],
};
