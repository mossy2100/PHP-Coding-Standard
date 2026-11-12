<?php

// === VALID ===

$a = new (className())();
$b = new (className())($first, $second);
$c = new ($prefix . 'Handler') ();
$d = new ($classes['default']) /* None. */ ();

// Left to PSR12.Classes.ClassInstantiation.
$e = new Foo();
$f = new Foo;
$g = new $class;
$h = new class {};

// === INVALID ===

$i = new (className());
$j = new ($prefix . 'Handler');
$k = [new (className()), new ($classes['default'])];
$l = new /* Dynamic. */ (className()) /* None. */;
