<?php

// Valid: every variable in braces, or no variables.
$a = "Hi {$name}";
$a = "City: {$user->address->city}, key {$row['id']}, call {$obj->get()}";
$a = 'Single $quoted';
$a = "Escaped \$price and {$total}";
$a = "Old ${name}";
$a = <<<'NOW'
    Nowdoc $name
    NOW;
$a = <<<EOT
    Heredoc {$name}
    EOT;

// Invalid: a variable without braces.
$a = "Hi $name";
$a = "Age $user->age and $user?->nickname";
$a = "Index $row[0], key $row[id], variable key $row[$key], last $row[-1]";
$a = "Mixed {$a} and $b";
$a = "Escaped \$price and $total";
$a = "Line one $first
line two $second";
$a = <<<EOT
    Heredoc $name
    and {$done} and $user->age
    EOT;
