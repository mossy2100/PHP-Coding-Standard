<?php

// phpcs:set PERCS31.Operators.UnaryOperatorSpacing checkPlusOperator true
// phpcs:set PERCS31.Operators.UnaryOperatorSpacing checkNegationOperator true
// phpcs:set PERCS31.Operators.UnaryOperatorSpacing checkNotOperator true
// phpcs:set PERCS31.Operators.UnaryOperatorSpacing checkBitwiseNotOperator true
// phpcs:set PERCS31.Operators.UnaryOperatorSpacing checkErrorControlOperator true

// === VALID ===

$plus = +$a;
$negated = -$a;
$not = !$a;
$bitwiseNot = ~$a;
$silenced = @file_get_contents($s);
$reference = &$a;
$binary = $a + $b;
$minus = $a - $b;
$wrapped = $a
    + $b
    - $c;
$afterIndex = $a[0] - 1;
$afterCall = foo() - 1;
$afterDecrement = $a-- - 1;
$notCommented = !/* Why. */ $a;
// The space is needed here, as without it PHP reads -- or ++.
$negatedTwice = - -$a;
$plusTwice = + +$a;
$negatedDecrement = - --$a;

// === INVALID ===

$plusSpace = + $a;
$plusLine = +
    $a;
$bitwiseNot = ~ $a;
$silenced = @ file_get_contents($s);
$commented = + /* Why. */ $a;
$afterCast = (int) + $a;
$afterConcat = 'a' . + $a;
$negated = - $a;
$negatedLine = -
    $a;
$negatedTwice = - - $a;
$afterBinary = $a - - 1;
$afterTernary = $a ? - 1 : - 2;
$inArray = [- 1, 'b' => - 2];
$compared = $a === - 1;
$not = ! $a;
$notLine = !
    $a;
$notTwice = ! ! $a;
$notCommentedSpace = ! /* Why. */ $a;
$reference = & $a;

function defaults(int $a = - 1, bool $b = ! A): int
{
    if (! $b) {
        return - $a;
    }

    return match ($a) {
        - 1 => - 2,
        default => 0,
    };
}

// phpcs:set PERCS31.Operators.UnaryOperatorSpacing checkPlusOperator false
// phpcs:set PERCS31.Operators.UnaryOperatorSpacing checkNegationOperator false
// phpcs:set PERCS31.Operators.UnaryOperatorSpacing checkNotOperator false
// phpcs:set PERCS31.Operators.UnaryOperatorSpacing checkBitwiseNotOperator false
// phpcs:set PERCS31.Operators.UnaryOperatorSpacing checkErrorControlOperator false
