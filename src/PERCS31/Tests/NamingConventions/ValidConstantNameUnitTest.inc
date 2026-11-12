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

// A global constant has no visibility, so it isn't checked by default.
const _TOP_LEVEL = 1;

// === INVALID ===

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

// A closure in a constant's value (PHP 8.5) has statements of its own, which aren't constants.
enum HoldsClosure
{
    case A;

    public const Handler = static function (int $a): int {
        $total = $a + 1;

        return $total;
    };
    public const Lookup = ['a' => 1, 'b' => [2, 3]], Other = self::A;
}

// With checkGlobalConstants on, a global constant is checked too.
// phpcs:set PERCS31.NamingConventions.ValidConstantName checkGlobalConstants true
const _CHECKED = 1;
const __DOUBLE_CHECKED = 2;
// phpcs:set PERCS31.NamingConventions.ValidConstantName checkGlobalConstants false
