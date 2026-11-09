<?php

// === VALID ===

$plus = +$a;
$bitwiseNot = ~$a;
$silenced = @file_get_contents($s);
$binary = $a + $b;
$wrapped = $a
    + $b;

// === INVALID ===

$plusSpace = + $a;
$plusLine = +
    $a;
$bitwiseNot = ~ $a;
$silenced = @ file_get_contents($s);
$commented = + /* Why. */ $a;
$afterCast = (int) + $a;
$afterConcat = 'a' . + $a;
