# OceanMoon Coding Standard - Custom Sniffs

This document describes the custom PHP_CodeSniffer sniffs created for the [OceanMoon Coding Standard](../README.md).

---
## OceanMoon.Arrays.ArrayDeclaration

Enforces consistent array formatting based on array type. The sniff differentiates between _lists_ (no array keys
appearing in the code), and _associative arrays_ (at least one key appearing in the code). Technically, a list in PHP is
any array with sequential integer keys starting from 0, but since we don't want to remove keys if they exist in the
code, we treat any array with keys as an associative array and format it as such, even if those keys are 0, 1, 2, etc.

The format is chosen automatically based on array type and content, loosely mirroring how
`OceanMoon\Core\Stringify::stringifyArray()` pretty-prints a value at runtime:

- List arrays where every element is **simple** — a scalar literal, `null`/`true`/`false`, a bare variable, or a bare
  constant — use a compact single-line format if it fits within the line length, or a grid format with uniform padding
  if it doesn't.
- List arrays containing at least one **complex** element — a nested array, a function/method call, a `new` expression,
  arithmetic, enum case access, property/static access, or any other expression — always use one element per line, _even
  if the whole array would otherwise fit on one line_. A grid or compact layout only reads unambiguously when every
  element is a plain literal, variable, or constant; anything more involved needs its own line.
- Associative arrays always use one key-value pair per line, arrows aligned.

Array indentation defaults to 4 spaces per nesting level; this is configurable. The sniff uses `mb_strlen()` for proper
Unicode character support when aligning arrows and grid padding. Values in associative arrays must start on the same
line as the double arrow.

**Single-line lists**: Lists where every element is simple (a scalar literal, `null`/`true`/`false`, a bare variable, or
a bare constant) use a compact format with no trailing comma, if they fit within the line length.

```php
// Good
$colors = ['red', 'green', 'blue'];
$values = [1, 'two', 3.0, null, true, $x, MY_CONST];

// Bad
$colors = [
    'red',
    'green',
    'blue',
];
```

**Grid format**: Simple-element lists too long for a single line are arranged in a grid with uniform padding. A trailing
comma is included.

```php
// Good
$colors = [
    'red',     'green',   'blue',    'cyan',    'magenta', 'yellow',
    'black',   'white',   'orange',  'purple',  'brown',   'pink',
    'grey',    'navy',    'teal',
];
```

**One per line**: Lists containing at least one complex element — function/method calls, `new` expressions, arithmetic,
enum case access, property/static access, or nested arrays — always use one element per line with a trailing comma,
regardless of whether the array would fit on one line.

```php
// Good
$results = [
    strtoupper('red'),
    strtolower('GREEN'),
    trim('  blue  '),
];
$points = [
    [1, 2],
    [3, 4],
];

// Bad — forces reformatting even though it fits on one line
$results = [strtoupper('red'), strtolower('GREEN'), trim('  blue  ')];
```

**Associative arrays**: One key-value pair per line, arrows aligned, trailing comma required.

```php
// Good
$user = [
    'name'  => 'John',
    'email' => 'john@example.com',
    'age'   => 30,
];

// Bad
$user = ['name' => 'John', 'email' => 'john@example.com', 'age' => 30];
```

**Configuration:**

| Property        | Type | Default | Description                                                         |
| --------------- | ---- | ------- | ------------------------------------------------------------------- |
| `maxLineLength` | int  | 120     | Maximum line length before wrapping to grid or one-per-line format. |
| `indent`        | int  | 4       | Number of spaces to indent array elements.                          |

```xml
<rule ref="OceanMoon.Arrays.ArrayDeclaration">
    <properties>
        <property name="maxLineLength" value="100"/>
        <property name="indent" value="2"/>
    </properties>
</rule>
```

---

## OceanMoon.Classes.ClassInstantiationNoBrackets

Removes unnecessary parentheses around `new` expressions when accessing members (PHP 8.4+).

PHP 8.4 introduced the ability to access properties and methods on newly instantiated objects without wrapping the
instantiation in parentheses. This sniff enforces that modern syntax.

**Good:**

```php
new DateTime()->format('Y-m-d');
new Foo()->method();
new Bar()->property;
```

**Bad:**

```php
(new DateTime())->format('Y-m-d');  // Unnecessary parentheses
(new Foo())->method();              // Unnecessary parentheses
(new Bar())->property;              // Unnecessary parentheses
```

---
## OceanMoon.Classes.PropertyDeclaration

Verifies that properties are declared correctly. This is a replacement for `PSR2.Classes.PropertyDeclaration` that
properly handles PHP 8.4 property hooks.

**Improvements over the PSR2 version:**

- Variables inside property hook bodies (e.g. `$this`, `$value`, local variables) are correctly ignored as non-property
  declarations.
- Properties with hooks end with a closing brace `}`, not a semicolon `;`, and are handled correctly.
- Supports PHP 8.4 asymmetric visibility (`public private(set)`), enforcing that read-visibility comes before
  write-visibility.
- Enforces correct ordering of modifiers: `abstract`/`final` before visibility, `static`/`readonly` after visibility.

---
## OceanMoon.WhiteSpace.ScopeIndent

Checks that control structures and code are indented correctly. This is a fork of `Generic.WhiteSpace.ScopeIndent` with
PHP 8.4 property hook support.

PHP_CodeSniffer's tokenizer does not recognize property hook braces as scope openers/closers, so the built-in
`ScopeIndent` sniff cannot track their indentation. This sniff builds a map of property hook scopes (both the hook
container `{ get ... set ... }` and individual hook bodies `get { ... }`) and tracks them alongside the standard scope
stack.

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

**Configuration:**

| Property | Type | Default | Description                             |
| -------- | ---- | ------- | --------------------------------------- |
| `indent` | int  | 4       | Number of spaces per indentation level. |

```xml
<rule ref="OceanMoon.WhiteSpace.ScopeIndent">
    <properties>
        <property name="indent" value="2"/>
    </properties>
</rule>
```

---
## OceanMoon.PHP.DisallowMultiConstantDefinition

Disallows defining multiple constants in a single statement (e.g. `const A = 1, B = 2;`). This is a fork of
`SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition`.

The original sniff scans for commas between `const` and the closing `;`, skipping over commas inside a short array
(`[...]`) so it doesn't mistake them for constant separators — but it doesn't do the same for a parenthesized
argument list. That makes it misfire on a single, valid constant whose value is a multi-argument `new` expression or
function call, e.g. `const I = new Complex(0, 1);` is wrongly flagged, because the comma inside `Complex(0, 1)` is
miscounted as a constant separator. This fork also skips over parenthesized expressions, fixing the false positive
while still catching genuine multi-constant statements.

Applies equally to class constants and namespace/file-scope constants — `T_CONST` isn't class-specific.

**Good:**

```php
public const int A = 1;
public const int B = 2;
public const array scores = [42, 33, 12, 99]; // not flagged - comma belongs to the array literal
public const Complex I = new Complex(0, 1);  // not flagged — comma belongs to the constructor call
```

**Bad:**

```php
public const int A = 1, B = 2; // comma detected as indicating multi-constant definition
```
