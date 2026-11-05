<?php

// === VALID ===

function allowed(): void
{
    $allowedHeredoc = <<<COMPLIANT
        This
        is
          indented
        COMPLIANT;

    var_dump(
        'foo',
        <<<'COMPLIANT'
            This
            is
            a
            compliant
            parameter
            COMPLIANT,
        'bar',
    );
}

// === INVALID ===

function notAllowed(): void
{
    $notAllowed = <<<'COUNTEREXAMPLE'
This
  is
not

allowed.
COUNTEREXAMPLE;

    $tooFar = <<<'X'
            Too far
            X;

    $interpolated = <<<EOT
    {$tooFar} first
    EOT;
}

// === CONTEXT, VALID ===

var_dump(
    <<<'EOT'
        The first argument.
        EOT,
    <<<'EOT'
        The second argument.
        EOT,
);

$first = [
    <<<'EOT'
        The first element.
        EOT,
];

// A binary operator's own sniff moves it to the start of the line.
$concat = 'a' .
    <<<'EOT'
        Hello
        EOT;

$leading = 'a'
    . <<<'EOT'
        Hello
        EOT;

$ternary = $a
    ? <<<'EOT'
        Yes
        EOT
    : <<<'EOT'
        No
        EOT;

<<<'EOT'
    A statement of its own.
    EOT;

// === CONTEXT, INVALID ===

function context(): iterable
{
    $counterexample =
    <<<'EOT'
        Hello
        EOT;

    $operatorOnNextLine
        = <<<'EOT'
            Hello
            EOT;

    yield
        <<<EOT
            Hello {$counterexample}
            EOT;

    return
        <<<'EOT'
            Hello
            EOT;
}

$array = [
    'a' =>
        <<<'EOT'
            Hello
            EOT,
    'b'
        => <<<'EOT'
            Hello
            EOT,
];

$match = match ($a) {
    1 =>
        <<<'EOT'
            Hello
            EOT,
    default => '',
};

$arrowFunction = fn() =>
    <<<'EOT'
        Hello
        EOT;

// A comment in the way can't be fixed.
$comment = // Why.
    <<<'EOT'
        Hello
        EOT;
