<?php

declare(strict_types=1);

namespace Vendor;

class Example
{
    public function run(): mixed
    {
        // A condition split at every boolean operator, as PSR12's BooleanOperatorPlacement fix leaves it.
        if (
            ($bestValue < 1.0 && $prefixedValue > $bestValue) || ($prefixedValue >= 1.0 && $prefixedValue < $bestValue)
        ) {
            $bestValue = $prefixedValue;
        }

        // A long condition with no boolean operator. A new line is indented once past its operand's line.
        if (
            $someLongVariableName + $anotherLongVariableName + $yetAnotherLongVariableName + $finalLongVariableName
                + $more > 1
        ) {
            $bestValue = 0;
        }

        // A foreach header too long for one line.
        foreach (
            $this->someLongMethodName($someLongArgumentName) + $this->anotherLongMethodName($anotherLongArgument)
            as $item
        ) {
            $bestValue = $item;
        }

        // A group on the expression's first line, too long for one line.
        $isMatch = ($someLongConditionName || $anotherLongConditionName || $yetAnotherLongConditionName || $theLastOne)
            && $more;

        // An argument on its own line, split at the wrong operator.
        $total = max(
            $someLongVariableName * $anotherLongVariableName + $yetAnotherLongVariableName * $finalLongVariableName
                + $andOneMoreVariableName * $lastOne,
            0,
        );

        // An arrow function's body too long for one line.
        $double = static fn(int $value): int => $value * $someLongMultiplierName
            + $anotherLongOffsetName * $yetAnotherFactor + 1;

        // A match arm's value split where it fits.
        $label = match ($bestValue) {
            0 => 'none',
            default => "Some text, {$bestValue}",
        };

        // A concatenation in a ternary's value, with its operator at the end of a line.
        $message = $isMatch
            ? "Some long text that goes on for a while, then some more, {$this->describe($bestValue)} and then even "
                . 'more text.'
            : 'none';

        return $someLongVariableName !== null && $anotherLongVariableName > 10
            || $yetAnotherLongVariableName < 5 && $isMatch;
    }
}
