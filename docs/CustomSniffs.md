# Mossy2100 Coding Standard - Custom Sniffs

This document describes the custom PHP_CodeSniffer sniffs the [Mossy2100 Coding Standard](../README.md) adds as house style, on top of
PERCS31. The custom sniffs for PER Coding Style itself are in [PERCS31](PERCS31.md#custom-sniffs).

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

## Mossy2100.Classes.EmptyLinesAroundClassBraces

Disallows blank lines after a class's opening brace and before its closing brace. This replaces
`SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces`.

**Why:** `SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces` rejects an empty body written as `{}` on one line,
which [PER Coding Style 3.0, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) recommends. This sniff skips that case, and leaves everything else to the
parent. See [PERCS31.Formatting.EmptyBody](PERCS31.md#percs31formattingemptybody).

**Auto-fixable:** yes, as the parent.

---

## Mossy2100.Classes.RequireSingleLineMethodSignature

Requires a method signature that fits within the line length to be written on one line, skipping one with a hook on a
promoted constructor parameter. This replaces `SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature`.

**Why:** the parent takes the `;` in a hook on a promoted constructor parameter, e.g.
`public string $name { set => trim($value); }`, as the end of the signature, so its fix joins only part of it, and
`phpcbf` can't finish. [PER Coding Style 3.1, Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) allows a single short hook there, so this sniff skips a
function with one, and leaves everything else to the parent. See [`PERCS31.Classes.PromotedPropertyHooks`](PERCS31.md#percs31classespromotedpropertyhooks).

**Auto-fixable:** yes, as the parent.

---

## Mossy2100.ControlStructures.LanguageConstructWithParentheses

Disallows parentheses around the argument of a language construct, such as `echo('x')`, except `exit` and `die`. This
replaces `SlevomatCodingStandard.ControlStructures.LanguageConstructWithParentheses`.

**Why:** the Slevomat sniff reports `die()` and fixes it to `die`. [PER Coding Style 3.0, Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says `exit()` and
`die()` SHOULD have parentheses, which `Universal.PHP.RequireExitDieParentheses` checks, so the two sniffs would undo
each other's fixes. This sniff skips `exit` and `die`, and leaves everything else to the parent.

**Auto-fixable:** yes, as the parent.

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

## Mossy2100.Functions.RequireSingleLineCall

Requires a function, method or constructor call that fits within the line length to be written on one line. This
replaces `SlevomatCodingStandard.Functions.RequireSingleLineCall`, and skips a call with an anonymous class argument.

**Why:** the parent's `ignoreWithComplexParameter` option skips a call with a closure, arrow function or array argument,
but not one with an anonymous class. So it reports `consume(new class {` ... `});`, and its fix puts the whole class
body on one line, which then crashes `SlevomatCodingStandard.Classes.PropertySpacing`.

**Auto-fixable:** yes, as the parent.

**Configuration:**

| Property                     | Type | Default | Description                                                                       |
| ---------------------------- | ---- | ------- | --------------------------------------------------------------------------------- |
| `maxLineLength`              | int  | 120     | The longest line a call may be joined onto.                                       |
| `ignoreWithComplexParameter` | bool | true    | Skip a call with a closure, arrow function, array, anonymous class or inner call. |

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

## Mossy2100.NamingConventions.ValidVariableName

Warns about a variable or parameter whose name starts with an underscore, inside a class, enum, trait or interface.

**Why:** `Squiz.NamingConventions.ValidVariableName` reports a leading underscore as an error outside a class. Inside
one, it strips the underscore before checking camelCase, because the Squiz convention prefixes private members with one.

**Auto-fixable:** no. Renaming a variable is a refactor, not a fix.

This sniff fills only that gap. It doesn't report:

- Superglobals, such as `$_GET` and `$_SERVER`.
- A name that Squiz already reports, because the rest of it isn't camelCase either, such as `$_user_name`.
- Properties, including promoted constructor parameters, and static property access (`self::$_count`).
  `PERCS31.Classes.PropertyDeclaration` checks those where they're declared.

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

## Mossy2100.TypeHints.DeclareStrictTypes

Requires `declare(strict_types=1)` at the top of each file. This replaces
`SlevomatCodingStandard.TypeHints.DeclareStrictTypes`, and allows the declaration between its own PHP tags on the first
line of a file with markup.

**Why:** [PER Coding Style 3.1, Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements) says that in a file containing markup outside PHP tags, the declaration MUST be
on the first line, e.g. `<?php declare(strict_types=1) ?>`. The parent requires a blank line before and after it. This
sniff accepts that form, with or without the `;`, if it sets `strict_types` to 1, and leaves everything else to the
parent. See [`PERCS31.Files.FileHeader`](PERCS31.md#percs31filesfileheader).

**Auto-fixable:** yes, as the parent.

---

## Mossy2100.TypeHints.ParameterTypeHint

Requires a type hint on each parameter, skipping a function with a hook on a promoted constructor parameter. This
replaces `SlevomatCodingStandard.TypeHints.ParameterTypeHint`.

**Why:** the parent treats every variable between a function's parentheses as a parameter, so `$value` or `$this` in a
hook on a promoted constructor parameter is reported as a parameter with no type hint. The other parameters of that
function aren't checked either. [PER Coding Style 3.1, Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) allows a single short hook there, so this sniff skips
a function with one, and leaves everything else to the parent. See [`PERCS31.Classes.PromotedPropertyHooks`](PERCS31.md#percs31classespromotedpropertyhooks).

**Auto-fixable:** yes, as the parent.

---

## Mossy2100.TypeHints.ParameterTypeHintSpacing

Checks the spacing around parameter type hints, skipping a function with a hook on a promoted constructor parameter.
This replaces `SlevomatCodingStandard.TypeHints.ParameterTypeHintSpacing`.

**Why:** the parent treats every variable between a function's parentheses as a parameter, so `$value` or `$this` in a
hook on a promoted constructor parameter is reported as a parameter with bad spacing. The other parameters of that
function aren't checked either. [PER Coding Style 3.1, Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) allows a single short hook there, so this sniff skips
a function with one, and leaves everything else to the parent. See [`PERCS31.Classes.PromotedPropertyHooks`](PERCS31.md#percs31classespromotedpropertyhooks).

**Auto-fixable:** yes, as the parent.
