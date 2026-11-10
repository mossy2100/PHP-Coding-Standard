<?php

declare(strict_types=1);

// Valid.
$a = clone($b);
$a = clone($b, ['foo' => 'bar']);
$a = clone(
    $b,
    ['foo' => 'bar'],
);
$a = clone($b, [
    'foo' => 'bar',
]);
$a = clone $b;

// Grouping parentheses, or a space after clone, aren't an argument list.
$a = clone ($b)->c;
$a = clone( $b )->c;
$a = clone ( $b );

// Spaces just inside the parentheses.
$a = clone( $b );
$a = clone( $b, [1] );

// A split list: the first argument on the next line, one per line, indented once.
$a = clone($b,
    [1],
);
$a = clone(
    $b, [1],
);
$a = clone(
        $b,
);
