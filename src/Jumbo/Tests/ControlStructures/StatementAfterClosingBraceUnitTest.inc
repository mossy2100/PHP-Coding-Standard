<?php

// === VALID ===

if ($a) {
    echo 1;
} elseif ($b) {
    echo 2;
} else if ($c) {
    echo 3;
} else {
    echo 4;
}

try {
    echo 1;
} catch (E $e) {
    echo 2;
} finally {
    echo 3;
}

do {
    echo 1;
} while ($a);

for ($i = 0; $i < 1; $i++) {
    echo 1;
} // for $i

foo(function () {
    if ($a) { echo 1; }
});

if ($a) { echo 1; } ?>
<p>Hi</p>
<?php

// A class or function is PSR12.Classes.ClosingBrace's.
function a()
{
} echo 1;

// === INVALID ===

declare(ticks=1) {
    echo 1;
} echo 2;

if ($a) {
    echo 1;
} echo 2;

if ($a) {
    echo 1;
} else {
    echo 3;
} echo 2;

if ($a) {
    echo 1;
} else if ($b) {
    echo 3;
} echo 2;

for ($i = 0; $i < 1; $i++) {
    echo 1;
} echo 2;

foreach ($a as $b) {
    echo 1;
} echo 2;

while ($a) {
    echo 1;
} echo 2;

switch ($a) {
    case 1:
        break;
} echo 2;

try {
    echo 1;
} catch (E $e) {
    echo 3;
} echo 2;

try {
    echo 1;
} finally {
    echo 3;
} echo 2;

if ($a) {
    if ($b) {
        echo 1;
    } echo 2;
}
