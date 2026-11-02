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

**Auto-fixable:** yes, unless a comment is between the attribute and its target.

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
```

---

## Mossy2100.Classes.ClassDeclaration

Checks the declaration of a class, interface, trait or enum, and the placement of its braces. This replaces
`PSR2.Classes.ClassDeclaration`.

**Why:** `PSR2.Classes.ClassDeclaration` rejects an empty body written as `{}` on one line, which
[PER Coding Style 3.0, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) recommends. This sniff skips that case, and leaves everything else to the
parent. For an empty body, the checks on `extends` and `implements` still run. See [Mossy2100.Formatting.EmptyBody](#mossy2100formattingemptybody).

**Auto-fixable:** yes, as the parent.

---

## Mossy2100.Classes.EmptyLinesAroundClassBraces

Disallows blank lines after a class's opening brace and before its closing brace. This replaces
`SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces`.

**Why:** `SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces` rejects an empty body written as `{}` on one line,
which [PER Coding Style 3.0, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) recommends. This sniff skips that case, and leaves everything else to the
parent. See [Mossy2100.Formatting.EmptyBody](#mossy2100formattingemptybody).

**Auto-fixable:** yes, as the parent.

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

## Mossy2100.Classes.PropertyDeclaration

Verifies that properties are declared correctly. This replaces `PSR2.Classes.PropertyDeclaration`.

**Why:** PSR-12's `PSR2.Classes.PropertyDeclaration` misreads variables in PHP 8.4 property hook bodies, such as
`$value` and `$this`, as property declarations, and reports false errors on them. It also only warns about a leading
underscore, and doesn't check promoted constructor properties, where [PER Coding Style 3.0, Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says MUST NOT.

**Auto-fixable:** no.

It reports:

- A property name with a leading underscore (`Underscore`), for any visibility, including promoted constructor
  parameters. PER Coding Style 3.0 disallows it too.
- The `var` keyword (`VarUsed`).
- A property with no visibility (`ScopeMissing`).

`SlevomatCodingStandard.Classes.PropertyDeclaration` checks modifier order and the spacing after the type, and
`SlevomatCodingStandard.Classes.DisallowMultiPropertyDefinition` checks for several properties in one statement.

**Good:**

```php
class User
{
    private string $secret = '';
    public static int $total = 0;

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

    // Leading underscore on a promoted parameter.
    public function __construct(private int $_id) {}
}
```

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

Warns about an empty class, interface, trait, enum, function or method body that isn't written as `{}` on the same line
as the declaration, after a single space.

**Why:** [PER Coding Style 3.0, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says an empty body SHOULD be abbreviated that way, including a
constructor that only promotes properties. No inherited sniff checks it. As it's a SHOULD, this is a warning, not an
error. A body containing a comment isn't empty, and closures aren't covered by PER 3.0.

**Auto-fixable:** yes, unless a comment is between the declaration and the opening brace.

**Good:**

```php
final class NotFoundException extends Exception {}

final class Point
{
    public function __construct(public readonly int $x, public readonly int $y) {}

    public function reset(): void {}
}
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
```

---

## Mossy2100.Functions.MultiLineFunctionDeclaration

Checks the layout of function declarations, including the placement of the opening brace. This replaces
`Squiz.Functions.MultiLineFunctionDeclaration`.

**Why:** `Squiz.Functions.MultiLineFunctionDeclaration` rejects an empty body written as `{}` on one line, which
[PER Coding Style 3.0, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) recommends. This sniff skips that case, and leaves everything else to the
parent. For an empty body after a single-line signature, the parent's `CloseBracketNewLine` check is skipped too, as it
can't be run on its own. See [Mossy2100.Formatting.EmptyBody](#mossy2100formattingemptybody).

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

## Mossy2100.WhiteSpace.ScopeClosingBrace

Checks that a scope's closing brace is on a line by itself, and indented to match. This replaces
`Squiz.WhiteSpace.ScopeClosingBrace`.

**Why:** `Squiz.WhiteSpace.ScopeClosingBrace` rejects an empty body written as `{}` on one line, which
[PER Coding Style 3.0, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) recommends. This sniff skips that case, and leaves everything else to the
parent. See [Mossy2100.Formatting.EmptyBody](#mossy2100formattingemptybody).

**Auto-fixable:** yes, as the parent.

---

## Mossy2100.WhiteSpace.ScopeIndent

Checks that code is indented correctly, including property hooks (PHP 8.4+). This is a fork of
`Generic.WhiteSpace.ScopeIndent`.

**Why:** PHP_CodeSniffer's tokenizer doesn't treat property hook braces as scopes, so `Generic.WhiteSpace.ScopeIndent`
doesn't check the indentation inside a hook.

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
