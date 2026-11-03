<?php
// phpcs:set PERCS31.Operators.OperatorSpacing ignoreNewlines true
// The sniff runs alone here, with every code. The PERCS31 ruleset excludes the codes for more than one space.

// === VALID ===

$a = $b && $c;
$a = $b . $c;
$a = $b
    . $c;
$a = $b or $c;

// === INVALID ===

$a = $b&&$c;
$a = $b  .  $c;
$a = $b  or  $c;
