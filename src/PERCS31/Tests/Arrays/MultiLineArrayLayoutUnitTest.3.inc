<?php

// phpcs:set PERCS31.Arrays.MultiLineArrayLayout splitForeachAndMatch true

// With the property on, an array that starts a split foreach or match header may start a line.
foreach (
    [
        'alpha',
        'beta',
    ]
    as $item
) {
    run();
}

$result = match (
    [
        'alpha',
        'beta',
    ]
) {
    default => 'other',
};

// The bracket is still reported anywhere else.
$array =
    [
        'alpha',
        'beta',
    ];

// phpcs:set PERCS31.Arrays.MultiLineArrayLayout splitForeachAndMatch false

// With it off, the same headers are reported.
foreach (
    [
        'alpha',
        'beta',
    ]
    as $item
) {
    run();
}

$result = match (
    [
        'alpha',
        'beta',
    ]
) {
    default => 'other',
};
