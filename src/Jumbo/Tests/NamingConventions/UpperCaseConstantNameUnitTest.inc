<?php

// === VALID ===

const GLOBAL_LIMIT = 1;
define('DEFINED_LIMIT', 2);

class Holder
{
    public const int CLASS_LIMIT = 3;
}

enum Suit: string
{
    case Hearts = 'h';

    // PascalCase, as PER Coding Style 3.1 recommends.
    public const string DefaultColour = 'red';

    // UPPER_CASE, which PER allows: a severity 4 warning, hidden at the default severity, which these tests run at.
    public const string FALLBACK_COLOUR = 'black';
}

// === INVALID ===

const globalLimit = 1;

class LowerHolder
{
    public const int classLimit = 3;
}

enum LowerSuit: string
{
    case Hearts = 'h';

    // Neither PascalCase nor UPPER_CASE.
    public const string defaultColour = 'red';
}
