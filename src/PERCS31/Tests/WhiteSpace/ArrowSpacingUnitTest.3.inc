<?php

// The default: at least one space each side of an array's =>, as PER Coding Style 3.1 requires no more.
// The property isn't set here.

// At least one space each side of an array's =>, so arrows may be lined up.
$valid = [
    'a'   => 1,
    'bcd' => 2,
];
$alsoValid = ['a'  =>  1, 'b' => 2];

// No space on one side, or either.
$invalid = ['a'=>1, 'b' =>2, 'c'=> 3];

// A match arm still needs exactly one.
$value = match ($x) {
    1  => 'one',
};

// End of the arrays.
// End of the file.
