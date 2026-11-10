<?php

// === VALID ===

const TOP_LEVEL = 1;

final class Valid
{
    public const int LIMIT = 10;

    public const array SIZES = ['_small' => 1, '_large' => 2];

    public const string LABEL = self::LIMIT > 5 ? '_big' : '_small';

    private const DEFAULTS = [self::LIMIT => '_x'];
}

enum Suit
{
    case Hearts;

    public const Suit DEFAULT = self::Hearts;
}

// === INVALID ===

const _TOP_LEVEL = 1;

final class Invalid
{
    public const int _HIDDEN = 1;

    protected const _PROTECTED = 2;

    private const string __DOUBLE = 'x'; // Passes: a double underscore isn't a single one.

    public const A = 1, _B = 2;
}

interface HasConstant
{
    public const _INTERFACE = 1;
}
