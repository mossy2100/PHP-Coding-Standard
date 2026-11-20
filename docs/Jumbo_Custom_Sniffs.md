# Jumbo Custom Sniffs

The sniffs written for the [Jumbo](Jumbo.md) standard. Each one fills a gap in the [inherited sniffs](Jumbo_Inherited_Sniffs.md), or replaces an inherited sniff
that misreads modern PHP. Each section gives the reason. The custom sniffs for PER Coding Style itself are in
[PERCS31 Custom Sniffs](PERCS31_Custom_Sniffs.md).

| Sniff                                                      | Checks                                                          | Fixable |
| ---------------------------------------------------------- | --------------------------------------------------------------- | ------- |
| `Jumbo.Arrays.ArrayDeclaration`                            | A multi-line array is on one line if it fits                    | Yes     |
| `Jumbo.Arrays.ElementComma`                                | A multi-line array element's comma on the element's last line   | Yes     |
| `Jumbo.Classes.ConstantSpacing`                            | Blank lines after a constant, safe on the last line             | Yes     |
| `Jumbo.Classes.EmptyLinesAroundClassBraces`                | No blank lines inside class braces, allowing `{}`               | Yes     |
| `Jumbo.Classes.EnumCaseSpacing`                            | Blank lines after an enum case, safe on the last line           | Yes     |
| `Jumbo.Classes.PropertySpacing`                            | Blank lines after a property, safe on the last line             | Yes     |
| `Jumbo.Classes.RequireSingleLineClassHeader`               | A split class, interface or enum header fits on a line          | Yes     |
| `Jumbo.Classes.RequireSingleLineMethodSignature`           | A short signature on one line, skipping a promoted hook         | Yes     |
| `Jumbo.Commenting.BlockComment`                            | A block comment's indents, and the spaces around one on a line  | Yes     |
| `Jumbo.Commenting.TrailingComment`                         | One space between code and a `//` comment after it              | Yes     |
| `Jumbo.ControlStructures.HeaderLayout`                     | A header on one line if it fits, else split with its condition  | Yes     |
| `Jumbo.ControlStructures.LanguageConstructWithParentheses` | No `echo('x')`, allowing `exit()` and `die()`                   | Yes     |
| `Jumbo.ControlStructures.MatchArmWrapIndent`               | One line if it fits, else a wrapped `match` arm indented once   | Yes     |
| `Jumbo.ControlStructures.RequireShortTernaryOperator`      | `$a ?: $b`, skipping a nullable type in a `set` hook            | Yes     |
| `Jumbo.Formatting.ExpressionFormat`                        | An expression on one line, or split at its loosest operators    | Yes     |
| `Jumbo.Formatting.RequireSingleLineStatement`              | A split statement or `case` label fits on a line                | Yes     |
| `Jumbo.Functions.RequireSingleLineCall`                    | A call that fits is on one line, skipping a class or `match`    | Yes     |
| `Jumbo.Functions.RequireSingleLineSignature`               | A split function or closure signature fits on a line            | Yes     |
| `Jumbo.Namespaces.UnusedUses`                              | No unused `use` statements, safe with a miscased `use Function` | Yes     |
| `Jumbo.NamingConventions.ValidVariableName`                | No leading underscore on a variable or parameter in a class     | No      |
| `Jumbo.PHP.EmbeddedPhp`                                    | A block's tags on their own lines, level with its code          | Mostly  |
| `Jumbo.TypeHints.DeclareStrictTypes`                       | `strict_types=1` required, allowing `<?php declare(...) ?>`     | Yes     |
| `Jumbo.TypeHints.ParameterTypeHint`                        | Parameter type hints, skipping a promoted hook                  | Yes     |
| `Jumbo.TypeHints.ParameterTypeHintSpacing`                 | Parameter type hint spacing, skipping a promoted hook           | Yes     |
| `Jumbo.WhiteSpace.BlockBlankLines`                         | No blank line at either end of a block `declare` or a hook      | Yes     |
| `Jumbo.WhiteSpace.LanguageConstructSpacing`                | One space after `return`, `echo` and others, with no line break | Yes     |

## Jumbo.Arrays.ArrayDeclaration

Requires a multi-line array to be written on one line if it fits.

**Why:** no inherited sniff collapses a multi-line array that fits on one line. The stock array sniffs (see [Inherited
Sniffs](Jumbo_Inherited_Sniffs.md)) only check the layout of an array that's already multi-line.

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

## Jumbo.Arrays.ElementComma

Requires the comma after an element of a multi-line array or destructuring list to be on the element's last line, not
the next one.

**Why:** PER Coding Style says nothing about where an array element's comma goes. Squiz `ArrayDeclaration`'s `NoComma`
check covers it, but only for a keyed element, and it expects the comma on the line after a heredoc's closing marker, as
before PHP 7.3. So it reports `EOT,`, and its fix puts the comma on a line of its own. PERCS31 excludes it, and this
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

## Jumbo.Classes.ConstantSpacing, EnumCaseSpacing and PropertySpacing

Enforce the blank lines after a class constant, enum case or property. These replace
`SlevomatCodingStandard.Classes.ConstantSpacing`, `EnumCaseSpacing` and `PropertySpacing`, and skip a member on the last
line of a file.

**Why:** the parents assume there's a line after every member, so `public int $a = 1; }` on a file's last line is a PHP
fatal error. That ends the whole run, and loses the report for every other file. PERCS31 reports the closing brace
anyway. Each sniff only adds that one check, and can go once Slevomat fixes it.

**Auto-fixable:** yes, as the parents.

---

## Jumbo.Classes.EmptyLinesAroundClassBraces

Disallows blank lines after a class's opening brace and before its closing brace. This replaces
`SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces`.

**Why:** `SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces` rejects an empty body written as `{}` on one line,
which [PER Coding Style 3.1, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) recommends. This sniff skips that case, and leaves everything else to the
parent. See [PERCS31.Formatting.EmptyBody](PERCS31_Custom_Sniffs.md#percs31formattingemptybody).

**Auto-fixable:** yes, as the parent.

---

## Jumbo.Classes.RequireSingleLineMethodSignature

Requires a method signature that fits within the line length to be written on one line, skipping one with a hook on a
promoted constructor parameter. This replaces `SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature`.

**Why:** the parent takes the `;` in a hook on a promoted constructor parameter, e.g.
`public string $name { set => trim($value); }`, as the end of the signature, so its fix joins only part of it, and
`phpcbf` can't finish. [PER Coding Style 3.1, Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) allows a single short hook there, so this sniff skips a
function with one, and leaves everything else to the parent. See [`PERCS31.Classes.PropertyHooks`](PERCS31_Custom_Sniffs.md#percs31classespropertyhooks).

**Auto-fixable:** yes, as the parent.

---

## Jumbo.Commenting.BlockComment

Checks the layout of a block comment, `/* ... */`. A docblock, `/** ... */`, isn't checked.

**A comment over several lines:**

- Each line after the first is indented at least as far as the line the `/*` is on. A blank line is allowed.
- A `*/` on a line by itself lines up with the `/*`.
- Text may share a line with the `/*` or the `*/`.
- A comment with a `*` starting each line is allowed, and its `*/` may be one column to the right, under the stars.

Where the `/*` itself goes is checked by [`PERCS31.WhiteSpace.ScopeIndent`](PERCS31_Custom_Sniffs.md#percs31whitespacescopeindent), as for a statement.

**A comment on one line:**

- One space after `/*`, and one before `*/`, as in `/* Text */`.
- With code before it on the line, one space before it. With code after it, one space after it.
- The space between a comment and a bracket, comma or operator beside it, or a semicolon after it, isn't checked here,
  as the sniffs for those report it. So `foo(/* none */ $a)` has no space after the `(`.

**Why:** PER Coding Style 3.1 has no rule for comments, so this is an extra rule. `Squiz.Commenting.BlockComment` wants
the text indented four spaces past the `/*`, which isn't this layout.

**Auto-fixable:** yes.

**Good:**

```php
if ($ready) {
    /*
    Start the engine.
    Then wait.
    */
    $speed = /* metres per second */ $engine->start();
}
```

**Bad:**

```php
if ($ready) {
    /*
  Start the engine.
        */
    $speed =   /*metres per second*/   $engine->start();
}
```

---

## Jumbo.Commenting.TrailingComment

Requires a single space between code and a `//` or `#` comment after it on the same line. So comments after code aren't
lined up in a column.

A comment on a line of its own isn't checked here, nor is a block comment, or a comment after a PHP open tag.

**Why:** PER Coding Style 3.1 has no rule for comments, so this is an extra rule. No inherited sniff checks it: the
Squiz and Slevomat sniffs for a comment after code disallow it altogether.

**Auto-fixable:** yes.

**Good:**

```php
$speed = 0; // Not moving.
```

**Bad:**

```php
$speed = 0;// Not moving.
$count = 10;    // Lined up.
```

---

## Jumbo.ControlStructures.HeaderLayout

Requires a control structure's header on one line if it fits, and split over lines if it doesn't. It covers `if`,
`elseif`, `while`, including a `do`-`while`'s, `foreach`, `for`, `switch` and `match`.

**Why:** PER allows either layout for a header that fits on one line, so joining one is an extra rule, in Jumbo only.
For one that doesn't fit, PER shows the layout but no sniff produces it. A header split over lines is also often left
over after `Jumbo.Arrays.ArrayDeclaration` collapses the array inside it.

- **A header split over lines** is joined if the whole resulting line, up to and including ` {`, fits within
  `maxLineLength`. A header with a comment, heredoc, multi-line string or brace body is left alone, as for
  [Jumbo.Arrays.ArrayDeclaration](#jumboarraysarraydeclaration). Reported as `ShouldBeSingleLine`.
- **A header on one line that's too long** is split. Its condition goes on its own line after the `(`, indented once,
  and `) {` goes on the next line, level with the keyword's line. A `for` header's expressions go on a line each.
  Reported as `ShouldBeMultiLine`.

The condition itself isn't changed. If it's still too long on its own line,
[`ExpressionFormat`](#jumboformattingexpressionformat) splits it, and `PERCS31.ControlStructures.ControlStructureSpacing`
puts a `foreach`'s `as` on its own line.

**Auto-fixable:** yes.

**Good:**

```php
foreach ([$m->inv(), $m->pow(-1)] as $inverse) {
    // ...
}

if (
    is_int($caseCloser) && $caseCloser < $endOfScope && in_array($tokens[$caseCloser]['code'], self::TERMINATORS, true)
) {
    return true;
}
```

**Bad:**

```php
foreach (
    [$m->inv(), $m->pow(-1)] as $inverse
) {
    // ...
}

if (is_int($caseCloser) && $caseCloser < $endOfScope && in_array($tokens[$caseCloser]['code'], self::TERMINATORS, true)) {
    return true;
}
```

**Configuration:**

| Property        | Type | Default | Description          |
| --------------- | ---- | ------- | -------------------- |
| `maxLineLength` | int  | 120     | Maximum line length. |

To use a different limit, set every line-length property, as described in [Line Length](LineLength.md).

---

## Jumbo.ControlStructures.LanguageConstructWithParentheses

Disallows parentheses around the argument of a language construct, such as `echo('x')`, except `exit` and `die`. This
replaces `SlevomatCodingStandard.ControlStructures.LanguageConstructWithParentheses`.

**Why:** the Slevomat sniff reports `die()` and fixes it to `die`. [PER Coding Style 3.1, Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says `exit()` and
`die()` SHOULD have parentheses, which `Universal.PHP.RequireExitDieParentheses` checks, so the two sniffs would undo
each other's fixes. This sniff skips `exit` and `die`, and leaves everything else to the parent.

**Auto-fixable:** yes, as the parent.

---

## Jumbo.ControlStructures.MatchArmWrapIndent

Requires a `match` arm's conditions, its `=>` and the first line of its value to be on one line, if they fit. Otherwise,
it sets where the `=>` goes and how the arm's lines are indented. Set `indent` to change the number of spaces, which is
4 by default.

An arm that doesn't fit is wrapped in one of two ways:

- **The conditions stay on one line.** A line starting with the `=>`, or with the value after it, is indented one level
  past the arm's first line.
- **The conditions are split,** one per line with a trailing comma, as PERCS31 requires. Each is level with the first,
  and the `=>` starts the next line, indented once.

**Why:** PER Coding Style 3.1 doesn't say how a wrapped arm is laid out, so this is an extra rule, matching how Jumbo
treats calls and arrays that fit, and keeping each arm distinct. Putting the `=>` on its own line after split conditions
is what makes their trailing comma useful: adding a condition then changes one line. PERCS31 allows the `=>` on the
last condition's line too. `PERCS31.Operators.OperatorPlacement` checks a line starting with an operator. A comment
among the arm's parts is left to a person.

**Auto-fixable:** yes.

**Good:**

```php
$label = match ($code) {
    200, 201 => 'OK',
    $thisIsALongCondition, $andThisIsAnotherOne, $andAThirdCondition, $andAFourthCondition, $andAFifthCondition
        => $this->describe($code),
    $thisIsAnExtremelyLongConditionThatTakesUpMostOfTheLineByItself,
    $andThisIsAnotherOneThatWillNotFitOnTheSameLineAsThatOne,
        => 'Long',
    default => 'Error',
};
```

**Bad:**

```php
$label = match ($code) {
    200,
    201, => 'OK',
    $thisIsAnExtremelyLongConditionThatTakesUpMostOfTheLineByItself,
        $andThisIsAnotherOneThatWillNotFitOnTheSameLineAsThatOne, => 'Long',
    default => 'Error',
};
```

**Configuration:**

| Property        | Type | Default | Description                                  |
| --------------- | ---- | ------- | -------------------------------------------- |
| `indent`        | int  | 4       | The number of spaces in one indent level.    |
| `maxLineLength` | int  | 120     | Maximum line length for an arm's first line. |

---

## Jumbo.ControlStructures.RequireShortTernaryOperator

Requires `$a ?: $b` instead of `$a ? $a : $b`. This replaces
`SlevomatCodingStandard.ControlStructures.RequireShortTernaryOperator`.

**Why:** PHP_CodeSniffer reads the `?` in `set(?int $value)` as a ternary's, as it doesn't know about hooks. The
Slevomat sniff then looks for a `:` that isn't there, and the error stops the file being checked. This sniff skips that
`?`, and leaves everything else to the parent.

**Auto-fixable:** yes, as the parent.

---

## Jumbo.Formatting.ExpressionFormat

Lays out each expression: on one line if it fits, otherwise split only before its loosest operators.

**Why:** PER allows a split before any operator, so this is an extra rule, in Jumbo only. Without it, an expression can
be split anywhere, even inside a group while the operators around the group stay on one line.

**The layout:**

1. If the expression fits on one line within `maxLineLength`, it goes on one line.
2. Otherwise, it's split only before the operators that bind most loosely, outside any grouping parentheses, such as
   the `||` in `$a && $b || $c`. Each line holds as many operands as fit.
3. An operand too long for a line of its own is split the same way, at its own loosest operators. Without parentheses,
   its new lines keep the same indent. In grouping parentheses, the parentheses stay with its contents, and its new
   lines are indented once past the line with the `(`.

Each new line starts with its operator, and is indented once past the line the expression starts on. In a control
structure's condition or a loop header, the lines split at `&&` or `||` are indented once past the line with the `(`,
and a line split at any other operator once past the line its operand starts on. These are the indents
[`PERCS31.Operators.OperatorPlacement`](PERCS31_Custom_Sniffs.md#percs31operatorsoperatorplacement) checks, with Jumbo's
`requireExactConditionIndent`. This sniff only reports where the lines break. A wrong indent, or an operator at the end
of a line, is that sniff's to report.

It covers every binary operator, including `.`, `instanceof` and, on PHP 8.5, `|>`. An assignment operator, or `and`,
`or` or `xor`, ends an expression, so `$total = $price` then `+ $tax` is checked as `$price + $tax`. So does a `,`,
`=>`, ternary `?` or `:`, or the brackets of a call, array or control structure. Each argument of a call is its own
expression.

These are left alone, and `RequireSingleLineStatement` still checks the first:

- An expression with a comment, a heredoc or nowdoc, a multi-line string or an arrow function, or a line break inside
  an operand, such as a multi-line call or closure.
- A `case` condition.
- An expression written on one line that's too long, unless it's a whole statement, a value after an assignment,
  `return` or `=>`, or a condition on its own line. Anywhere else, such as in an argument, a split would clash with the
  layout around it.
- A ternary's condition or value that's too long. One that's split but fits on one line is still joined.

| Code                 | Reported for                                       |
| -------------------- | -------------------------------------------------- |
| `ShouldBeSingleLine` | A split expression that fits on one line           |
| `IncorrectSplit`     | A longer expression that isn't laid out as above   |

**Auto-fixable:** yes. Only the whitespace around its operators and inside its grouping parentheses changes.

**Good:**

```php
$isMatch = ($bestValue < 1.0 && $prefixedValue > $bestValue)
    || ($prefixedValue >= 1.0 && $prefixedValue < $bestValue && $prefixedValue !== $previousValue);

$message = 'Cannot convert ' . $quantity->format() . ' to ' . $targetUnit->format() . ' as their dimensions differ: '
    . $quantity->dimensions() . ' and ' . $targetUnit->dimensions() . '.';

$isValid = !empty($type)
    && (self::isBasicType($type) || self::isPseudoType($type) || self::isResourceType($type)
        || self::isClassType($type));
```

**Bad:**

```php
$isMatch = ($bestValue < 1.0
    && $prefixedValue > $bestValue)
    || ($prefixedValue >= 1.0
    && $prefixedValue < $bestValue && $prefixedValue !== $previousValue);

$total = $price
    + $tax;

$message = 'Cannot convert ' .
    $quantity->format();
```

**Configuration:**

| Property        | Type | Default | Description          |
| --------------- | ---- | ------- | -------------------- |
| `maxLineLength` | int  | 120     | Maximum line length. |

To use a different limit, set every line-length property, as described in [Line Length](LineLength.md).

---

## Jumbo.Formatting.RequireSingleLineStatement, and the signature and class header sniffs

Require code split across lines to be written on one line, if the whole resulting line fits within `maxLineLength`.

| Sniff                                         | Covers                                                 |
| --------------------------------------------- | ------------------------------------------------------ |
| `Jumbo.Classes.RequireSingleLineClassHeader`  | A class, interface or enum's `extends`, `implements`   |
| `Jumbo.Formatting.RequireSingleLineStatement` | A statement or `case` label, outside its calls, arrays |
| `Jumbo.Functions.RequireSingleLineSignature`  | A function or closure's signature, and a `use` list    |

**Why:** PER allows these splits, so this is an extra rule, in Jumbo only. Other sniffs already do the same for a call,
an array, a method signature, a condition and a loop header.

These are left alone:

- A chain of two or more methods, each on its own line. A single wrapped `->` is collapsed.
- A comment, a heredoc or nowdoc, or a multi-line string.
- A line break only inside a call or array, whose own sniffs report it.
- A line break before or after a binary operator, or just inside grouping parentheses, in an expression that
  [`ExpressionFormat`](#jumboformattingexpressionformat) lays out.

A statement with a closure, `match` or anonymous class body, or a call or array too long for one line, can't be
collapsed. So the lines around it are joined instead, and it moves left to match:

```php
$a = $b
    ?? function () {
        return 1;
    };

// Becomes:
$a = $b ?? function () {
    return 1;
};
```

**Auto-fixable:** yes.

**Good:**

```php
$a = $b ? 1 : 2;

$query
    ->select()
    ->where();

/**
 * @param list<int> $items
 */
function total(array $items): int
{
    return 0;
}
```

**Bad:**

```php
$a
    = $b ? 1 : 2;

$a = $b
    ? 1
    : 2;

$a = $b ? (
    $c
) : 2;

/**
 * @param list<int> $items
 */
function total(
    array $items,
): int {
    return 0;
}
```

**Configuration:**

| Property        | Type | Default | Description                               |
| --------------- | ---- | ------- | ----------------------------------------- |
| `maxLineLength` | int  | 120     | Maximum line length for a collapsed line. |

To use a different limit, set every line-length property, as described in [Line Length](LineLength.md).

---

## Jumbo.Functions.RequireSingleLineCall

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

## Jumbo.Namespaces.UnusedUses

Disallows unused `use` statements, including in annotations. This replaces
`SlevomatCodingStandard.Namespaces.UnusedUses`.

**Why:** the Slevomat sniff reads the type of a `use` statement with a case-sensitive check, so
`use Function Vendor\helper;` is treated as a class import. It then reports the import as unused, and `phpcbf` deletes
it, which can break the code. This sniff skips the whole file while a `function` or `const` keyword after `use` isn't in
lower case. `Universal.UseStatements.LowercaseFunctionConst` reports that as an error and fixes it, and on `phpcbf`'s
next pass the import is read correctly. Everything else is left to the parent. It can be removed once Slevomat fixes the
check.

**Auto-fixable:** yes, as the parent.

---

## Jumbo.NamingConventions.ValidVariableName

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
    /**
     * @param list<int> $items
     */
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

## Jumbo.PHP.EmbeddedPhp

Sets the layout of a multi-line PHP block in a file with markup. This is a fork of `Squiz.PHP.EmbeddedPhp`.

- Nothing comes after `<?php` on its line, and nothing comes before `?>` on its line, except an indent.
- With nothing but an indent before `<?php`, the code directly inside is level with the tag.
- With markup before `<?php`, the code is indented one level past that line.
- `?>` is level with the line `<?php` is on. Markup may follow it.
- A tag on one line is exempt, such as `<?php foo(); ?>` or `<?php declare(strict_types=1) ?>`, and so is a last block
  with no `?>`.
- A short echo tag must be on one line.
- A blank line just inside the tags is allowed.

**Why:** PER doesn't set this layout. The PERCS31 standard allows the code level with the tags, or one level past them.
The Squiz sniff skips a file's first tag, so a file with one block wouldn't be checked, and it requires a semicolon that
PER's own `<?php declare(strict_types=1) ?>` doesn't have. Its fix also splits markup from the tags, which changes the
HTML's whitespace.

**Auto-fixable:** yes, except a short echo tag over several lines, as the right rewrite is a judgement call.

**Good:**

```php
<div>
    <?php
    if ($a) {
        foo();
    }
    ?>
    <p><?= $b ?></p>
    <p>Aged <span><?php
        $age = compute($user);
        echo $age;
    ?></span></p>
</div>
```

**Bad:**

```php
<div>
    <?php if ($a) {
        foo();
    } ?>
    <?php
        bar();
    ?>
    <p><?= foo(
        $b,
    ) ?></p>
</div>
```

---

## Jumbo.TypeHints.DeclareStrictTypes

Requires `declare(strict_types=1)` at the top of each file. This replaces
`SlevomatCodingStandard.TypeHints.DeclareStrictTypes`, and allows the declaration between its own PHP tags on the first
line of a file with markup.

**Why:** [PER Coding Style 3.1, Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements) says that in a file containing markup outside PHP tags, the declaration MUST be
on the first line, e.g. `<?php declare(strict_types=1) ?>`. The parent requires a blank line before and after it. This
sniff accepts that form, with or without the `;`, if it sets `strict_types` to 1. See [`PERCS31.Files.FileHeader`](PERCS31_Custom_Sniffs.md#percs31filesfileheader).
Section 3 also forbids spaces inside the parentheses, so it requires `strict_types=1`, where the parent's default is
`strict_types = 1`. And it lists declare statements as one block with no blank line in it, where the parent always wants
a blank line after `strict_types`, so this sniff wants none if another declare statement follows.

**Auto-fixable:** yes, as the parent. PHP doesn't allow `strict_types` in a block `declare`, so for a file starting with
one, the fix adds `declare(strict_types=1);` on the line above. In a file with markup, the fix adds
`<?php declare(strict_types=1) ?>` as the first line, as PER requires. PHP skips a shebang line, so in a file starting
with one, the fix adds it as the second line.

---

## Jumbo.TypeHints.ParameterTypeHint

Requires a type hint on each parameter, skipping a function with a hook on a promoted constructor parameter. This
replaces `SlevomatCodingStandard.TypeHints.ParameterTypeHint`.

**Why:** the parent treats every variable between a function's parentheses as a parameter, so `$value` or `$this` in a
hook on a promoted constructor parameter is reported as a parameter with no type hint. The other parameters of that
function aren't checked either. [PER Coding Style 3.1, Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) allows a single short hook there, so this sniff skips
a function with one, and leaves everything else to the parent. See [`PERCS31.Classes.PropertyHooks`](PERCS31_Custom_Sniffs.md#percs31classespropertyhooks).

**Auto-fixable:** yes, as the parent.

---

## Jumbo.TypeHints.ParameterTypeHintSpacing

Checks the spacing around parameter type hints, skipping a function with a hook on a promoted constructor parameter.
This replaces `SlevomatCodingStandard.TypeHints.ParameterTypeHintSpacing`.

**Why:** the parent treats every variable between a function's parentheses as a parameter, so `$value` or `$this` in a
hook on a promoted constructor parameter is reported as a parameter with bad spacing. The other parameters of that
function aren't checked either. [PER Coding Style 3.1, Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) allows a single short hook there, so this sniff skips
a function with one, and leaves everything else to the parent. See [`PERCS31.Classes.PropertyHooks`](PERCS31_Custom_Sniffs.md#percs31classespropertyhooks).

**Auto-fixable:** yes, as the parent.

---

## Jumbo.WhiteSpace.BlockBlankLines

Disallows a blank line straight after the opening brace, or straight before the closing brace, of a block `declare`, a
property's hook list and a hook's body. A blank line between two statements, or between two hooks, isn't reported.

**Why:** PER only rules these blank lines out for a class and a control structure, and at the end of a function's body.
It has no rule for a block `declare` or a property hook, so the [PERCS31](PERCS31.md) standard allows them. This is an extra
rule, so that every block in braces starts and ends the same way. See
[Blank Lines in a Block `declare`](PER-CS_3.1_Judgement_Calls.md#blank-lines-in-a-block-declare) and
[Blank Lines Inside a Hook's Braces](PER-CS_3.1_Judgement_Calls.md#blank-lines-inside-a-hooks-braces).

No inherited sniff covers these blocks. `Squiz.WhiteSpace.ControlStructureSpacing` has a fixed list of control
structures, without `declare`, and PHP_CodeSniffer doesn't read a hook's braces as a scope. The start of a function's
body is checked by `Squiz.WhiteSpace.FunctionOpeningBraceSpace`.

**Auto-fixable:** yes.

**Good:**

```php
public string $name {
    get {
        return $this->name;
    }

    set {
        $this->name = trim($value);
    }
}
```

**Bad:**

```php
public string $name {

    get {

        return $this->name;

    }

}
```

---

## Jumbo.WhiteSpace.LanguageConstructSpacing

Requires a single space after a language construct, with no line break: `echo`, `print`, `return`, `include`,
`include_once`, `require`, `require_once`, `yield`, `yield from`, `throw`, `namespace`, `use` and `goto`. This replaces
`Generic.WhiteSpace.LanguageConstructSpacing`.

**Why:** PER has no such rule, so `return` then a line break is allowed in the [PERCS31](PERCS31.md) standard. The Generic sniff also
checks `new`, which PER's section 8 puts on its own line before an anonymous class's attributes, and its fix turns
`(yield)` into `(yield )`. This sniff skips both, and leaves everything else to the parent. A comment after a construct
isn't reported, as the parent leaves it alone.

**Auto-fixable:** yes, as the parent.

**Good:**

```php
return $total;
```

**Bad:**

```php
return
    $total;
```
