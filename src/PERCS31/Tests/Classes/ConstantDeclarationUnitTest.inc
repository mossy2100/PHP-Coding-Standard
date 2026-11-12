<?php

// A global constant isn't checked.
const  GLOBAL_LIMIT = 1;

final class Valid
{
    public const LIMIT = 1;
    public const int TYPED = 1;
    final protected const ?string NULLABLE = null;
    private const int|string UNION = 1;
    const BARE = 1;
    public const A = 1, B = 2;
}

final class Invalid
{
    public const  LIMIT = 1;
    public const  int TYPED = 1;
    public const int  SPACED = 1;
    public const  ?string  BOTH = null;
    public const
        WRAPPED = 1;
    public const int
        TYPE_WRAPPED = 1;
    private const int|string   UNION = 1;
    public const /* A single space each side of a comment passes. */ COMMENTED = 1;
}

interface HasConstant
{
    public const  int LIMIT = 1;
}

enum Suit
{
    case Hearts;

    public const Suit  Wild = self::Hearts;
}
