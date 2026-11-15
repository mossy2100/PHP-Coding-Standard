<?php

// === VALID ===

$a = fn($x) => $x;
$b = array_map(fn($x) => $x * 2, $list);
$c = fn($x)
    => $x;

// === INVALID ===

$d = fn($x) => $x ;
$e = fn($x) => fn($y) => $x + $y ;
$f = fn($x)
    => $x
;

// A line break after fn, without and with a comment in the way.
$g = fn
($x) => $x;
$h = static fn
    &($x) => $x;
$i = fn // Why.
($x) => $x;
