<?php

// === VALID ===

#[Attribute]
final class Valid
{
    #[Deprecated]
    public const int OLD = 1;

    #[Deprecated]
    public int $property = 1;

    #[Override]
    #[Deprecated]
    public function method(#[SensitiveParameter] string $password, #[Unused] int $count): void
    {
        $closure = static function (#[SensitiveParameter] string $secret): void {
        };
        $arrow = static fn(#[SensitiveParameter] string $secret): string => $secret;
    }

    public function multiLine(
        #[SensitiveParameter]
        string $password,
        int $count,
    ): void {
    }

    public function __construct(#[SensitiveParameter] private string $promoted)
    {
    }
}

#[Attribute]
function plainFunction(#[SensitiveParameter] string $password): void
{
}

// === INVALID ===

#[Attribute] final class Invalid
{
    #[Deprecated] public const int OLD = 1;

    #[Deprecated] public int $property = 1;

    #[Override] #[Deprecated] public function method(): void
    {
    }

    public function multiLine(
        #[SensitiveParameter] string $password,
        int $count,
    ): void {
    }

    #[Deprecated] /* why */ public function commented(): void
    {
    }
}

#[Attribute] function invalidFunction(): void
{
}

function anonymousClasses(): array
{
    $valid = new
        #[Attribute]
        class {
            public int $x = 1;
        };

    $validTwo = new
        #[Attribute]
        #[Deprecated]
        class {};

    $sameLine = new #[Attribute] class {};

    $notIndented = new
    #[Attribute]
    class {};

    $classOnSameLine = new
        #[Attribute] class {};

    $twoOnOneLine = new #[Attribute] #[Deprecated] class ($a) {};

    $commented = new /* why */ #[Attribute] class {};

    consume(new #[Attribute] class {});

    return [new class {}];
}

class BlankLines
{
    /**
     * Valid: the docblock, then the attributes, then the method.
     */
    #[Foo]
    #[Bar]
    public function valid(#[Foo] int $a): void
    {
    }

    // Valid: a blank line between a parameter and the next one's attributes.
    public function blankBeforeParameterAttribute(
        int $a,

        #[Foo]
        int $b,
    ): void {
    }

    /**
     * A blank line after the docblock.
     */

    #[Foo]
    public function docblockGap(): void
    {
    }

    #[Foo]

    #[Bar]
    public function blockGap(): void
    {
    }

    public function parameterSpacing(#[Foo]  int $a, #[Bar]int $b): void
    {
    }
}

// A closure's attribute, as a call's argument: inline in a single-line list, on its own line in a split one.
$doubled = array_map(#[Pure] static fn(int $x): int => $x * 2, $items);
usort($items, #[Pure] static function (int $a, int $b): int {
    return $a <=> $b;
});
$split = array_map(
    #[Pure]
    static fn(int $x): int => $x,
    $items,
);
$spaced = array_map(#[Pure]  static fn(int $x): int => $x, $items);
$sameLine = array_map(
    #[Pure] static fn(int $x): int => $x,
    $items,
);
$misIndented = array_map(
#[Pure]
    static fn(int $x): int => $x,
    $items,
);

// Elsewhere, PER says nothing, so nothing is checked.
$pure = #[Pure]  fn(int $x): int => $x;
$plain = #[Pure]
    function (): int {
        return 1;
    };

$inline = #[Pure] fn(int $x): int => $x;
$handlers = [
    'save' => #[Pure] static fn(int $x): int => $x,
];
$twoSpaces = #[Pure]  fn(int $x): int => $x;
$ownLine = #[Pure]
    fn(int $x): int => $x;
$inArray = [
    #[Pure]
    fn(int $x): int => $x,
];

// Nothing between the last attribute and its target: no blank line, and no comment (not fixable).
#[Attribute]

final class BlankAfter
{
    #[Deprecated]
    // A comment.
    public function commented(): void
    {
    }
}

// A comment at the end of the attribute's line is fine, and is kept when a blank line after it is removed.
final class TrailingComment
{
    #[Deprecated] // Old.
    public function kept(): void
    {
    }

    #[Deprecated] // Old.

    public function blank(): void
    {
    }
}

// A named argument's closure keeps its attribute inline, even in a split list, as section 4.7 requires one space after
// the colon. The positional closure beside it has its attribute on the line before.
foo(
    #[Pure]
    function () {
        return 1;
    },
    callback: #[Pure] function () {
        return 2;
    },
    other: #[Pure] fn() => 3,
);

foo(callback: #[Pure] fn() => 1);

enum AttributesOnCases
{
    #[Label] case Same;

    #[Label]
    case Before;

    #[Label, Other] case Compound;

    #[Label] case Reported;

    #[Label]
    case Allowed;

    #[Label] /* A comment. */ case NotFixable;

    #[Label] public const int LIMIT = 1;
}

function commentsBeforeParameters(
    #[Pure]
    // A comment, not reported by default.
    int $a,
    // phpcs:set PERCS31.Attributes.AttributePlacement checkParameterComments true
    #[Pure]
    // A comment.
    int $b,
    #[Pure] // A comment at the end of the line.
    int $c,

    #[Pure]

    int $d,
    // phpcs:set PERCS31.Attributes.AttributePlacement checkParameterComments false
): void {
}

class Hooks
{
    public string $a {
        #[C] get => 'a';
    }

    public string $b {
        #[C]
        get => 'b';
    }

    public string $c {
        #[C] set(string $value) {
            $this->c = $value;
        }
    }

    public array $d {
        #[C] &get => $this->d;
    }

    public string $e {
        #[C] final get => 'e';
    }

    public string $f { #[C] get => 'f'; }

    // A hook's parameter follows the parameter rule.
    public string $g {
        set(#[C] string $value) {
            $this->g = $value;
        }
    }

    public string $h {
        set(#[C]  string $value) {
            $this->h = $value;
        }
    }

    public string $i {
        set(
            #[C]
            string $value,
        ) {
            $this->i = $value;
        }
    }

    public string $j {
        set(
            #[C] string $value,
        ) {
            $this->j = $value;
        }
    }

    // A method called get is still a method.
    #[C] public function get(): void {}
}

class SameLineSpacing
{
    public string $a {
        #[C]  get => 'a';
    }

    // phpcs:set PERCS31.Attributes.AttributePlacement checkSameLineSpacing true
    public string $b {
        #[C]  get => 'b';
    }

    public string $c {
        #[C]  &get => $this->c;
    }

    public string $d {
        #[C] get => 'd';
    }

    public string $e {
        #[C]
        get => 'e';
    }

    public string $f {
        #[C] /* A comment. */  get => 'f';
    }
}

enum SameLineSpacingEnum
{
    #[C]  case A;

    #[C] case B;

    #[C]
    case D;
}

$twoSpaces = #[Pure]  fn(int $x): int => $x;
$inline = #[Pure] fn(int $x): int => $x;
$ownLine = #[Pure]
    fn(int $x): int => $x;
$inArray = [
    #[Pure]  fn(int $x): int => $x,
];
// phpcs:set PERCS31.Attributes.AttributePlacement checkSameLineSpacing false

enum SpacingNotCheckedEnum
{
    #[C]  case A;
}

$notChecked = #[Pure]  fn(int $x): int => $x;

// === TWO OR MORE ATTRIBUTE BLOCKS ===

enum MultipleBlocks
{
    #[A] #[B] case X;

    #[A]
    #[B] case Y;

    #[A, B] case Z;

    #[A] #[B] /* A comment. */ case W;
}

class MultipleBlockHooks
{
    public string $a {
        #[A] #[B] get => 'a';
    }
}

function multipleBlocks(#[A] #[B] int $c, int $d = 1): void {}

usort($items, #[A] #[B] fn($x) => $x);

function oneBlock(#[A, B] int $c): void {}
