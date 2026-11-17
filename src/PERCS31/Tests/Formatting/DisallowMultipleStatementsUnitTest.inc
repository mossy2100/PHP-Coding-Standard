<?php

// === VALID ===

interface HasName
{
    public string $name { get; set; }

    public string $label { get; }
}

abstract class Base
{
    abstract public string $title { get; set; }

    public string $hooked {
        get => 'x';
        set {
            $this->hooked = $value;
        }
    }
}

// === INVALID ===

$a = 1; $b = 2;

abstract class Invalid
{
    public string $body {
        get { $x = 1; return 'x'; }
    }

    abstract public string $mixed { get => 'x'; set; }
}

// === VALID: the hooks of a promoted parameter, which PERCS31.Classes.PropertyHooks reports ===

class Promoted
{
    public function __construct(
        public string $name { get => 'a'; set => ucfirst($value); },
    ) {
        $a = 1;
    }
}

// === INVALID: a second statement that ends on a later line, or has no semicolon ===

$a = 1; foo(
    $b,
);

$c = 1; if ($d) {
    echo 1;
}

foo(); $e = function () {
    return 1;
};

// A comment after the semicolon stays with the first statement.
$f = 1; /* Why. */ $g = [
    1,
];

// === VALID: nothing after the semicolon but a comment, a closing brace or a closing tag ===

for ($i = 0; $i < 2; $i++) {
    echo $i;
}

for (
    $i = 0; $i < 2;
    $i++
) {
    echo $i;
}

$h = 1; // Why.
$j = fn() => 1; /* Why. */
if ($h) { echo 1; }
?>
<p><?php echo 1; ?></p>
