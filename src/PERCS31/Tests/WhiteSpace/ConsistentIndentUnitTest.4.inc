<?php

// === VALID ===

$a = [
    'b' =>
        'c',
    'd' => [
        'e' =>
            'f',
    ],
    'g' => <<<'EOT'
    h
    EOT,
];

foreach ($a as $k =>
    $v) {
}

switch ($a) {
    case 1:
        break;

        deadButLevel();
    default:
        return foo(
            1,
        );

        alsoLevel();
}

// === INVALID ===

$b = [
    'i' =>
                'j',
    'k' =>
    'l',
];

switch ($b) {
    case 1:
        break;

                foo();
    case 2:
        return;

    bar();
    default:
        break;
}
