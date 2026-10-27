# Mossy2100 Coding Standard - Custom Sniffs

This document describes the custom PHP_CodeSniffer sniffs created for the [Mossy2100 Coding Standard](../README.md).

---

## Mossy2100.Arrays.ArrayDeclaration

Requires a multi-line array to be written on one line if it fits. The rest of the array layout is enforced by stock
sniffs (see [Inherited Sniffs](InheritedSniffs.md)), but none of them collapses a multi-line array.

- **Fits** means the whole resulting line is within `maxLineLength`: the text before the opening bracket, the collapsed
  array, and the text after the closing bracket on its line. A fix never creates a line-length error.
- **Outermost first.** If an enclosing array fits, only that one is reported, as fixing it collapses the nested ones.
- **Clean output.** The fix uses single spaces, no space inside brackets or around `->`, `?->` and `::`, and no
  trailing commas.
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

To use a different limit, set every line-length property, as described in [Changing the Line Length](../README.md#changing-the-line-length).

---

## Mossy2100.Classes.PropertyDeclaration

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

## Mossy2100.Classes.DisallowPromotedPropertyHooks

Disallows property hooks on promoted constructor parameters (PHP 8.4+). Hook bodies inside a constructor signature are
hard to read, so declare the property in the class body instead. Promoted parameters without hooks are still allowed.

This is stricter than [PER Coding Style 3.0](https://www.php-fig.org/per/coding-style/#49-property-hooks), which allows a single short hook on one line.

Inherited sniffs also misread these hooks. Two Slevomat sniffs treat a variable in the hook body, such as `$value` or
`$this`, as another constructor parameter, and report errors that `phpcbf` can't fix:

- `SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint`
- `SlevomatCodingStandard.TypeHints.ParameterTypeHintSpacing.NoSpaceBetweenTypeHintAndParameter`

A multi-line hook also gets `Squiz.Functions.MultiLineFunctionDeclaration.Indent`.

Not auto-fixable, because moving a property and its hooks into the class body is a bigger rewrite than a fixer should
risk.

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
    public function __construct(public string $name { set => trim($value); }, public readonly int $age)
    {
    }
}
```

---

## Mossy2100.ControlStructures.MatchArrowSpacing

Requires exactly one space before and after the `=>` in a `match` arm, so arms aren't aligned, since aligned columns
make noisy diffs. `Squiz.WhiteSpace.OperatorSpacing` covers `=>` in arrays and `foreach` loops, but not `match` arms.

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
`SlevomatCodingStandard.ControlStructures.RequireSingleLineCondition` does the same for `if`, `elseif`, `while` and
`do-while`. A multi-line header is often left over after the array inside it is collapsed.

**Fits** and **skipped** mean the same as for [Mossy2100.Arrays.ArrayDeclaration](#mossy2100arraysarraydeclaration):
the whole resulting line, up to and including ` {`, must be within `maxLineLength`, and a header containing a comment,
heredoc, multi-line string or brace body is left alone.

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

To use a different limit, set every line-length property, as described in [Changing the Line Length](../README.md#changing-the-line-length).

---

## Mossy2100.WhiteSpace.ScopeIndent

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
<rule ref="Mossy2100.WhiteSpace.ScopeIndent">
    <properties>
        <property name="indent" value="2"/>
    </properties>
</rule>
```

---

## Mossy2100.PHP.DisallowMultiConstantDefinition

Disallows defining multiple constants in a single statement (e.g. `const A = 1, B = 2;`). This is a fork of
`SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition`.

The original sniff scans for commas between `const` and the closing `;`, skipping over commas inside a short array
(`[...]`) so it doesn't mistake them for constant separators. It doesn't do the same for a parenthesized argument list.
That makes it misfire on a single, valid constant whose value has a comma inside parentheses. For example, the
file-scope constant `const I = new Complex(0, 1);` is wrongly flagged, because the comma inside `Complex(0, 1)` is
miscounted as a constant separator. The same happens with a long-syntax array (`array(1, 2)`), or a closure with more
than one parameter (PHP 8.5+). This fork also skips over parenthesized expressions, fixing the false positive while
still catching genuine multi-constant statements.

Applies equally to class constants and namespace/file-scope constants, since `T_CONST` isn't class-specific.

**Good:**

```php
class Example
{
    public const int A = 1;
    public const int B = 2;
    public const array SCORES = [42, 33, 12, 99]; // Not flagged: the commas belong to the array literal.
    public const array SIZES = array(1, 2);       // Not flagged: the comma is inside the parentheses.
}

const I = new Complex(0, 1); // Not flagged: the comma belongs to the constructor call (PHP 8.1+).
```

**Bad:**

```php
public const int A = 1, B = 2; // comma detected as indicating multi-constant definition
```
