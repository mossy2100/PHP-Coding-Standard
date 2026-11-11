<?php

declare(strict_types=1);

namespace Vendor\Package;

// Valid: no comma on one line, or after a single argument split over lines, and one in a split list.
$a = max($b, $c);
$a = first([
    1,
    2,
]);
$a = first($b, function () {
    return 1;
});
$a = max(
    $b,
    $c,
);
$a = first(
    [
        1,
    ],
);

// A comma in a list on one line.
$a = max($b, $c,);
$a = new Collection($b,);

// A comma after a single argument split over lines, which doesn't split the list.
$a = first([
    1,
    2,
],);
$a = first($b, function () {
    return 1;
},);
$a = $c->d(fn($x) => [
    $x,
],);

// The closing parenthesis after the last argument of a split list is left to the layout sniff.
$a = max(
    $b,
    $c,);
