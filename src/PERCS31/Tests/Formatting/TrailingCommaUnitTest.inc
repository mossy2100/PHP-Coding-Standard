<?php

// === VALID ===

use Vendor\Package\{
    ClassA,
    ClassB,
};
use Other\{Single, Pair};

$a = match ($x) {
    1 => 'one',
    default => 'other',
};
$b = match ($x) { 1 => 'one', default => 'other' };

// === INVALID ===

use Third\{
    ClassC,
    ClassD
};
use Fourth\{Single, Pair,};

$c = match ($x) {
    1 => 'one',
    default => 'other'
};
$d = match ($x) { 1 => 'one', default => 'other', };
$e = match ($x) {
    1 => 'one',
    default => 'other' // A comment after the last arm.
};
