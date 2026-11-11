<?php

// === VALID ===

$a = (bool) $x;
$b = (int) $x;
$c = (float) $x;
$d = (string) $x;
$e = (array) $x;
$f = (object) $x;

// A binary string's prefix isn't a cast.
$g = b'abc';
$h = b"abc";

// === INVALID ===

$i = (boolean) $x;
$j = (integer) $x;
$k = (double) $x;

// A (binary) cast is an alias of (string), so the fix replaces it, and doesn't delete it.
$l = (binary) $x;
$m = (BINARY) $x;
$n = ( binary ) $x;
$o = (binary)$x;
