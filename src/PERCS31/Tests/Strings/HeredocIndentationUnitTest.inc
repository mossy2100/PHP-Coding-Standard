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
