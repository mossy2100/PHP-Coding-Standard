<?php

// === VALID: AT LEAST ONCE PAST THE ( LINE ===

if (
    $a
    && $b
) {
}

if (
    $a
        && $b
) {
}

if (
    $prefix
    . $name === 'admin'
) {
}

if (
    $prefix
        . $name === 'admin'
) {
}

function check(): void
{
    if ($ready) {
    } elseif (
        $a
        || $b
    ) {
    }
}

// === INVALID: NOT INDENTED ONCE ===

if (
    $a
&& $b
) {
}

while (
    $a
  || $b
) {
}

// An expression nested in the condition follows the general rule.
if (
    foo(
        $a
        . $b,
    )
) {
}

// phpcs:set PERCS31.Operators.OperatorPlacement requireExactConditionIndent true

// === VALID: EXACTLY ONCE ===

if (
    $a
    && $b
) {
}

if (
    $prefix
        . $name === 'admin'
    && $b
) {
}

if (
    $a
    && $prefix
        . $name === 'admin'
) {
}

// === INVALID: NOT EXACTLY ONCE ===

if (
    $a
        && $b
) {
}

if (
    $prefix
    . $name === 'admin'
) {
}

if (
    $a
    && $prefix
            . $name === 'admin'
) {
}

// phpcs:set PERCS31.Operators.OperatorPlacement requireExactConditionIndent false
