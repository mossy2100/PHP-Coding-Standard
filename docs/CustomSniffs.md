# Mossy2100 Coding Standard - Custom Sniffs

This document describes the custom PHP_CodeSniffer sniffs created for the [Mossy2100 Coding Standard](../README.md).

---

## Mossy2100.Arrays.ArrayDeclaration

Requires a multi-line array to be written on one line if it fits.

**Why:** no inherited sniff collapses a multi-line array that fits on one line. The stock array sniffs (see
[Inherited Sniffs](InheritedSniffs.md)) only check the layout of an array that's already multi-line.

**Auto-fixable:** yes.

- **Fits** means the whole resulting line is within `maxLineLength`: the text before the opening bracket, the collapsed
  array, and the text after the closing bracket on its line. A fix never creates a line that's too long.
- **Outermost first.** If an enclosing array fits, only that one is reported, as fixing it collapses the nested ones.
- **Clean output.** The fix uses single spaces, no space inside brackets or around `->`, `?->` and `::`, and no trailing
  commas.
- **Skipped** if the array contains a comment, a heredoc or nowdoc, a multi-line string, or a brace body such as a
  closure, anonymous class or `match` expression.

**Good:**

```php
$user = ['name' => 'John', 'email' => 'john@example.com'];

$words = [
    'The quick brown fox jumps over the lazy dog.',
    'Pack my box with five dozen liquor jugs, please.',
    'How vexingly quick daft zebras jump!',
];
```

**Bad:**

```php
$user = [
    'name' => 'John',
    'email' => 'john@example.com',
];
```

**Configuration:**

| Property        | Type | Default | Description                                |
| --------------- | ---- | ------- | ------------------------------------------ |
| `maxLineLength` | int  | 120     | Maximum line length for a collapsed array. |

To use a different limit, set every line-length property, as described in [Line Length](LineLength.md).

---

## Mossy2100.Attributes.AttributePlacement

Requires an attribute to be on the line before its target, as [PER Coding Style 3.0, Section 12.2](https://www.php-fig.org/per/coding-style/#122-placement) requires. The exception
is a parameter in a single-line parameter list, whose attribute must be inline.

**Why:** `SlevomatCodingStandard.Attributes.AttributeAndTargetSpacing` has one option, `allowOnSameLine`, which allows
an attribute on the same line as its target either everywhere or nowhere. The Mossy2100 ruleset turns it on, so that a
parameter's attribute can be inline. This sniff then reports every other same-line attribute, including an inline one in
a multi-line parameter list.

An attribute on an anonymous class follows [PER Coding Style 3.1, Section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes) instead. The attributes start on the line
after `new`, indented once, with `class` on the line after them at the same indent. `Mossy2100.WhiteSpace.ScopeIndent`
then indents the class body from `class`.

**Auto-fixable:** yes, unless a comment is between the attribute and its target. For an anonymous class, the fix puts
each attribute block on its own line. It isn't applied when the class is in parentheses, such as a function call's
argument, as it would conflict with the multi-line call sniffs.

**Good:**

```php
#[Attribute]
final class Session
{
    #[Override]
    public function login(#[SensitiveParameter] string $password): bool
    {
        // ...
    }

    public function connect(
        string $host,
        #[SensitiveParameter]
        string $password,
    ): void {
        // ...
    }
}

$handler = new
    #[Attribute]
    class {
        public int $retries = 3;
    };
```

**Bad:**

```php
#[Attribute] final class Session
{
    #[Override] public function login(#[SensitiveParameter] string $password): bool
    {
        // ...
    }

    public function connect(
        string $host,
        #[SensitiveParameter] string $password,
    ): void {
        // ...
    }
}

$handler = new #[Attribute] class {
    public int $retries = 3;
};
```

---

## Mossy2100.Attributes.DisallowMultiLineAttributesJoining

Disallows several attributes in one attribute block when the block spans more than one line. A block on one line, such
as `#[Attribute, Deprecated]`, is allowed. This replaces `SlevomatCodingStandard.Attributes.DisallowAttributesJoining`.

**Why:** [PER Coding Style 3.0, Section 12.3](https://www.php-fig.org/per/coding-style/#123-compound-attributes) allows several attributes in one block, but says that if the list is split
into multiple lines, the attributes MUST be in separate blocks. The Slevomat sniff disallows joining altogether, and its
fix leaves a block written over several lines in a poor layout. No other sniff in PHPCS, Slevomat, PHPCSExtra or the
other standards checked covers it.

**Auto-fixable:** yes, unless there's a comment in the block. The fix puts each attribute in its own block, on its own
line.

**Good:**

```php
#[Attribute, Deprecated]
final class Marker {}

#[Attribute]
#[Deprecated('Use Marker instead.')]
final class OldMarker {}
```

**Bad:**

```php
#[
    Attribute,
    Deprecated('Use Marker instead.'),
]
final class OldMarker {}
```

---

## Mossy2100.Classes.ClassDeclaration

Checks the declaration of a class, interface, trait or enum, and the placement of its braces, except in an empty body
written as `{}` on one line, e.g. `class Foo {}`. This replaces `PSR2.Classes.ClassDeclaration`.

**Why:** `PSR2.Classes.ClassDeclaration` rejects an empty body written as `{}` on one line, which
[PER Coding Style 3.0, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) recommends. This sniff skips that case, and leaves everything else to the
parent. For an empty body, the checks on `extends` and `implements` still run. See [Mossy2100.Formatting.EmptyBody](#mossy2100formattingemptybody).

**Auto-fixable:** yes, as the parent.

---

## Mossy2100.Classes.ClassInstantiationNoBrackets

Warns about parentheses around a class instantiation that's immediately dereferenced with `->`, `?->` or `::`, such as
`(new Foo())->bar()`.

**Why:** [PER Coding Style 3.0, Section 4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says the instantiation SHOULD NOT be wrapped in parentheses, and PHP 8.4 allows
`new Foo()->bar()`. As it's a SHOULD, this is a warning. `SlevomatCodingStandard.PHP.UselessParentheses` has a similar
check, but it misses `::`, and removes the parentheses from `(new Foo() ?: new Bar())->baz()`, changing its meaning.
This sniff only reports parentheses holding exactly one `new` expression. A `new` without an argument list, such as
`(new Foo)->bar()`, is left until `PSR12.Classes.ClassInstantiation` has added the `()`, as PHP needs them.

**Auto-fixable:** yes.

**Good:**

```php
$total = new Calculator()->add(1, 2);

$fallback = ($cached ?: new Calculator())->add(1, 2);
```

**Bad:**

```php
$total = (new Calculator())->add(1, 2);
```

---

## Mossy2100.Classes.DisallowPromotedPropertyHooks

Disallows property hooks on promoted constructor parameters (PHP 8.4+). Declare the property in the class body instead.
Promoted parameters without hooks are still allowed.

**Why:** hooks are hard to read in a constructor signature. Also, sniffs from the base coding standards misread them,
and report errors that `phpcbf` can't fix (see below). This is stricter than [PER Coding Style 3.0](https://www.php-fig.org/per/coding-style/#49-property-hooks), which allows a single
short hook on one line.

**Auto-fixable:** no. Moving a property and its hooks into the class body is a bigger rewrite than a fixer should risk.

Two Slevomat sniffs treat a variable in the hook body, such as `$value` or `$this`, as another constructor parameter:

- `SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint`
- `SlevomatCodingStandard.TypeHints.ParameterTypeHintSpacing.NoSpaceBetweenTypeHintAndParameter`

A multi-line hook also gets `Squiz.Functions.MultiLineFunctionDeclaration.Indent`.

**Good:**

```php
class User
{
    public string $name {
        set => trim($value);
    }

    public function __construct(string $name, public readonly int $age)
    {
        $this->name = $name;
    }
}
```

**Bad:**

```php
class User
{
    public function __construct(public string $name { set => trim($value); }, public readonly int $age) {}
}
```

---

## Mossy2100.Classes.EmptyLinesAroundClassBraces

Disallows blank lines after a class's opening brace and before its closing brace. This replaces
`SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces`.

**Why:** `SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces` rejects an empty body written as `{}` on one line,
which [PER Coding Style 3.0, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) recommends. This sniff skips that case, and leaves everything else to the
parent. See [Mossy2100.Formatting.EmptyBody](#mossy2100formattingemptybody).

**Auto-fixable:** yes, as the parent.

---

## Mossy2100.Classes.EnumMethodVisibility

Requires a non-public method in an enum to be `private`, not `protected`.

**Why:** [PER Coding Style 3.0, Section 9](https://www.php-fig.org/per/coding-style/#9-enumerations) says non-public methods in an enum MUST use `private`, as enums don't support
inheritance. No inherited sniff checks it.

**Auto-fixable:** yes. The fix doesn't change behaviour, since nothing can extend an enum. It also removes `final` if
present, as PHP warns that a private method can't be final.

**Good:**

```php
enum Suit
{
    case Hearts;

    private function symbol(): string
    {
        return '♥';
    }
}
```

**Bad:**

```php
enum Suit
{
    case Hearts;

    protected function symbol(): string
    {
        return '♥';
    }
}
```

---

## Mossy2100.Classes.PropertyDeclaration

Verifies that properties are declared correctly. This replaces `PSR2.Classes.PropertyDeclaration`.

**Why:** PSR-12's `PSR2.Classes.PropertyDeclaration` misreads variables in PHP 8.4 property hook bodies, such as
`$value` and `$this`, as property declarations, and reports false errors on them. It also only warns about a leading
underscore, and doesn't check promoted constructor properties, where [PER Coding Style 3.0, Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says MUST NOT.
This sniff also checks that `get` comes before `set` in a property's hooks, which no inherited sniff does.

**Auto-fixable:** only `HookOrder`, unless there's a comment between the hooks.

It reports:

- A property name with a leading underscore (`Underscore`), for any visibility, including promoted constructor
  parameters. PER Coding Style 3.0 disallows it too.
- The `var` keyword (`VarUsed`).
- A property with no visibility (`ScopeMissing`).
- `set` before `get` in a property's hooks (`HookOrder`). [PER Coding Style 3.0, Section 4.10](https://www.php-fig.org/per/coding-style/#410-interface-and-abstract-properties) requires this for an
  abstract or interface property with no hook bodies, e.g. `{ get; set; }`. The Mossy2100 standard applies it to every
  hook list, including ones with bodies, `&` or attributes.

`SlevomatCodingStandard.Classes.PropertyDeclaration` checks modifier order and the spacing after the type, and
`SlevomatCodingStandard.Classes.DisallowMultiPropertyDefinition` checks for several properties in one statement.

**Good:**

```php
class User
{
    private string $secret = '';
    public static int $total = 0;

    public string $name {
        get => ucfirst($this->name);
        set => trim($value);
    }

    public function __construct(private int $id) {}
}
```

**Bad:**

```php
class User
{
    private string $_secret = '';  // Leading underscore.
    var $legacy;                   // The var keyword.
    static int $total = 0;         // No visibility.

    // The set hook before the get hook.
    public string $name {
        set => trim($value);
        get => ucfirst($this->name);
    }

    // Leading underscore on a promoted parameter.
    public function __construct(private int $_id) {}
}
```

---

## Mossy2100.ControlStructures.LanguageConstructWithParentheses

Disallows parentheses around the argument of a language construct, such as `echo('x')`, except `exit` and `die`. This
replaces `SlevomatCodingStandard.ControlStructures.LanguageConstructWithParentheses`.

**Why:** the Slevomat sniff reports `die()` and fixes it to `die`. [PER Coding Style 3.0, Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says `exit()` and
`die()` SHOULD have parentheses, which `Universal.PHP.RequireExitDieParentheses` checks, so the two sniffs would undo
each other's fixes. This sniff skips `exit` and `die`, and leaves everything else to the parent.

**Auto-fixable:** yes, as the parent.

---

## Mossy2100.ControlStructures.MatchArrowSpacing

Requires exactly one space before and after the `=>` in a `match` arm. Aligned arms make noisy diffs.

**Why:** `Squiz.WhiteSpace.OperatorSpacing` checks `=>` in arrays and `foreach` loops, but not in `match` arms, so
aligned arms aren't caught.

**Auto-fixable:** yes.

A line break before or after the arrow is allowed, for wrapping a long arm.

**Good:**

```php
$label = match ($size) {
    1 => 'one',
    default => 'many',
};
```

**Bad:**

```php
$label = match ($size) {
    1       => 'one',
    default => 'many',
};
```

---

## Mossy2100.ControlStructures.MultiLineTernaryOperator

Requires a ternary that spans lines to take three, with `?` and `:` each starting a line, indented once. This replaces
`SlevomatCodingStandard.ControlStructures.DisallowTrailingMultiLineTernaryOperator`.

**Why:** [PER Coding Style 3.0, Section 6.4](https://www.php-fig.org/per/coding-style/#64-operators-placement) says a multi-line ternary MUST occupy three lines, never two, with each
operator at the start of its line. The Slevomat sniff only reports a `?` at the end of a line. No inherited sniff
reports a ternary on two lines, a trailing `:`, or the wrong indentation. A short ternary (`?:`) is a binary operator in
PER, so it isn't checked here.

**Auto-fixable:** yes, unless a comment is next to `?` or `:`.

The indentation is always 4 spaces, as [PER Coding Style 3.0, Section 2.4](https://www.php-fig.org/per/coding-style/#24-indenting) requires. This sniff doesn't use the `indent`
property of `Mossy2100.WhiteSpace.ScopeIndent`.

**Good:**

```php
$label = $isActive
    ? 'Active'
    : 'Inactive';
```

**Bad:**

```php
$label = $isActive
    ? 'Active' : 'Inactive';

$label = $isActive ? 'Active' :
    'Inactive';
```

---

## Mossy2100.ControlStructures.RequireSingleLineHeader

Requires a multi-line `foreach`, `for`, `switch` or `match` header to be written on one line if it fits.

**Why:** `SlevomatCodingStandard.ControlStructures.RequireSingleLineCondition` does this for `if`, `elseif`, `while` and
`do-while`, but not for `foreach`, `for`, `switch` or `match`. A multi-line header is often left over after
`Mossy2100.Arrays.ArrayDeclaration` collapses the array inside it.

**Auto-fixable:** yes.

**Fits** and **skipped** mean the same as for [Mossy2100.Arrays.ArrayDeclaration](#mossy2100arraysarraydeclaration): the whole resulting line, up to and
including ` {`, must be within `maxLineLength`, and a header containing a comment, heredoc, multi-line string or brace
body is left alone.

**Good:**

```php
foreach ([$m->inv(), $m->pow(-1)] as $inverse) {
    // ...
}
```

**Bad:**

```php
foreach (
    [$m->inv(), $m->pow(-1)] as $inverse
) {
    // ...
}
```

**Configuration:**

| Property        | Type | Default | Description                                 |
| --------------- | ---- | ------- | ------------------------------------------- |
| `maxLineLength` | int  | 120     | Maximum line length for a collapsed header. |

To use a different limit, set every line-length property, as described in [Line Length](LineLength.md).

---

## Mossy2100.ControlStructures.SwitchDeclaration

Checks `switch` statements. This replaces `PSR2.ControlStructures.SwitchDeclaration`, and adds two rules:

- The last non-empty case MUST end with a terminating statement, such as `break`, `return` or `throw`.
- A multi-line case condition MUST be wrapped in parentheses, with `):` on a line of their own.

**Why:** [PER Coding Style 3.1, Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match) adds both rules. The parent only checks a case that's followed by another,
and doesn't check multi-line conditions.

**Auto-fixable:** yes. The fix adds `break;` before the `}`, or adds the parentheses, or moves the `)` to its own line.
It's left to a person when there's a comment in the way, or the `}` follows code on its line.

**Good:**

```php
switch (true) {
    case (
        $total > 100
        && $isMember
    ):
        applyDiscount();
        break;
    default:
        chargeFullPrice();
        break;
}
```

**Bad:**

```php
switch (true) {
    case $total > 100
        && $isMember:
        applyDiscount();
        break;
    default:
        chargeFullPrice();
}
```

---

## Mossy2100.Formatting.DisallowMultipleStatements

Requires each statement to be on a line by itself. This replaces `Generic.Formatting.DisallowMultipleStatements`.

**Why:** the Generic sniff treats the operations of an abstract or interface property, `{ get; set; }`, as two
statements on one line. [PER Coding Style 3.0, Section 4.10](https://www.php-fig.org/per/coding-style/#410-interface-and-abstract-properties) requires them on one line. This sniff skips a semicolon that
ends a `get` or `set` operation when the previous one does too, and leaves everything else to the Generic sniff.

**Auto-fixable:** yes.

**Good:**

```php
interface HasName
{
    public string $name { get; set; }
}

$a = 1;
$b = 2;
```

**Bad:**

```php
$a = 1; $b = 2;
```

---

## Mossy2100.Formatting.EmptyBody

Reports an empty class, interface, trait, enum, function, method or closure body that isn't written as `{}` on the same
line as the declaration, after a single space. It's a warning, except for a closure, where it's an error.

**Why:** [PER Coding Style 3.1, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says an empty class or function body SHOULD be abbreviated that way,
including a constructor that only promotes properties. [Section 7](https://www.php-fig.org/per/coding-style/#7-closures) says an empty closure body MUST be. No inherited sniff
checks either. An empty anonymous class is treated the same way, for consistency. A body containing a comment isn't
empty.

**Auto-fixable:** yes, unless a comment is between the declaration and the opening brace.

**Good:**

```php
final class NotFoundException extends Exception {}

final class Point
{
    public function __construct(public readonly int $x, public readonly int $y) {}

    public function reset(): void {}
}

$noop = static function (): void {};
```

**Bad:**

```php
final class NotFoundException extends Exception
{
}

final class Point
{
    public function __construct(public readonly int $x, public readonly int $y)
    {
    }

    public function reset(): void{}
}

$noop = static function (): void {
};
```

---

## Mossy2100.Functions.MultiLineFunctionDeclaration

Checks the layout of function and closure declarations, including the placement of the opening brace, except in an empty
body written as `{}` on one line, e.g. `function reset(): void {}`. This replaces
`Squiz.Functions.MultiLineFunctionDeclaration`.

**Why:** `Squiz.Functions.MultiLineFunctionDeclaration` rejects an empty body written as `{}` on one line, which
[PER Coding Style 3.1, Sections 4.4 and 7](https://www.php-fig.org/per/coding-style/#7-closures) requires for a closure and recommends for a function. This sniff skips that
case, and leaves everything else to the parent. For an empty body after a single-line signature, the parent's
`CloseBracketNewLine` check is skipped too, as it can't be run on its own. See [Mossy2100.Formatting.EmptyBody](#mossy2100formattingemptybody).

**Auto-fixable:** yes, as the parent.

---

## Mossy2100.Namespaces.UnusedUses

Disallows unused `use` statements, including in annotations. This replaces
`SlevomatCodingStandard.Namespaces.UnusedUses`.

**Why:** the Slevomat sniff reads the type of a `use` statement with a case-sensitive check, so `use Function
Vendor\helper;` is treated as a class import. It then reports the import as unused, and `phpcbf` deletes it, which can
break the code. This sniff skips the whole file while a `function` or `const` keyword after `use` isn't in lower case.
`Universal.UseStatements.LowercaseFunctionConst` reports that as an error and fixes it, and on `phpcbf`'s next pass the
import is read correctly. Everything else is left to the parent. It can be removed once Slevomat fixes the check.

**Auto-fixable:** yes, as the parent.

---

## Mossy2100.NamingConventions.ValidConstantName

Disallows a constant name that starts with an underscore, for class constants and `const` statements at file scope.

**Why:** [PER Coding Style 3.0, Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says constant names MUST NOT start with an underscore.
`Generic.NamingConventions.UpperCaseConstantName` only checks the case, so `const _HIDDEN = 1;` passes it.

**Auto-fixable:** no. Renaming a constant is a refactor, not a fix.

**Good:**

```php
final class Config
{
    public const int LIMIT = 10;
}
```

**Bad:**

```php
final class Config
{
    public const int _LIMIT = 10;
}
```

---

## Mossy2100.NamingConventions.ValidEnumCaseName

Requires enum case names to be in PascalCase: a capital first letter, only letters and digits, and not all capitals,
apart from a single letter.

**Why:** [PER Coding Style 3.0, Section 9](https://www.php-fig.org/per/coding-style/#9-enumerations) says enum case declarations MUST use PascalCase. No inherited sniff checks it.
A run of capitals inside a name, such as `HTTPStatus`, is allowed, as PER section 2.7 only recommends treating acronyms
as words. Based on `PhpCollective.Classes.EnumCaseCasing`, from php-collective/code-sniffer, which skips a name with no
capitals, such as `hearts`.

**Auto-fixable:** no. Renaming a case would break references to it in other files.

**Good:**

```php
enum Suit
{
    case Hearts;
    case BigJoker;
}
```

**Bad:**

```php
enum Suit
{
    case hearts;
    case BIG_JOKER;
}
```

---

## Mossy2100.NamingConventions.ValidVariableName

Warns about a variable or parameter whose name starts with an underscore, inside a class, enum, trait or interface.

**Why:** `Squiz.NamingConventions.ValidVariableName` reports a leading underscore as an error outside a class. Inside
one, it strips the underscore before checking camelCase, because the Squiz convention prefixes private members with one.

**Auto-fixable:** no. Renaming a variable is a refactor, not a fix.

This sniff fills only that gap. It doesn't report:

- Superglobals, such as `$_GET` and `$_SERVER`.
- A name that Squiz already reports, because the rest of it isn't camelCase either, such as `$_user_name`.
- Properties, including promoted constructor parameters, and static property access (`self::$_count`).
  `Mossy2100.Classes.PropertyDeclaration` checks those where they're declared.

**Good:**

```php
class Order
{
    public function total(array $items): int
    {
        $sum = 0;
        foreach ($items as $item) {
            $sum += $item;
        }

        return $sum;
    }
}
```

**Bad:**

```php
class Order
{
    public function total(array $_items): int
    {
        $_sum = 0;
        foreach ($_items as $_item) {
            $_sum += $_item;
        }

        return $_sum;
    }
}
```

---

## Mossy2100.Operators.OperatorPlacement

Disallows a binary operator at the end of a line, when a statement wraps at it. The operator goes at the start of the
next line instead. Operators within a line aren't affected.

**Why:** [PER Coding Style 3.0, Section 6.4](https://www.php-fig.org/per/coding-style/#64-operators-placement) says a wrapped operator MUST be at the start of the new line, and section 7.1
says the same of an arrow function's `=>`. Only two inherited sniffs check this, each for one case:
`Universal.Operators.ConcatPosition` for `.`, and `PSR12.ControlStructures.BooleanOperatorPlacement` for `&&` and `||`
in a condition. This sniff covers every other binary operator: assignment, arithmetic, comparison, bitwise and logical
operators, `??`, `instanceof`, the short ternary `?:`, the arrow function's `=>`, and the pipe operator `|>` (PHP 8.5,
PER Coding Style 3.1). A full ternary is checked by `Mossy2100.ControlStructures.MultiLineTernaryOperator`, and `=>` in
an array or `match` arm isn't an operator.

**Auto-fixable:** yes, unless a comment follows the operator.

**Good:**

```php
$total = $price * $quantity
    + $shipping
    - $discount;

$label
    = $isActive ? 'Active' : 'Inactive';
```

**Bad:**

```php
$total = $price * $quantity +
    $shipping -
    $discount;

$label =
    $isActive ? 'Active' : 'Inactive';
```

---

## Mossy2100.Operators.UnaryOperatorSpacing

Disallows whitespace, including a line break, between a prefix unary `+`, `~` or `@` and its operand.

**Why:** there should be no whitespace between a prefix unary operator and its operand. Inherited sniffs check the
others: `SlevomatCodingStandard.Operators.NegationOperatorSpacing` for `-`, `Generic.Formatting.SpaceAfterNot` for `!`,
`Generic.WhiteSpace.IncrementDecrementSpacing` for `++` and `--`, and Slevomat's `ReferenceSpacing` and
`SpreadOperatorSpacing` for `&` and `...`. Nothing checks `+`, `~` or `@`. A cast keeps the single space after it that
[PER Coding Style 3.0, Section 6.1](https://www.php-fig.org/per/coding-style/#61-unary-operators) requires.

**Auto-fixable:** yes, unless a comment is between the operator and its operand.

**Good:**

```php
$flags = ~$mask;
$contents = @file_get_contents($path);
```

**Bad:**

```php
$flags = ~ $mask;
$contents = @ file_get_contents($path);
```

---

## Mossy2100.PHP.DisallowMultiConstantDefinition

Disallows defining several constants in one statement (e.g. `const A = 1, B = 2;`), for class and file-scope constants.
This is a fork of `SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition`.

**Why:** the Slevomat sniff mistakes a comma inside parentheses for a constant separator. So it flags a single, valid
constant such as `const I = new Complex(0, 1);`, or one whose value is `array(1, 2)` or a closure with two parameters
(PHP 8.5+). This fork skips commas inside parentheses, as upstream already does inside `[...]`. It's temporary: the
upstream fixes (slevomat/coding-standard#1873 and #1874) were merged on 2026-10-26, and the fork can go once a Slevomat
release includes them.

**Auto-fixable:** yes.

**Good:**

```php
class Example
{
    public const int A = 1;
    public const int B = 2;
    public const array SCORES = [42, 33, 12, 99]; // Not flagged: the commas belong to the array literal.
}

const I = new Complex(0, 1); // Not flagged: the comma belongs to the constructor call (PHP 8.1+).
```

**Bad:**

```php
class Example
{
    public const int A = 1, B = 2; // Flagged: two constants in one statement.
}

const C = 3, D = 4; // Flagged: the same applies outside a class.
```

---

## Mossy2100.PHP.RequireCloneParentheses

Warns about `clone` without parentheses, such as `clone $a`, or with a space before them, such as `clone ($a)`.

**Why:** [PER Coding Style 3.1, Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says `clone()` SHOULD always be called with parentheses, as it's a function
as of PHP 8.5. As it's a SHOULD, this is a warning. No inherited sniff checks it.

**Auto-fixable:** yes, when the operand is a variable, name or `new` expression, followed by any `->`, `?->`, `::`, `[]`
or call. A space before the parentheses isn't removed when something is dereferenced after them, as `clone ($a)->b`
clones `$a->b` before PHP 8.5.

**Good:**

```php
$copy = clone($original);
```

**Bad:**

```php
$copy = clone $original;
$copy = clone ($original);
```

---

## Mossy2100.WhiteSpace.OperatorSpacing

Requires a single space around operators, including the pipe operator `|>`. This replaces
`Squiz.WhiteSpace.OperatorSpacing`, with the same settings: a single space before `=` and `=>`, and a line break allowed
either side.

**Why:** [PER Coding Style 3.1, Section 6.2](https://www.php-fig.org/per/coding-style/#62-binary-operators) says `|>` (PHP 8.5) is a binary operator, so it needs a space either side.
The parent doesn't know it. PHP_CodeSniffer 4.0 doesn't define `T_PIPE` itself, but passes PHP's own token through on
PHP 8.5, so this sniff registers it when it's defined. The pipe isn't checked when PHPCS runs on PHP 8.4.

**Auto-fixable:** yes, as the parent.

**Good:**

```php
$slug = $title |> trim(...) |> strtolower(...);
```

**Bad:**

```php
$slug = $title|>trim(...)|>strtolower(...);
```

---

## Mossy2100.WhiteSpace.ScopeClosingBrace

Checks that a scope's closing brace is on a line by itself, and indented to match, except in an empty body written as
`{}` on one line, e.g. `class Foo {}` or `function () {}`. This replaces `Squiz.WhiteSpace.ScopeClosingBrace`.

**Why:** `Squiz.WhiteSpace.ScopeClosingBrace` rejects an empty body written as `{}` on one line, which
[PER Coding Style 3.1, Sections 4.4 and 7](https://www.php-fig.org/per/coding-style/#7-closures) requires for a closure and recommends for a class or function. This sniff skips
that case, and leaves everything else to the parent. See [Mossy2100.Formatting.EmptyBody](#mossy2100formattingemptybody).

**Auto-fixable:** yes, as the parent.

---

## Mossy2100.WhiteSpace.ScopeIndent

Checks that code is indented correctly, including property hooks (PHP 8.4+), and an anonymous class with attributes.
This is a fork of `Generic.WhiteSpace.ScopeIndent`.

**Why:**

- PHP_CodeSniffer's tokenizer doesn't treat property hook braces as scopes, so `Generic.WhiteSpace.ScopeIndent` doesn't
  check the indentation inside a hook.
- [PER Coding Style 3.1, Section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes) indents an anonymous class with attributes once from the line with `new`, and its body
  from `class`. The parent expects `class` at the statement's indent. See [Mossy2100.Attributes.AttributePlacement](#mossy2100attributesattributeplacement).

**Auto-fixable:** yes.

**Good:**

```php
class User
{
    public string $name {
        get {
            return $this->name;
        }
        set {
            $this->name = trim($value);
        }
    }
}
```

**Bad:**

```php
class User
{
    public string $name {
    get {
        return $this->name;
    }
    }
}
```

**Configuration:**

| Property | Type | Default | Description                             |
| -------- | ---- | ------- | --------------------------------------- |
| `indent` | int  | 4       | Number of spaces per indentation level. |

```xml
<rule ref="Mossy2100.WhiteSpace.ScopeIndent">
    <properties>
        <property name="indent" value="2"/>
    </properties>
</rule>
```
