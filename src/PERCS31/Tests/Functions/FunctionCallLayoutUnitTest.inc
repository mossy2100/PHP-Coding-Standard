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

// Grouping parentheses aren't an argument list.
$a = clone ($b)->c;
$a = clone( $b )->c;

// A space before the parentheses, and just inside them.
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

// A set hook's parameter list isn't a call, so its spacing isn't checked here.
final class Hooks
{
    public string $a {
        set ( string $value ) {
            $this->a = $value;
        }
    }

    public string $b {
        set( string $value ) => trim($value);
    }
}

// A first-class callable split over lines is left to Universal.WhiteSpace.FirstClassCallableSpacing.
$a = strlen(...);
$a = $b->c(...);
$a = strlen(
    ...
);
$a = $b->c(
    ...
);
$a = D::e(
    ...);

// With a comment too.
$a = strlen(
    // Why.
    ...
);

// `require` and `include` are language constructs, so parentheses after one only group its expression.
$b = include ($a ? 'x' : 'y') . '.php';
require_once (__DIR__) . '/x.php';
$c = require ($base . '/file.php');
