<?php

// === VALID ===

#[A]
#[B]
class Separate {}

#[A, B]
class OneBlock {}

enum OneBlockCase
{
    #[A, B] case Z;
}

// A single-line list is left to PERCS31.Attributes.AttributePlacement, which splits it.
function inList(#[A] #[B] int $c): void {}

usort($items, #[A] #[B] fn($x) => $x);

// === INVALID ===

#[A] #[B]
class SameLine {}

enum TwoBlocks
{
    #[A] #[B] case X;
}

class Hooks
{
    public string $a {
        #[A] #[B]
        get => 'a';
    }

    public string $b {
        #[A] /* A comment. */ #[B]
        get => 'b';
    }
}

// Code before the blocks, so the next block is indented one level more.
$pure = #[A] #[B] fn($x) => $x;
