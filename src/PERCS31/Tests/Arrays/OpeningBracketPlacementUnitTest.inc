<?php

// === VALID ===

$single = [1, 2];

$multi = [
    1,
    2,
];

$nested = [
    [
        1,
        2,
    ],
    [
        3,
    ],
];

[
    $first,
    $second,
] = pair();

foo([
    1,
    2,
]);

// An argument in an argument list has to start its line, when the list has one argument per line.
foo(
    [
        1,
        2,
    ],
);

bar(
    $a,
    [
        1,
    ],
);

bar(
    [
        1,
    ],
    $b,
);

baz( // Why.
    [
        1,
    ],
);

$object = new Collection(
    [
        1,
    ],
    $b,
);

#[Route(
    [
        'GET',
    ],
)]
function routed(): void {}

$copy = clone(
    $object,
    [
        'name' => 'Copy',
    ],
);

// A match arm's condition has to start its line too.
$result = match ($value) {
    [
        1,
    ] => 'one',
    [
        2,
    ] => 'two',
    'three',
    [
        4,
    ] => 'more',
};

// The first condition of a split control structure has to start its line too.
if (
    [
        $first,
    ] === $expected
) {
} elseif (
    [
        $second,
    ] === $expected
) {
}

while (
    [
        $first,
    ] !== $expected
) {
}

switch (
    [
        $first,
    ]
) {
    default:
        break;
}

// === INVALID ===

$map = [
    'key' =>
        [
            1,
        ],
];

return
    [
        1,
    ];

$assigned =
    [
        1,
    ];

// A foreach has no rule that puts its expression on the line after the parenthesis.
foreach (
    [
        1,
    ] as $value
) {
}

// After a comment (not fixable).
$commented = // Why.
    [
        1,
    ];

// A call through an array element, an array, a string or a named member takes arguments too.
$result = $handlers['x'](
    [
        1,
    ],
    2,
);
$result = [$object, 'method'](
    [
        1,
    ],
    2,
);
$result = 'strlen'(
    [
        1,
    ],
    2,
);
$result = $object->{$method}(
    [
        1,
    ],
    2,
);
