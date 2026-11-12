<?php

// === VALID ===

$someInstance
    ->create()
    ->prepare()
    ->run();

$a = $foo->bar()->baz();

$b = $this->repository
    ->findAll();

$c = Foo::create()
    ->run();

$d = $this->foo()->bar(
    1,
);

$app->get('/hello', function ($name) {
    return 'Hello';
})->name('hello');

$e = $f->g
    ->h;

// === INVALID ===

$x->create()
    ->prepare()
    ->run();

$y = $this->em->createQueryBuilder()
    ->select('a');

$z = $a?->b()
    ?->c();

function f()
{
    return $this->a()
        ->b();
}

foo($bar->baz()
    ->qux());

// An operator at the end of a line: a statement's only one, its first, a later one, and one alone on its line.
$user = $repository->
    find($id);

$someInstance->
    create()
    ->prepare();

$result = $builder
    ->where('a')->
    orderBy('b');

$value = $object
    ?->
    method();

// After a comment, it isn't fixed.
$user = $repository-> // Why.
    find($id);
