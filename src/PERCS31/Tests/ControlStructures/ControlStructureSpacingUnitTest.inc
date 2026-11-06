<?php

// === VALID: a multi-line array as the whole condition ===

if ([
    1,
    2,
]
) {
    foo();
}

if ($a) {
    while ([
        1,
    ]
    ) {
        foo();
    }
}

// === VALID: the parent's layout ===

if (
    $a
    && $b
) {
    foo();
}

if ([1, 2]) {
    foo();
}

// === INVALID: an array as the whole condition ===

if ([
    1,
]) {
    foo();
}

if ([
    1,
]
    ) {
    foo();
}

// === INVALID: the parent's checks still run ===

if ($a
    && $b
) {
    foo();
}

if ([1] === $a
    && $b
) {
    foo();
}

if (
$a
) {
    foo();
}
