<?php

// Valid: on one line, too long to collapse, or a comment.
class A implements B, C
{
    public int $d;
}
class Abcdefghijklmnopqrstuvwxyz implements
    Bbcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz,
    Cbcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz,
    Dbcdefghijklmnopqrstuvwxyz
{
    public int $e;
}
class F implements
    // Why.
    G
{
    public int $h;
}

// Invalid: all of these fit.
class I implements
    J,
    K
{
    public int $l;
}
interface M extends
    N,
    O
{
    public function p(): void;
}
enum Q: int implements
    R,
    S
{
    case T = 1;
}
$u = new class implements
    V,
    W
{
    public int $x;
};
