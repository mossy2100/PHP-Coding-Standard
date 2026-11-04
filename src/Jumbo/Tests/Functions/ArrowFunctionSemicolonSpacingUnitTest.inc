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
