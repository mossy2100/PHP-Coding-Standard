<?php

// === VALID ===

$a = clone($b);
$a = clone($b, ['x' => 1]);
$a = $b->clone();
$a = clone(...);

// === INVALID ===

$a = clone $b;
$a = clone $b->c;
$a = clone $b?->c()['d'] ?? $e;
$a = clone self::$b;
$a = clone \Vendor\Foo::instance();
$a = clone new Foo();
$a = clone ($b);
$a = clone ($b)->c;
$a = clone $$b;
$a = clone /* the original */ $b;

// Parentheses followed by a dereference hold only part of what is cloned.
$a = clone($b)->c;
$a = clone($b->up())->color;
$a = clone(new Foo())->bar->baz()[0];
$a = clone($b)[0];
$a = clone($b + $c)->magnitude;
$a = clone ($b ?: $c)::$shared;
$a = clone($b)->{$name}::{$other}()[0];
$a = clone $b->{$name};
$a = clone($b)->$$name;
$a = clone /* the copy */ ($b)->c;

// A variable variable.
$a = clone $$b->c;
$a = clone ${$b};
$a = clone($$b)->c;
