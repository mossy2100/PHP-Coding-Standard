<?php

// A value wrapped after =>, after an element that has a keyword owning parentheses. Walking back from the =>, the sniff
// once jumped forward at that keyword, and never finished.
$afterArrowFunction = [
    'callback' => static fn(int $x): int => $x * 2,
    'description' =>
        'A value on the line after its key.',
];

$afterClosure = [
    'callback' => function () {
        return 1;
    },
    'description' =>
        'A value on the line after its key.',
];

$afterLongArray = [
    'values' => array(1, 2),
    'description' =>
        'A value on the line after its key.',
];

$afterMatch = [
    'label' => match ($code) {
        1 => 'one',
        default => 'many',
    },
    'description' =>
        'A value on the line after its key.',
];

// Dead code after a case that returns a closure.
switch ($mode) {
    case 1:
        return function () {
            return 1;
        };
        echo 'dead';
}
