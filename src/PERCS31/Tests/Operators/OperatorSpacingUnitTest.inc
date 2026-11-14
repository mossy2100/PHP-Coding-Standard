<?php
// phpcs:set PERCS31.Operators.OperatorSpacing ignoreNewlines true
// The sniff runs alone here, with every code. The PERCS31 ruleset excludes the codes for more than one space.

// === VALID ===

$a = $b && $c;
$a = $b . $c;
$a = $b
    . $c;
$a = $b or $c;

// === INVALID ===

$a = $b&&$c;
$a = $b  .  $c;
$a = $b  or  $c;

// A multi-catch's | is a union type, not an operator, so it isn't checked here.
try {
    foo();
} catch (FirstException|SecondException $e) {
    bar();
} catch (ThirdException | FourthException $e) {
    bar();
}

$flags = $a | $b;

// A by-reference hook's & isn't an operator.
class ReferenceHooks
{
    public array $items = [] {
        &get => $this->items;
    }

    public array $list = [] { &get => $this->list; }
}

// A reference assignment: valid, then one with no space after its =.
$valid = &$a;
$glued =& $a;
$spaced = & $a;

// A reference assignment: no space either side, no space after, and no space before.
$none=&$a;
$noneAfter =&$a;
$noneBefore= &$a;
$noneBeforeCompound=& $a;
foo($arg=&$a);
$element['key']=&$a;

// A by-reference hook with a body in braces: the & isn't an operator.
class ReferenceHookWithBody
{
    private array $items = [];

    public array $long {
        &get {
            return $this->items;
        }
    }
}

function defaults(int $a=1, int $b = 2, int $d  =4, int $e  = 5)
{
    return static fn(int $f=6): int => $f;
}

declare(ticks=1);

// A set hook's parameter types aren't operators, and neither is a nullable type's ?.
class HookParameterTypes
{
    public int|string $union {
        set(int|string $value) {
            $this->union = $value;
        }
    }

    public \Countable&\Stringable $intersection {
        set(\Countable&\Stringable $value) => $value;
    }

    public (\Countable&\Stringable)|null $dnf {
        set((\Countable&\Stringable)|null $value) => $value;
    }

    public ?int $nullable {
        set(?int $value) => $value;
    }

    public int|string $split {
        set(
            int
            |string $value,
        ) => $value;
    }

    public function __construct(public int|string $promoted { set(int|string $value) => $value; }) {}

    // A real operator in a hook is still checked.
    public int $operators {
        get => $this->operators|1;
        set(#[Foo(1|2)] int $value) {
            $this->operators = 1|2;
            foo($value|1);
            set($value|1);
            $this->set($value&1);
        }
    }
}

// A unary operator starting a statement or block isn't spaced, and a binary one after an expression's } is.
foo();
-$k->b();
+$k->c();
if ($a) {
    -$k->d();
}
$e = (int) -$f;
$m = match ($b) {
    1 => 2,
} -1;
$n = ${'x'} -1;

// A unary + or - after another prefix operator isn't binary.
$a = !-$b;
$a = ~-$b;
$a = @-$b;
$a = !+$b;
