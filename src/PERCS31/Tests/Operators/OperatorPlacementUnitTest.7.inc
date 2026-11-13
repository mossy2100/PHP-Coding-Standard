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
