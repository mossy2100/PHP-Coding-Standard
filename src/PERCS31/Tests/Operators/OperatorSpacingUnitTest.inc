<?php

// The pipe operator, PHP 8.5 only. The other operators are left to the parent.

// === VALID ===

$a = $b |> trim(...);
$a = $b  |>  trim(...);
$a = $b
    |> trim(...);

// === INVALID ===

$a = $b|>trim(...);
