# Jumbo Coding Standard

A PHP_CodeSniffer standard for PHP 8.4 and later: [PERCS31](PERCS31.md), for PER Coding Style 3.1, plus extra rules reflecting my
preferences and common practices. These add consistent naming, modern PHP, and stricter choices where PER allows
more than one layout.

Everything PERCS31 checks, Jumbo checks too, apart from the changes listed under [PERCS31](#percs31). This page documents
what Jumbo adds. For the PER rules, see [PERCS31](PERCS31.md).

**Contents:**

- [Usage](#usage)
- [PERCS31](#percs31)
- [Inherited Sniffs](#inherited-sniffs)
- [Custom Sniffs](#custom-sniffs)
- [Not Checked](#not-checked)

---

## Usage

Jumbo is installed with the `mossy2100/coding-standard` package, and registered with PHP_CodeSniffer the same way. See
[Installation](../README.md#installation). Then reference it in your `phpcs.xml`:

```xml
<?xml version="1.0"?>
<ruleset name="My Project">
    <file>src</file>
    <file>tests</file>

    <rule ref="Jumbo"/>
</ruleset>
```

The line length limit is 120. To change it, see [Line Length](LineLength.md).

---

## PERCS31

The complete [PERCS31](PERCS31.md) standard is included, which is built on PSR-12. These are Jumbo's changes to it:

- `PERCS31.Attributes.AttributePlacement` requires a closure's attribute inline, after a single space, outside a call's
  argument list too, e.g. in an assignment or an array (`checkClosureAttributesOutsideCalls` set to `true`).
- `PERCS31.Classes.PropertyDeclaration` checks the hook order in every hook list, including ones with bodies
  (`orderHooksWithBodies` set to `true`).
- `PERCS31.Classes.PropertyHookLayout` reports a space in a by-reference hook, `& get` (`checkReferenceSpacing` set to
  `true`), and requires exactly one space before a hook's own `{`, as in `get {` (`checkHookBraceSpacing` set to
  `true`).
- `PERCS31.WhiteSpace.ArrowSpacing` checks the spacing around `=>` in every array and `yield`, not just a `match` arm
  (`checkArray` and `checkYield` set to `true`). It replaces Squiz `ArrayDeclaration`'s arrow spacing checks.
- `PERCS31.Operators.OperatorPlacement` requires a wrapped line in a list item, such as a call argument, to be indented
  once past the item, not lined up with it (`requireListItemIndent` set to `true`). A wrapped line in grouping
  parentheses must line up with the group's first line, with no extra indent (`requireGroupingLineUp` set to `true`).
- `PERCS31.Formatting.TrailingComma` disallows a comma before a `match` arm's `=>` when its conditions are split over
  lines, which PERCS31 doesn't check (`disallowInSplitMatchConditions` set to `true`).
- `PERCS31.Functions.MultiLineFunctionDeclaration` reports a blank line before a closing parenthesis or between a
  closure's `use` items, and more than one between parameters (`checkOptionalBlankLines` set to `true`).
- `PSR2.Methods.FunctionCallSignature.EmptyLine` reports a blank line in a call's arguments, which PERCS31 excludes.
- `SlevomatCodingStandard.TypeHints.DNFTypeHintFormat` reports `T|null`, which should be `?T`, as an error, not a
  warning.
- `Squiz.Arrays.ArrayDeclaration` also runs the checks PERCS31 leaves out. See [Squiz](#squiz).
- `PERCS31.Operators.OperatorSpacing` also reports more than one space around an operator, with `ignoreNewlines` on and
  `ignoreSpacingBeforeAssignments` off. See [Squiz](#squiz).
- `Squiz.Functions.FunctionDeclarationArgumentSpacing` requires exactly one space around a default value's `=`, using
  the `SpaceBeforeEquals` and `SpaceAfterEquals` codes PERCS31 excludes (`PERCS31.Operators.OperatorSpacing` has
  `checkDefaultValues` set to `false`).
- `PSR12.Keywords.ShortFormTypeKeywords` is excluded, as `SlevomatCodingStandard.PHP.TypeCast` reports the same casts.
  See [Slevomat](#slevomat).
- `PSR2.Files.ClosingTag` is excluded, as `Zend.Files.ClosingTag` reports the same closing tags, and more. See
  [Zend](#zend).
- `PERCS31.NamingConventions.AcronymCasing`'s warning has a severity of 5, not 4, so it's shown by default.

---

## Inherited Sniffs

Sniffs from PHP_CodeSniffer and Slevomat that Jumbo adds on top of PERCS31. They're extra rules, not PER rules.

### Generic

- **Generic.Formatting.SpaceAfterNot**: Disallows whitespace after `!`, including a line break (`spacing` set to 0).
- **Generic.CodeAnalysis.AssignmentInCondition**: Warns about assignments in conditions. See [Yoda-style Comparisons](../README.md#yoda-style-comparisons).
- **PERCS31.Files.LineLength**: PERCS31's line length check, with `lineLimit` set from the ruleset's `lineLength`
  entity. See [Line Length](LineLength.md).

### Squiz

- **Squiz.Arrays.ArrayDeclaration**: The checks PERCS31 leaves out: one space after a comma, no space inside an empty
  array `[]`, and no mix of keyed and unkeyed values. Which line a comma goes on is left to
  [`Jumbo.Arrays.ElementComma`](#jumboarrayselementcomma). The spacing around `=>` is left to
  [`PERCS31.WhiteSpace.ArrowSpacing`](PERCS31.md#percs31whitespacearrowspacing), as Squiz only checks it in a single-line array. Its alignment checks and
  `SingleLineNotAllowed` stay excluded, so keys, values and arrows are not aligned, and a single-line array with several
  values is allowed. `Jumbo.Arrays.ArrayDeclaration` requires single-line where it fits.
- **Squiz.NamingConventions.ValidVariableName**: Enforces `$camelCase` for variables, parameters, and properties. Its
  checks for an underscore on properties are excluded, as one requires it on private properties. See
  [Variable and Property Names](VariableAndPropertyNames.md).
- **Squiz.Strings.DoubleQuoteUsage.NotRequired**: Ensures strings use single quotes unless double quotes are necessary.
- **Squiz.WhiteSpace.OperatorSpacing**: Requires exactly one space around operators, including before `=`, so
  assignments are not aligned. Used through [`PERCS31.Operators.OperatorSpacing`](PERCS31.md#percs31operatorsoperatorspacing), which extends it to
  `&&`, `||`, `and`, `or`, `xor`, `.` and `|>`. PERCS31 excludes its codes for more than one space, and Jumbo turns them
  back on. `=>` is covered by [`PERCS31.WhiteSpace.ArrowSpacing`](PERCS31.md#percs31whitespacearrowspacing).
- **Squiz.WhiteSpace.ObjectOperatorSpacing**: Disallows spaces around `->`, `?->` and `::`, e.g. `$a -> b()`. A line
  break before one, in a wrapped method chain, is allowed.

### Slevomat

Reference: [Slevomat coding standard repository and documentation](https://github.com/slevomat/coding-standard)

- **Arrays.ArrayAccess**: Disallows whitespace between array access operators.
- **Arrays.DisallowImplicitArrayCreation**: Disallows implicit array creation.
- **Arrays.DisallowPartiallyKeyed**: Requires arrays to have keys for all or none of the values.
- **Arrays.SingleLineArrayWhitespace**: Checks whitespace in single-line array declarations.
- **Attributes.AttributesOrder**: Requires attributes to be ordered alphabetically.
- **Classes.ClassMemberSpacing**: Enforces spacing between class members.
- **Classes.DisallowStringExpressionPropertyFetch**: Disallows string expressions for property access.
- **Classes.MethodSpacing**: Enforces spacing between methods.
- **Classes.ModernClassNameReference**: Requires `::class` instead of `get_class()`, `get_parent_class()` and
  `get_called_class()`.
- **Classes.RequireMultiLineMethodSignature**: Requires multi-line format for long method signatures.
- **Classes.RequireSelfReference**: Requires `self::` instead of `ClassName::` for self-references.
- **Classes.RequireSingleLineMethodSignature**: Requires single-line format for short method signatures. Used through
  [`Jumbo.Classes.RequireSingleLineMethodSignature`](#jumboclassesrequiresinglelinemethodsignature), which skips a method with a hook on a promoted parameter.
- **Classes.TraitUseSpacing**: Disallows a blank line between trait `use` statements. Its checks before the first and
  after the last are excluded, as `PSR12.Traits.UseDeclaration`, in PERCS31, covers them.
- **Commenting.AnnotationName**: Enforces correct annotation names.
- **Commenting.DeprecatedAnnotationDeclaration**: Enforces the `@deprecated` annotation format.
- **Commenting.EmptyComment**: Disallows empty comments.
- **Commenting.RequireOneLinePropertyDocComment**: Requires one-line format for short property doc comments.
- **Commenting.UselessInheritDocComment**: Disallows useless `@inheritDoc` comments.
- **ControlStructures.DisallowContinueWithoutIntegerOperandInSwitch**: Disallows `continue` without an operand in
  `switch`.
- **ControlStructures.RequireMultiLineCondition**: Requires multi-line format for long conditions with boolean
  operators.
- **ControlStructures.RequireMultiLineTernaryOperator**: Requires multi-line format for long ternary operators.
- **ControlStructures.RequireNullCoalesceEqualOperator**: Requires `??=` when possible.
- **ControlStructures.RequireNullCoalesceOperator**: Requires `??` when possible.
- **ControlStructures.RequireNullSafeObjectOperator**: Requires `?->` when possible.
- **ControlStructures.RequireSingleLineCondition**: Requires single-line format for short conditions.
- **ControlStructures.RequireShortTernaryOperator**: Requires the short ternary `?:` when possible.
- **ControlStructures.RequireTernaryOperator**: Requires ternary operator when possible.
- **ControlStructures.UselessIfConditionWithReturn**: Disallows useless if conditions returning true or false.
- **Exceptions.DeadCatch**: Disallows unreachable catch blocks, e.g. a catch after `catch (Throwable)`.
- **Exceptions.RequireNonCapturingCatch**: Requires catch without variable when exception is not used.
- **Functions.FunctionLength**: Enforces maximum function length of 100 lines.
- **Functions.RequireArrowFunction**: Requires arrow functions for simple closures.
- **Functions.RequireMultiLineCall**: Requires multi-line format for long function calls.
- **Functions.StaticClosure**: Requires the `static` keyword for closures that don't use `$this`.
- **Functions.StrictCall**: Requires the strict argument in `in_array()`, `array_search()`, `array_keys()` and
  `base64_decode()`.
- **Functions.UselessParameterDefaultValue**: Disallows useless parameter default values.
- **Namespaces.AlphabeticallySortedUses**: Requires use statements to be alphabetically sorted.
- **Namespaces.DisallowGroupUse**: Disallows group use declarations.
- **Namespaces.MultipleUsesPerLine**: Disallows multiple use statements on one line.
- **Namespaces.NamespaceDeclaration**: Enforces proper namespace declaration format.
- **Namespaces.ReferenceUsedNamesOnly**: Requires use statements for all referenced names.
- **Namespaces.RequireOneNamespaceInFile**: Requires exactly one namespace per file.
- **Namespaces.UseFromSameNamespace**: Disallows use statements from the same namespace.
- **Namespaces.UselessAlias**: Disallows useless use statement aliases.
- **Operators.NegationOperatorSpacing**: Enforces the spacing after a unary `-`, including a line break. Its
  `spacesCount` defaults to 0, so no whitespace is allowed.
- **Operators.RequireCombinedAssignmentOperator**: Requires combined assignment operators (`+=`, `-=`, etc.).
- **Operators.RequireOnlyStandaloneIncrementAndDecrementOperators**: Requires `++` and `--` to be used standalone.
- **Operators.SpreadOperatorSpacing**: Enforces spacing around spread operator.
- **PHP.DisallowDirectMagicInvokeCall**: Disallows direct `__invoke()` calls.
- **PHP.ReferenceSpacing**: Enforces spacing around reference operator.
- **PHP.ShortList**: Requires short list syntax `[]`.
- **PHP.TypeCast**: Requires `(int)`, `(bool)` and `(float)` instead of `(integer)`, `(boolean)`, `(double)` or
  `(real)`, and forbids `(binary)` and `(unset)`. It replaces PERCS31's `PSR12.Keywords.ShortFormTypeKeywords`, which
  only checks `(int)` and `(bool)`, so a long-form cast is reported once.
- **PHP.UselessSemicolon**: Disallows useless semicolons.
- **Strings.DisallowVariableParsing**: Disallows variable parsing in strings.
- **TypeHints.ClassConstantTypeHint**: Requires class constant type hints.
- **TypeHints.DeclareStrictTypes**: Requires `declare(strict_types=1)`. Used through
  [`Jumbo.TypeHints.DeclareStrictTypes`](#jumbotypehintsdeclarestricttypes), which allows `<?php declare(strict_types=1) ?>` on the first line of a file with
  markup.
- **TypeHints.LongTypeHints**: Requires long type hints (`int` instead of `integer`).
- **TypeHints.NullableTypeForNullDefaultValue**: Requires nullable type for parameters with null default.
- **TypeHints.ParameterTypeHint**: Requires parameter type hints. Used through [`Jumbo.TypeHints.ParameterTypeHint`](#jumbotypehintsparametertypehint),
  which skips a function with a hook on a promoted parameter.
- **TypeHints.ParameterTypeHintSpacing**: Enforces spacing around parameter type hints. Used through
  [`Jumbo.TypeHints.ParameterTypeHintSpacing`](#jumbotypehintsparametertypehintspacing), which skips a function with a hook on a promoted parameter.
- **TypeHints.PropertyTypeHint**: Requires property type hints.
- **TypeHints.ReturnTypeHint**: Requires return type hints.
- **TypeHints.ReturnTypeHintSpacing**: Enforces spacing around return type hints.
- **Variables.DisallowVariableVariable**: Disallows variable variables (`$$var`).
- **Variables.DuplicateAssignmentToVariable**: Disallows duplicate assignments to the same variable.
- **Variables.UnusedVariable**: Disallows variables that are assigned but never used.
- **Variables.UselessVariable**: Disallows useless variables.

### Zend

- **Zend.Files.ClosingTag**: Disallows a closing tag at the end of a file, including one that mixes PHP and HTML.
  PERCS31's `PSR2.Files.ClosingTag` only checks PHP-only files. A file that ends in HTML has no closing tag at its end,
  so it passes.

---

## Custom Sniffs

Each one fills a gap in the inherited sniffs, or replaces an inherited sniff that misreads modern PHP. Each section
gives the reason. The custom sniffs for PER Coding Style itself are in [PERCS31](PERCS31.md#custom-sniffs).

| Sniff                                                      | Checks                                                          | Fixable |
| ---------------------------------------------------------- | --------------------------------------------------------------- | ------- |
| `Jumbo.Arrays.ArrayDeclaration`                            | A multi-line array is on one line if it fits                    | Yes     |
| `Jumbo.Arrays.ElementComma`                                | A multi-line array element's comma on the element's last line   | Yes     |
| `Jumbo.Classes.ConstantSpacing`                            | Blank lines after a constant, safe on the last line             | Yes     |
| `Jumbo.Classes.EmptyLinesAroundClassBraces`                | No blank lines inside class braces, allowing `{}`               | Yes     |
| `Jumbo.Classes.EnumCaseSpacing`                            | Blank lines after an enum case, safe on the last line           | Yes     |
| `Jumbo.Classes.PropertySpacing`                            | Blank lines after a property, safe on the last line             | Yes     |
| `Jumbo.Classes.RequireSingleLineMethodSignature`           | A short signature on one line, skipping a promoted hook         | Yes     |
| `Jumbo.ControlStructures.LanguageConstructWithParentheses` | No `echo('x')`, allowing `exit()` and `die()`                   | Yes     |
| `Jumbo.ControlStructures.RequireSingleLineHeader`          | A multi-line loop, `switch` or `match` header fits on a line    | Yes     |
| `Jumbo.ControlStructures.MatchArmWrapIndent`               | One line if it fits, else a wrapped `match` arm indented once   | Yes     |
| `Jumbo.ControlStructures.StatementAfterClosingBrace`       | A control structure's `}` on its own line                       | Yes     |
| `Jumbo.Functions.RequireSingleLineCall`                    | A call that fits is on one line, skipping a class or `match`    | Yes     |
| `Jumbo.Namespaces.UnusedUses`                              | No unused `use` statements, safe with a miscased `use Function` | Yes     |
| `Jumbo.NamingConventions.ValidVariableName`                | No leading underscore on a variable or parameter in a class     | No      |
| `Jumbo.Operators.UnaryOperatorSpacing`                     | No whitespace after a prefix `+`, `~` or `@`                    | Yes     |
| `Jumbo.TypeHints.DeclareStrictTypes`                       | `strict_types=1` required, allowing `<?php declare(...) ?>`     | Yes     |
| `Jumbo.TypeHints.ParameterTypeHint`                        | Parameter type hints, skipping a promoted hook                  | Yes     |
| `Jumbo.TypeHints.ParameterTypeHintSpacing`                 | Parameter type hint spacing, skipping a promoted hook           | Yes     |

### Jumbo.Arrays.ArrayDeclaration

Requires a multi-line array to be written on one line if it fits.

**Why:** no inherited sniff collapses a multi-line array that fits on one line. The stock array sniffs (see
[Inherited Sniffs](#inherited-sniffs)) only check the layout of an array that's already multi-line.

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

### Jumbo.Arrays.ElementComma

Requires the comma after an element of a multi-line array or destructuring list to be on the element's last line, not
the next one.

**Why:** PER Coding Style says nothing about where an array element's comma goes. Squiz `ArrayDeclaration`'s `NoComma`
check covers it, but only for a keyed element, and it expects the comma on the line after a heredoc's closing marker,
as before PHP 7.3. So it reports `EOT,`, and its fix puts the comma on a line of its own. PERCS31 excludes it, and this
sniff replaces it in Jumbo, for keyed and unkeyed elements, in arrays and in destructuring lists such as
`[$a, $b] = $pair;`. A heredoc's or nowdoc's closing marker counts as the element's last line.

**Auto-fixable:** yes, unless a comment is between the element and its comma.

**Good:**

```php
$messages = [
    'greeting' => <<<EOT
        Hello, {$name}.
        EOT,
    'farewell' => 'Goodbye.',
];
```

**Bad:**

```php
$messages = [
    'greeting' => 'Hello.'
    , 'farewell' => 'Goodbye.',
];
```

---

### Jumbo.Classes.EmptyLinesAroundClassBraces

Disallows blank lines after a class's opening brace and before its closing brace. This replaces
`SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces`.

**Why:** `SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces` rejects an empty body written as `{}` on one line,
which [PER Coding Style 3.1, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) recommends. This sniff skips that case, and leaves everything else to the
parent. See [PERCS31.Formatting.EmptyBody](PERCS31.md#percs31formattingemptybody).

**Auto-fixable:** yes, as the parent.

---

### Jumbo.Classes.ConstantSpacing, EnumCaseSpacing and PropertySpacing

Enforce the blank lines after a class constant, enum case or property. These replace
`SlevomatCodingStandard.Classes.ConstantSpacing`, `EnumCaseSpacing` and `PropertySpacing`, and skip a member on the last
line of a file.

**Why:** the parents assume there's a line after every member, so `public int $a = 1; }` on a file's last line is a PHP
fatal error. That ends the whole run, and loses the report for every other file. PERCS31 reports the closing brace
anyway. Each sniff only adds that one check, and can go once Slevomat fixes it.

**Auto-fixable:** yes, as the parents.

---

### Jumbo.Classes.RequireSingleLineMethodSignature

Requires a method signature that fits within the line length to be written on one line, skipping one with a hook on a
promoted constructor parameter. This replaces `SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature`.

**Why:** the parent takes the `;` in a hook on a promoted constructor parameter, e.g.
`public string $name { set => trim($value); }`, as the end of the signature, so its fix joins only part of it, and
`phpcbf` can't finish. [PER Coding Style 3.1, Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) allows a single short hook there, so this sniff skips a
function with one, and leaves everything else to the parent. See [`PERCS31.Classes.PromotedPropertyHooks`](PERCS31.md#percs31classespromotedpropertyhooks).

**Auto-fixable:** yes, as the parent.

---

### Jumbo.ControlStructures.LanguageConstructWithParentheses

Disallows parentheses around the argument of a language construct, such as `echo('x')`, except `exit` and `die`. This
replaces `SlevomatCodingStandard.ControlStructures.LanguageConstructWithParentheses`.

**Why:** the Slevomat sniff reports `die()` and fixes it to `die`. [PER Coding Style 3.1, Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says `exit()` and
`die()` SHOULD have parentheses, which `Universal.PHP.RequireExitDieParentheses` checks, so the two sniffs would undo
each other's fixes. This sniff skips `exit` and `die`, and leaves everything else to the parent.

**Auto-fixable:** yes, as the parent.

---

### Jumbo.ControlStructures.RequireSingleLineHeader

Requires a multi-line `foreach`, `for`, `switch` or `match` header to be written on one line if it fits.

**Why:** `SlevomatCodingStandard.ControlStructures.RequireSingleLineCondition` does this for `if`, `elseif`, `while` and
`do-while`, but not for `foreach`, `for`, `switch` or `match`. A multi-line header is often left over after
`Jumbo.Arrays.ArrayDeclaration` collapses the array inside it.

**Auto-fixable:** yes.

**Fits** and **skipped** mean the same as for [Jumbo.Arrays.ArrayDeclaration](#jumboarraysarraydeclaration): the whole resulting line, up to and
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

### Jumbo.ControlStructures.MatchArmWrapIndent

Requires a `match` arm's conditions, its `=>` and the first line of its value to be on one line, if they fit. Otherwise,
a line starting with a later condition, the `=>`, or the value must be indented one level past the arm's first line. Set
`indent` to change the number of spaces, which is 4 by default.

**Why:** PER Coding Style 3.1 doesn't say how a wrapped arm is laid out, so this is an extra rule, matching how Jumbo
treats calls and arrays that fit, and keeping each arm distinct. `PERCS31.Operators.OperatorPlacement` checks a line
starting with an operator. A comment among the arm's parts is left to a person.

**Auto-fixable:** yes.

**Good:**

```php
$label = match ($code) {
    200, 201 => 'OK',
    $thisIsAVeryLongConditionThatTakesUpMostOfTheLineByItself,
        $andThisIsAnotherOneThatWillNotFitOnTheSameLineAsThatOne => 'Long',
    default => 'Error',
};
```

**Bad:**

```php
$label = match ($code) {
    200,
    201 => 'OK',
    default => 'Error',
};
```

---

### Jumbo.ControlStructures.StatementAfterClosingBrace

Requires a control structure's closing brace to be on its own line, for `if`, `elseif`, `else`, `for`, `foreach`,
`while`, `do`, `switch`, `try`, `catch`, `finally` and block `declare` statements. It allows:

- A comment, such as `} // for $i`.
- What continues the structure: `} else {`, `} elseif (...) {`, `} else if (...) {`, `} catch (...) {`, `} finally {`,
  or the `} while (...);` of a `do`.
- A closing PHP tag, or another closing brace.

**Why:** [PER Coding Style 3.1, Section 4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says any closing brace MUST NOT be followed by a comment or statement on the
same line, but it's in the section on classes, and PERCS31 reads it as classes and functions. So this is an extra rule.
No inherited sniff checks it.

A block `declare` in the file header is skipped, as [`PERCS31.Files.FileHeader`](PERCS31.md#percs31filesfileheader) reports what follows it.

**Auto-fixable:** yes. The statement moves to the next line.

**Good:**

```php
if ($ready) {
    start();
} // $ready

stop();
```

**Bad:**

```php
if ($ready) {
    start();
} stop();
```

---

### Jumbo.Functions.RequireSingleLineCall

Requires a function, method or constructor call that fits within the line length to be written on one line. This
replaces `SlevomatCodingStandard.Functions.RequireSingleLineCall`, and skips a call with an anonymous class or `match`
argument.

**Why:** the parent's `ignoreWithComplexParameter` option skips a call with a closure, arrow function or array argument,
but not one with an anonymous class. So it reports `consume(new class {` ... `});`, and its fix puts the whole class
body on one line, which then crashes Slevomat's property spacing sniff. Its fix collapses a `match` too, which other
sniffs expand again, until `phpcbf` gives up.

**Auto-fixable:** yes, as the parent.

**Configuration:**

| Property                     | Type | Default | Description                                                                       |
| ---------------------------- | ---- | ------- | --------------------------------------------------------------------------------- |
| `maxLineLength`              | int  | 120     | The longest line a call may be joined onto.                                       |
| `ignoreWithComplexParameter` | bool | true    | Skip a call with a closure, arrow function, array, anonymous class or inner call. |

---

### Jumbo.Namespaces.UnusedUses

Disallows unused `use` statements, including in annotations. This replaces
`SlevomatCodingStandard.Namespaces.UnusedUses`.

**Why:** the Slevomat sniff reads the type of a `use` statement with a case-sensitive check, so `use Function
Vendor\helper;` is treated as a class import. It then reports the import as unused, and `phpcbf` deletes it, which can
break the code. This sniff skips the whole file while a `function` or `const` keyword after `use` isn't in lower case.
`Universal.UseStatements.LowercaseFunctionConst` reports that as an error and fixes it, and on `phpcbf`'s next pass the
import is read correctly. Everything else is left to the parent. It can be removed once Slevomat fixes the check.

**Auto-fixable:** yes, as the parent.

---

### Jumbo.NamingConventions.ValidVariableName

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

### Jumbo.Operators.UnaryOperatorSpacing

Disallows whitespace, including a line break, between a prefix unary `+`, `~` or `@` and its operand.

**Why:** there should be no whitespace between a prefix unary operator and its operand. Inherited sniffs check the
others: `SlevomatCodingStandard.Operators.NegationOperatorSpacing` for `-`, `Generic.Formatting.SpaceAfterNot` for `!`,
`Generic.WhiteSpace.IncrementDecrementSpacing` for `++` and `--`, and Slevomat's `ReferenceSpacing` and
`SpreadOperatorSpacing` for `&` and `...`. Nothing checks `+`, `~` or `@`. A cast keeps the single space after it that
[PER Coding Style 3.1, Section 6.1](https://www.php-fig.org/per/coding-style/#61-unary-operators) requires.

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

### Jumbo.TypeHints.DeclareStrictTypes

Requires `declare(strict_types=1)` at the top of each file. This replaces
`SlevomatCodingStandard.TypeHints.DeclareStrictTypes`, and allows the declaration between its own PHP tags on the first
line of a file with markup.

**Why:** [PER Coding Style 3.1, Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements) says that in a file containing markup outside PHP tags, the declaration MUST be
on the first line, e.g. `<?php declare(strict_types=1) ?>`. The parent requires a blank line before and after it. This
sniff accepts that form, with or without the `;`, if it sets `strict_types` to 1. See
[`PERCS31.Files.FileHeader`](PERCS31.md#percs31filesfileheader). Section 3 also forbids spaces inside the parentheses,
so it requires `strict_types=1`, where the parent's default is `strict_types = 1`. And it lists declare statements as
one block with no blank line in it, where the parent always wants a blank line after `strict_types`, so this sniff wants
none if another declare statement follows.

**Auto-fixable:** yes, as the parent. PHP doesn't allow `strict_types` in a block `declare`, so for a file starting with
one, the fix adds `declare(strict_types=1);` on the line above. It's not fixable with markup before the file's first
`<?php`, as it must be the first statement. A shebang line is fine.

---

### Jumbo.TypeHints.ParameterTypeHint

Requires a type hint on each parameter, skipping a function with a hook on a promoted constructor parameter. This
replaces `SlevomatCodingStandard.TypeHints.ParameterTypeHint`.

**Why:** the parent treats every variable between a function's parentheses as a parameter, so `$value` or `$this` in a
hook on a promoted constructor parameter is reported as a parameter with no type hint. The other parameters of that
function aren't checked either. [PER Coding Style 3.1, Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) allows a single short hook there, so this sniff skips
a function with one, and leaves everything else to the parent. See [`PERCS31.Classes.PromotedPropertyHooks`](PERCS31.md#percs31classespromotedpropertyhooks).

**Auto-fixable:** yes, as the parent.

---

### Jumbo.TypeHints.ParameterTypeHintSpacing

Checks the spacing around parameter type hints, skipping a function with a hook on a promoted constructor parameter.
This replaces `SlevomatCodingStandard.TypeHints.ParameterTypeHintSpacing`.

**Why:** the parent treats every variable between a function's parentheses as a parameter, so `$value` or `$this` in a
hook on a promoted constructor parameter is reported as a parameter with bad spacing. The other parameters of that
function aren't checked either. [PER Coding Style 3.1, Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) allows a single short hook there, so this sniff skips
a function with one, and leaves everything else to the parent. See [`PERCS31.Classes.PromotedPropertyHooks`](PERCS31.md#percs31classespromotedpropertyhooks).

**Auto-fixable:** yes, as the parent.

---

## Not Checked

Jumbo adds no gaps of its own. Everything listed under [Not Checked in PERCS31](PERCS31.md#not-checked) applies here too.
