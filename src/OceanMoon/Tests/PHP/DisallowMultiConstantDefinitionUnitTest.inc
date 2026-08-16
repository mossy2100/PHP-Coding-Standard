<?php

class Foo
{
    // === VALID: single constant per statement ===

    public const int A = 1;

    public const int B = 2;

    // Valid: comma inside a short array literal is not a constant separator.
    public const array SCORES = [42, 33, 12, 99];

    // Valid: comma inside a parenthesized constructor call is not a constant separator.
    public const Complex I = new Complex(0, 1);

    // Valid: comma inside a parenthesized function call is not a constant separator.
    public const int SUM = add(1, 2);

    // === ERRORS: multiple constants in one statement ===

    // DisallowedMultiConstantDefinition: basic multi-constant definition.
    public const int C = 1, D = 2;

    // DisallowedMultiConstantDefinition: still detected after a parenthesized call - the comma inside add(1, 2)
    // must be skipped, but the comma separating F must still be caught.
    public const int E = add(1, 2), F = 3;

    // DisallowedMultiConstantDefinition: still detected after a short array literal.
    public const array G = [1, 2], H = 3;
}

// Valid: single file-scope (non-class) constant. T_CONST isn't class-specific, so this must also be checked.
const TOP_LEVEL_A = 1;

// DisallowedMultiConstantDefinition: multi-constant definition at file scope.
const TOP_LEVEL_B = 1, TOP_LEVEL_C = 2;
