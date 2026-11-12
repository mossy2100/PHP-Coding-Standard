<?php

// === VALID: a multi-line foreach ===

foreach ([
    1,
    2,
] as $x) {
    foo();
}

foreach ([
    'a' => 1,
] as $key => $value) {
    foo();
}

foreach (
    $items as $x
) {
    foo();
}

foreach (foo(
    1,
) as $x) {
    bar();
}

// === VALID: a multi-line match ===

$label = match ($this->lookup([
    'code' => $code,
])) {
    default => 'none',
};

$label = match ([
    $first,
    $second,
]) {
    default => 'none',
};

$label = match (
    $code
) {
    default => 'none',
};

// === VALID: the parent's layout ===

foreach ($items as $x) {
    foo();
}

if (
    $a
    && $b
) {
    foo();
}

// === INVALID: a space inside a multi-line foreach's parentheses ===

foreach ( [
    1,
] as $x) {
    foo();
}

foreach ([
    1,
] as $x ) {
    foo();
}

// === INVALID: the parent's checks still run ===

foreach ( $items as $x ) {
    foo();
}

if ($a
    && $b
) {
    foo();
}

while ($a
    && $b
) {
    foo();
}

// === INVALID: a space inside a multi-line match's parentheses ===

$label = match ( $this->lookup([
    'code' => $code,
]) ) {
    default => 'none',
};
