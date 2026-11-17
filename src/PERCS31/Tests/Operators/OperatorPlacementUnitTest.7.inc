<?php

// phpcs:set PERCS31.Operators.OperatorPlacement requireExactConditionIndent true

// An operator wrapped in an arrow function's body, in a condition. Walking back to the operand's start, the sniff once
// jumped forward at fn, and never finished.
if (
    $isActive
    && static fn() => 1
        + 2
) {
    run();
}

// phpcs:set PERCS31.Operators.OperatorPlacement requireExactConditionIndent false

// A match arm's value wrapped at an operator is indented once past the arm's first line, so level with a wrapped =>.
// The comma after the last of an arm's split conditions is inside the arm, so the value doesn't start a new item there.
$x = match ($y) {
    $condition1, $condition2
        => $variable1 + $variable2
        + $variable3,
    $condition3,
    $condition4,
        => $variable1 + $variable2
        + $variable3,
    default => 8,
};
