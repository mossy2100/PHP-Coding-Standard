<?php

// By default, only the reference and spread operators are checked.

// === VALID ===

function &validReturn(array &$items, int &...$rest): array
{
    return $items;
}

$assigned = &$a;
foreach ($items as &$item) {
    $item++;
}
foreach ($items as $key => &$item) {
    $item++;
}
$array = [&$a, 'b' => &$b];
[&$c, 'd' => &$d] = $array;
$closure = function &() use (&$a): int {
    return $a;
};
$arrow = fn&(array &$e): array => $e;
$commented = &/* Why. */ $a;

// A bitwise & isn't a reference, including in a default value.
$bitwise = $a & $b;
$inArray = [A & B, 'c' => A & B];
foo($a, A & B);

function bitwiseDefault(int $flags = A & B): int
{
    return $flags & A;
}

$closureDefault = function (int $flags = A & B): int {
    return $flags;
};
$arrowDefault = fn(int $flags = A & B): int => $flags;

// The other prefix operators aren't checked unless their properties are on.
$plus = + $a;
$negated = - $a;
$not = ! $a;
$bitwiseNot = ~ $a;
$silenced = @ file_get_contents($s);

class ValidHook
{
    public array $items = [] {
        &get => $this->items;
    }
}

// === INVALID ===

function & invalidReturn(array & $items, int & ...$rest): array
{
    return $items;
}

$assigned = & $a;
$assignedLine = &
    $a;
foreach ($items as & $item) {
    $item++;
}
foreach ($items as $key => & $item) {
    $item++;
}
$array = [& $a, 'b' => & $b];
[& $c, 'd' => & $d] = $array;
$closure = function & () use (& $a): int {
    return $a;
};
$arrow = fn& (array & $e): array => $e;
$commented = & /* Why. */ $a;
$joined =& $a;

class InvalidHook
{
    public array $items = [] {
        & get => $this->items;
    }

    public array $inline = [] { & get => $this->inline; }
}

// === THE SPREAD OPERATOR ===

function validVariadic(int ...$rest): array
{
    return [...$rest, 'a' => 1];
}

$unpacked = foo(...$args);
$instance = new Foo(...$args);

// A first-class callable's ... has no operand, so it's skipped, whatever its spacing.
$callable = foo(...);
$method = $object->method(...);
$static = Foo::make(...);
$spaced = foo( ... );
$spacedAfter = foo(... );

function invalidVariadic(int ... $rest, int &... $more): array
{
    return [... $rest, 'a' => 1, ... $more];
}

$unpacked = foo(... $args);
$unpackedLine = foo(1, ...
    $args);
$instance = new Foo(... $args);
$arrow = fn(... $values) => foo(... $values);
$commented = foo(... /* Why. */ $args);
