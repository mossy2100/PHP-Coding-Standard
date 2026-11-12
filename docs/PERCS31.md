# PERCS31 Coding Standard

A PHP_CodeSniffer standard for [PER Coding Style 3.1](https://www.php-fig.org/per/coding-style/), and nothing more. It's built on PSR-12, which PER Coding Style
extends, and so includes [PSR-1](https://www.php-fig.org/psr/psr-1/), which PER Coding Style requires.

No complete PHP_CodeSniffer standard for PER Coding Style exists at the time of development. PSR-12's sniffs get some
PER rules wrong, such as rejecting an empty body written as `{}`, and don't yet support PHP 8.4 and 8.5 features such as
property hooks and the pipe operator. This standard fixes both, using sniffs from PHP_CodeSniffer, Slevomat, PHPCSExtra
and php-collective where they fit, and custom sniffs where nothing did.

Where the spec is unclear, contradicts itself or says nothing, [PER-CS 3.1 Judgement Calls](PER-CS_3.1_Judgement_Calls.md) gives the reading PERCS31
takes, and why.

The [Jumbo standard](Jumbo.md) is PERCS31 plus extra rules. Use PERCS31 on its own to follow PER Coding Style without them.

**Contents:**

- [Usage](#usage)
- [PSR-12](#psr-12)
- [Inherited Sniffs](#inherited-sniffs)
- [Custom Sniffs](#custom-sniffs)
- [Not Checked](#not-checked)

---

## Usage

PERCS31 is installed with the `mossy2100/coding-standard` package, and registered with PHP_CodeSniffer the same way. See
[Installation](../README.md#installation). Then reference it in your `phpcs.xml`:

```xml
<?xml version="1.0"?>
<ruleset name="My Project">
    <file>src</file>
    <file>tests</file>

    <rule ref="PERCS31"/>
</ruleset>
```

---

## PSR-12

The complete [PSR-12](https://www.php-fig.org/psr/psr-12/) standard is included. These PSR-12 sniffs are replaced by PERCS31 versions:

- **Generic.Files.LineLength**, by [`PERCS31.Files.LineLength`](#percs31fileslinelength), which also warns, at a lower severity, about a line over
  80 characters.
- **Generic.Formatting.DisallowMultipleStatements**, by [`PERCS31.Formatting.DisallowMultipleStatements`](#percs31formattingdisallowmultiplestatements), which allows
  `{ get; set; }` on an abstract or interface property.
- **Generic.PHP.LowerCaseKeyword**, by [`PERCS31.PHP.LowerCaseKeyword`](#percs31phplowercasekeyword), which also checks a property hook's name.
- **Generic.PHP.LowerCaseType**, by [`PERCS31.PHP.LowerCaseType`](#percs31phplowercasetype), which also checks a `set` hook's parameter type.
- **Generic.NamingConventions.UpperCaseConstantName**, from PSR-1, by [`PERCS31.NamingConventions.UpperCaseConstantName`](#percs31namingconventionsuppercaseconstantname),
  which allows PascalCase for an enum constant.
- **PSR12.Classes.ClosingBrace**, by [`PERCS31.Classes.ClosingBrace`](#percs31classesclosingbrace), which also checks a property hook's closing braces.
- **PSR12.ControlStructures.BooleanOperatorPlacement** is kept, with `allowOnly` set to `first`, so a wrapped `&&` or
  `||` in a condition starts the line (5.1, 6.4). PSR-12 only requires consistency.
- **PSR12.ControlStructures.ControlStructureSpacing**, by [`PERCS31.ControlStructures.ControlStructureSpacing`](#percs31controlstructurescontrolstructurespacing), which
  drops its rules for a multi-line `foreach`.
- **PSR12.Files.DeclareStatement**, by [`PERCS31.Files.DeclareStatement`](#percs31filesdeclarestatement), which accepts a declare statement with several
  directives, such as `declare(ticks=1,strict_types=1);`, or a string value, such as `declare(encoding='UTF-8');`.
- **PSR12.Files.FileHeader**, by [`PERCS31.Files.FileHeader`](#percs31filesfileheader), which allows `<?php declare(strict_types=1) ?>` on the
  first line of a file with markup.
- **PSR12.Functions.NullableTypeDeclaration**, by [`PERCS31.Functions.NullableTypeDeclaration`](#percs31functionsnullabletypedeclaration), which also checks a `set`
  hook's parameter.
- **PSR12.Operators.OperatorSpacing**, by [`PERCS31.Operators.OperatorSpacing`](#percs31operatorsoperatorspacing), which also covers the pipe operator `|>`.
- **PSR12.Traits.UseDeclaration** is kept, but its `NoBlankLineAfterUse` and `BlankLineAfterLastUse` codes are replaced
  by `SlevomatCodingStandard.Classes.TraitUseSpacing`, which also reports more than one blank line after the last trait
  `use` statement. Its `MultipleImport` code is replaced by `SlevomatCodingStandard.Classes.TraitUseDeclaration`, which
  also reports several traits in a `use` statement with a `{ }` block.
- **PSR12.Classes.AnonClassDeclaration** is kept, but its three comma spacing codes are replaced by
  `Universal.WhiteSpace.CommaSpacing`, which reports the same.
- **PSR12.Properties.ConstantVisibility** is kept, but a constant with no visibility is an error, not a warning, as
  [Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says MUST.
- **PSR2.Classes.ClassDeclaration**, **Squiz.Functions.MultiLineFunctionDeclaration** and
  **Squiz.WhiteSpace.ScopeClosingBrace**, by versions that allow an empty body as `{}`. See
  [`PERCS31.Formatting.EmptyBody`](#percs31formattingemptybody).
- **PSR2.Classes.PropertyDeclaration** and **Generic.WhiteSpace.ScopeIndent**, by [`PERCS31.Classes.PropertyDeclaration`](#percs31classespropertydeclaration)
  and [`PERCS31.WhiteSpace.ScopeIndent`](#percs31whitespacescopeindent), which support property hooks (PHP 8.4).
- **Squiz.ControlStructures.ControlSignature** is kept, but its `NewlineAfterOpenBrace` code is replaced by
  [`PERCS31.ControlStructures.BodyOnNextLine`](#percs31controlstructuresbodyonnextline), which allows an empty body as `{}`.
- **PSR2.ControlStructures.SwitchDeclaration**, by [`PERCS31.ControlStructures.SwitchDeclaration`](#percs31controlstructuresswitchdeclaration), which adds PER's
  `switch` rules.
- **PSR2.Methods.FunctionCallSignature**, by [`PERCS31.Functions.FunctionCallLayout`](#percs31functionsfunctioncalllayout), which also checks `clone()`, and
  skips a `set` hook's parameter list, which isn't a call.
- **Squiz.Classes.ValidClassName**, from PSR-1, by [`PERCS31.NamingConventions.ValidClassName`](#percs31namingconventionsvalidclassname), which also reports a name
  with no lower-case letter.
- **PSR12.Keywords.ShortFormTypeKeywords**, by [`PERCS31.PHP.TypeCast`](#percs31phptypecast), which also reports `(double)` and `(binary)`.

`Squiz.Functions.FunctionDeclarationArgumentSpacing`'s `SpaceBeforeEquals` and `SpaceAfterEquals` are excluded, as a
default value's `=` is a binary operator, which section 6.2 allows more than one space around.
[`PERCS31.Operators.OperatorSpacing`](#percs31operatorsoperatorspacing) checks it instead.

---

## Inherited Sniffs

Sniffs from PHP_CodeSniffer's other standards, Slevomat and PHPCSExtra, each for the PER Coding Style rule in brackets.
Two custom sniffs extend php-collective sniffs.

### Generic

- **Generic.Arrays.ArrayIndent**: Indents multi-line array elements one level, with the closing bracket at the indent of
  the statement (11).
- **Generic.Arrays.DisallowLongArraySyntax**: Requires short array syntax `[]` instead of `array()` (11).
- **Generic.Formatting.SpaceAfterCast**: Requires exactly one space after a cast, e.g. `(int) $value` (6.1).
- **Generic.Strings.UnnecessaryHeredoc**: Warns about a heredoc with no interpolation or escape sequences, which could
  be a nowdoc (10).

### PEAR

- **PEAR.WhiteSpace.ObjectOperatorIndent**: In a method chain split across lines, requires each `->` or `?->` to start
  its line, indented once past the line the statement starts on (4.7).

### Squiz

- **Squiz.Arrays.ArrayDeclaration**: Requires the first value of a multi-line array on the next line, and one value per
  line (11), and no trailing comma on a single-line array (2.6). Its other checks aren't PER rules, so they're excluded:
  alignment, spacing around `=>`, the spacing in `[]`, mixing keyed and unkeyed values, and which line a comma goes on.
  Its check for the closing bracket on a new line is excluded too, as `Generic.Arrays.ArrayIndent` reports the same. Its
  comma spacing checks never run on a single-line array, so `Universal.WhiteSpace.CommaSpacing` checks that instead. Its
  check for a multi-line array's trailing comma is replaced by Slevomat's `Arrays.TrailingArrayComma`, as it missed a
  keyed last element, and wanted the comma after a heredoc on the next line, as before PHP 7.3. Its `ValueNoNewline`
  check is replaced by [`PERCS31.Arrays.ValuePerLine`](#percs31arraysvalueperline), as it read an attribute on a value as an earlier value.

### PHPCSExtra

Reference: [PHPCSExtra repository and documentation](https://github.com/PHPCSStandards/PHPCSExtra)

- **Universal.Attributes.BracketSpacing**: Disallows a space after `#[` or before `]`, e.g. `#[ Override ]` (12.1).
- **Universal.Attributes.DisallowAttributeParentheses**: Disallows `()` on an attribute with no arguments, e.g.
  `#[Override()]` (12.1).
- **Universal.Attributes.TrailingComma**: Disallows a trailing comma in an attribute block on one line, and requires one
  in a split block of several attributes (2.6). Its `ForbiddenSingleAttributeMultiLine` code is excluded, as it forbids
  the comma in a split block of one attribute, where 2.6 requires it.
- **Universal.Classes.DisallowAnonClassParentheses**: Disallows `()` on an anonymous class with no arguments, e.g.
  `new class () {}` (8).
- **Universal.Constants.LowercaseClassResolutionKeyword**: Requires `::class` in lower case (2.5).
- **Universal.ControlStructures.DisallowAlternativeSyntax**: Disallows the alternative syntax, e.g.
  `if ($a): ... endif;`, as a structure's body must be in braces (5).
- **Universal.Operators.ConcatPosition**: Requires a wrapped `.` at the start of the line, not the end (6.4).
- **Universal.PHP.LowercasePHPTag**: Requires the `<?php` tag in lower case (3).
- **Universal.PHP.RequireExitDieParentheses**: Warns about `exit` or `die` without parentheses, as a SHOULD (4.7).
- **Universal.UseStatements.LowercaseFunctionConst**: Requires `function` and `const` in lower case in a `use` statement
  (2.5).
- **Universal.WhiteSpace.CommaSpacing**: Requires no space before a comma and one after, in an attribute block, e.g.
  `#[Foo, Bar]` (12.3), and in any other list, such as an array, group `use`, `match` conditions or `clone()`, as the
  examples show (3, 5.2, 11). Its codes ending in `InFunctionCall` check a comma inside a call's parentheses (4.7). They
  replace `Generic.Functions.FunctionCallArgumentSpacing`, which misses a comma on the line after a heredoc's closing
  marker, and one in an array or a `match` inside the call, as in `foo([1,2])`. Its codes for parameter, closure `use`
  and `declare` lists aren't used, as other sniffs check those. It also covers lists with no example, such as
  `echo 1, 2;` and `global $a, $b;`.

### Slevomat

Reference: [Slevomat coding standard repository and documentation](https://github.com/slevomat/coding-standard)

- **Arrays.SingleLineArrayWhitespace**: Requires no space inside a single-line array's brackets, as section 11's example
  shows (11). Its comma checks are excluded, as `Universal.WhiteSpace.CommaSpacing` covers them, including in an array
  nested in a multi-line one, which this sniff skips.
- **Arrays.TrailingArrayComma**: Requires a trailing comma after the last element of a multi-line array or short
  destructuring list, as in `[$a, $b] = $c;`, including after a heredoc's closing marker (2.6). `list()` is covered by
  [`PERCS31.Formatting.TrailingComma`](#percs31formattingtrailingcomma).
- **Attributes.RequireAttributeAfterDocComment**: Requires a doc comment before the attributes, not after (12.2).
- **Classes.BackedEnumTypeSpacing**: Requires no space before a backed enum's colon (9). Its check for one space after
  it is excluded, as [`PERCS31.Classes.ClassDeclaration`](#percs31classesclassdeclaration) reports the same.
- **Classes.ClassKeywordOrder**: Requires `abstract` or `final` before `readonly` on a class, e.g.
  `final readonly class` (4.6).
- **Classes.DisallowMultiConstantDefinition**: Disallows several constants in one statement (4.3). Needs Slevomat 8.31.3
  or later, which stops it flagging a comma inside parentheses, as in `const I = new Complex(0, 1);`.
- **Classes.DisallowMultiPropertyDefinition**: Disallows several properties in one statement (4.3).
- **Classes.PropertyDeclaration**: Requires the modifier order, and the spacing around the modifiers and the type,
  including on promoted properties (4.3, 4.6). Its check for a space after a nullable type's `?` is replaced by
  [`PERCS31.Functions.NullableTypeDeclaration`](#percs31functionsnullabletypedeclaration), which is more complete: it checks every place a type can appear,
  including a `set` hook's parameter, not just a property.
- **Classes.TraitUseDeclaration**: Disallows several traits in one `use` statement, as in `use A, B;` (4.2). It
  replaces `PSR12.Traits.UseDeclaration.MultipleImport`, which misses a statement with a `{ }` block, as in
  `use A, B {`. That form is reported but not fixed.
- **Classes.TraitUseSpacing**: Requires no blank line between trait `use` statements, exactly one after the last, and
  none before the class's closing brace, as section 4.2's examples show (4.2). Its check before the first `use` is
  excluded, as `PSR12.Traits.UseDeclaration` reports the same.
- **Functions.ArrowFunctionDeclaration**: Requires no space after `fn`, e.g. `fn($x) => $x`. The expression may be on
  the next line (7.1).
- **Functions.DisallowTrailingCommaInClosureUse** and **...InDeclaration**: Disallow a trailing comma in a single-line
  list (2.6). For a call, [`PERCS31.Functions.DisallowTrailingCommaInCall`](#percs31functionsdisallowtrailingcommaincall) replaces `...InCall`.
- **Functions.NamedArgumentSpacing**: Requires no space before a named argument's colon, and one after (4.7).
- **Functions.RequireTrailingCommaInClosureUse** and **...InDeclaration**: Require a trailing comma in a multi-line
  list (2.6). For a call, [`PERCS31.Functions.RequireTrailingCommaInCall`](#percs31functionsrequiretrailingcommaincall) replaces `...InCall`.
- **TypeHints.DNFTypeHintFormat**: Requires no spaces inside the parentheses (2.5). Its check for spaces around `|` and
  `&` is replaced by [`PERCS31.TypeHints.CompoundTypeOperatorSpacing`](#percs31typehintscompoundtypeoperatorspacing), which allows a split type. Its checks for `null`
  last and for `?T` are replaced by [`PERCS31.TypeHints.NullType`](#percs31typehintsnulltype), which covers every type.

---

## Custom Sniffs

Each one fills a gap in the inherited sniffs, or replaces an inherited sniff that conflicts with PER Coding Style. Each
section gives the reason.

| Sniff                                                   | Checks                                                              | Fixable |
| ------------------------------------------------------- | ------------------------------------------------------------------- | ------- |
| `PERCS31.Arrays.ArrayBracketSpacing`                    | No blank line before a multi-line array's `]`                       | Yes     |
| `PERCS31.Arrays.OpeningBracketPlacement`                | A multi-line array's `[` isn't on a line of its own, outside a list | Yes     |
| `PERCS31.Arrays.ValuePerLine`                           | One value per line in a multi-line array, the first on the next     | Yes     |
| `PERCS31.Attributes.AttributePlacement`                 | An attribute is on its own line, except on a parameter              | Yes     |
| `PERCS31.Attributes.DisallowMultiLineAttributesJoining` | One attribute per block when a block spans lines                    | Yes     |
| `PERCS31.Attributes.DisallowMultipleAttributesPerLine`  | Separate attribute blocks on separate lines                         | Yes     |
| `PERCS31.Classes.AnonClassOpeningBrace`                 | One space before an anonymous class's `{`, and nothing after it     | Yes     |
| `PERCS31.Classes.ClassDeclaration`                      | Class declarations, allowing an empty body as `{}`                  | Yes     |
| `PERCS31.Classes.ClassInstantiationNoBrackets`          | A warning for `(new Foo())->bar()`                                  | Yes     |
| `PERCS31.Classes.ClosingBrace`                          | No comment or statement after a class's, function's or hook's `}`   | No      |
| `PERCS31.Classes.ConstantDeclaration`                   | One space after a class constant's `const`, and after its type      | Yes     |
| `PERCS31.Classes.EnumMemberVisibility`                  | `private`, not `protected`, for a method or constant in an enum     | Yes     |
| `PERCS31.Classes.PromotedPropertyHooks`                 | A hook on a promoted parameter is a single short hook on one line   | No      |
| `PERCS31.Classes.PropertyDeclaration`                   | No leading `_`, no `var`, a visibility, and `get` before `set`      | Partly  |
| `PERCS31.Classes.PropertyHookLayout`                    | A hook list's and hook body's braces, and a short hook's `=>`       | Partly  |
| `PERCS31.ControlStructures.BodyOnNextLine`              | A structure's body on the line after its `{`, allowing `{}`         | Yes     |
| `PERCS31.ControlStructures.ControlStructureSpacing`     | A control structure's parentheses, with no split rule for `foreach` | Yes     |
| `PERCS31.ControlStructures.MatchArmPlacement`           | Each `match` arm starts on a new line                               | Yes     |
| `PERCS31.ControlStructures.MultiLineTernaryOperator`    | A multi-line ternary takes three lines, `?` and `:` leading         | Yes     |
| `PERCS31.ControlStructures.SwitchDeclaration`           | `switch` rules, with a `break` in the last case                     | Yes     |
| `PERCS31.Files.DeclareStatement`                        | No spaces in `declare(...)`, with one directive or several          | Yes     |
| `PERCS31.Files.FileHeader`                              | The file header, allowing `<?php declare(...) ?>` above markup      | Yes     |
| `PERCS31.Files.LineLength`                              | A warning over 120 characters, and a hidden one over 80             | No      |
| `PERCS31.Formatting.DisallowMultipleStatements`         | One statement per line, allowing `{ get; set; }`                    | Yes     |
| `PERCS31.Formatting.EmptyBody`                          | An empty class, function, closure or anonymous class body is `{}`   | Yes     |
| `PERCS31.Formatting.MethodChaining`                     | The first method of a split chain on the next line                  | Yes     |
| `PERCS31.Formatting.TrailingComma`                      | Trailing commas in `match`, group `use`, `list()` and some calls    | Yes     |
| `PERCS31.Functions.DisallowTrailingCommaInCall`         | No trailing comma in a call, unless the argument list is split      | Yes     |
| `PERCS31.Functions.ArrowFunctionSemicolonSpacing`       | No space before the `;` that ends an arrow function                 | Yes     |
| `PERCS31.Functions.FunctionCallLayout`                  | A call's argument list, including `clone()`'s                       | Yes     |
| `PERCS31.Functions.MultiLineFunctionDeclaration`        | Function, closure and `fn` declarations, allowing `{}` bodies       | Yes     |
| `PERCS31.Functions.NullableTypeDeclaration`             | No space after the `?` of a nullable type, in any position          | Yes     |
| `PERCS31.Functions.RequireTrailingCommaInCall`          | A trailing comma in a split call, but not after `foo(...)`'s `...`  | Yes     |
| `PERCS31.Namespaces.GroupUseLayout`                     | A split group `use` has one name per line, and `}` on its own line  | Yes     |
| `PERCS31.NamingConventions.AcronymCasing`               | A warning for an acronym in capitals in a name                      | No      |
| `PERCS31.NamingConventions.UpperCaseConstantName`       | Constants in UPPER_CASE, or PascalCase in an enum                   | No      |
| `PERCS31.NamingConventions.ValidClassName`              | Classes, interfaces, traits and enums in PascalCase                 | No      |
| `PERCS31.NamingConventions.ValidConstantName`           | No leading underscore on a constant                                 | No      |
| `PERCS31.NamingConventions.ValidEnumCaseName`           | Enum cases in PascalCase                                            | No      |
| `PERCS31.Operators.OperatorPlacement`                   | A wrapped binary operator starts the next line, indented once       | Yes     |
| `PERCS31.Operators.OperatorSpacing`                     | At least one space around binary operators, including `\|>`         | Yes     |
| `PERCS31.PHP.LowerCaseKeyword`                          | Keywords in lower case, including a property hook's `get` or `set`  | Yes     |
| `PERCS31.PHP.LowerCaseType`                             | Types in lower case, including a `set` hook's parameter type        | Yes     |
| `PERCS31.PHP.RequireCloneParentheses`                   | A warning for `clone $a`, not `clone($a)`                           | Yes     |
| `PERCS31.PHP.TypeCast`                                  | `(bool)`, `(int)`, `(float)` and `(string)` in a cast               | Yes     |
| `PERCS31.Strings.HeredocIndentation`                    | Where a heredoc or nowdoc begins, and its indentation               | Yes     |
| `PERCS31.TypeHints.CompoundTypeLayout`                  | One type, or one DNF union segment, per line of a split type        | Yes     |
| `PERCS31.TypeHints.CompoundTypeOperatorSpacing`         | No space around `\|` or `&` in a type, but a split before one       | Yes     |
| `PERCS31.TypeHints.NullType`                            | `null` last in a union, and `?T` in a constant or hook parameter    | Yes     |
| `PERCS31.WhiteSpace.ArrowSpacing`                       | One space around `=>` in `match` arms                               | Yes     |
| `PERCS31.WhiteSpace.ConsistentIndent`                   | No line indented further than its scope, after a statement          | Yes     |
| `PERCS31.WhiteSpace.ScopeClosingBrace`                  | A closing brace on its own line, allowing `{}`                      | Yes     |
| `PERCS31.WhiteSpace.ScopeIndent`                        | Indentation, including property hooks and anonymous classes         | Yes     |

### PERCS31.Arrays.ArrayBracketSpacing

Disallows a blank line before the closing bracket of a multi-line array. This extends
`PhpCollective.Arrays.ArrayBracketSpacing`, from [php-collective/code-sniffer](https://github.com/php-collective/code-sniffer).

**Why:** [PER Coding Style 3.1, Section 11](https://www.php-fig.org/per/coding-style/#11-arrays) says the closing bracket MUST be on the next line after the last value.
`Generic.Arrays.ArrayIndent` only checks that it starts a line. This subclass skips a destructuring assignment, which
isn't an array declaration, so section 2.3 allows the blank line.

**Auto-fixable:** yes, except after a comment.

**Good:**

```php
$values = [
    1,
    2,
];
```

**Bad:**

```php
$values = [
    1,
    2,

];
```

---

### PERCS31.Arrays.OpeningBracketPlacement

Disallows the opening bracket of a multi-line array on a line of its own, e.g. after `=` or `return`. These are
allowed, as each has to start a line:

- An element of a multi-line array.
- An argument in an argument list, including an attribute's.
- A condition of a `match` arm.
- A destructuring assignment such as `[$a, $b] = pair();` split over lines.

**Why:** [PER Coding Style 3.1, Section 11](https://www.php-fig.org/per/coding-style/#11-arrays) says the opening bracket of a multi-line array MUST NOT be on its own line.
PER's own example puts a nested array's `[` on its own line, hence the first exception. [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) puts each
argument of a split argument list on its own line, hence the second, and a `match` arm starts a line too. No inherited
sniff checks it.

**Auto-fixable:** yes. The fix moves the `[` to the end of the previous line, and `Generic.Arrays.ArrayIndent` then
re-indents the elements. After a comment, it's left to a person.

**Good:**

```php
$response = $client->post([
    'name' => $customer->fullName(),
    'email' => $customer->primaryEmailAddress(),
    'message' => 'Thanks for signing up. Your account is now active.',
]);

$response = $client->post(
    '/customers/welcome',
    [
        'name' => $customer->fullName(),
        'email' => $customer->primaryEmailAddress(),
        'message' => 'Thanks for signing up. Your account is now active.',
    ],
);

$rows = [
    [
        'id' => 1,
        'name' => 'Alice Example',
        'email' => 'alice@example.com',
        'team' => 'Platform Engineering',
        'role' => 'Administrator',
    ],
];
```

**Bad:**

```php
$payload =
    [
        'name' => $customer->fullName(),
        'email' => $customer->primaryEmailAddress(),
        'message' => 'Thanks for signing up. Your account is now active.',
    ];
```

---

### PERCS31.Arrays.ValuePerLine

Requires each value of a multi-line array to start on a new line, including the first. This replaces
`Squiz.Arrays.ArrayDeclaration`'s `ValueNoNewline` check, which only looks at an array with two or more values and no
keys. This sniff also checks an array with one value, and one that mixes keyed and unkeyed values, as in
`'a' => 1, 2,`, which no Squiz check covers. An array whose values all have keys is left to the Squiz sniff's
`IndexNoNewline` check.

**Why:** [PER Coding Style 3.1, Section 11](https://www.php-fig.org/per/coding-style/#11-arrays) says that when an array is split across lines, the first value MUST be on the
next line, and there MUST be only one value per line. The Squiz check reads an attribute on a value as an earlier value
on the line, so it reports `#[Pure] static fn() => 1,` though it's on a line of its own. PER doesn't say where a
closure's attribute goes, and the attribute is part of the value. A subclass can't fix this, as the check is in the
middle of the Squiz sniff's `processMultiLineArray()`.

**Auto-fixable:** yes. The fix starts the value on a new line, and `Generic.Arrays.ArrayIndent` indents it.

**Good:**

```php
$handlers = [
    #[Pure] static fn(int $x): int => $x,
    #[Pure] static fn(int $x): int => $x * 2,
];
```

**Bad:**

```php
$values = [1,
    2, 3,
];
```

---

### PERCS31.Attributes.AttributePlacement

Requires an attribute to be on the line before its target, as [PER Coding Style 3.1, Section 12.2](https://www.php-fig.org/per/coding-style/#122-placement) requires. The exception
is a parameter in a single-line parameter list, whose attribute must be inline.

A closure or arrow function isn't a structure in section 12.2's list. As a call's argument, its attribute follows the
rule for a parameter:

- **Argument list on one line:** inline, after a single space, e.g. `array_map(#[Pure] static fn($x) => $x, $items)`. A
  closure split over lines doesn't split the list, as [section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says, so
  `usort($items, #[Pure] static function ($a, $b) {` keeps its attribute inline.
- **Argument list split:** on its own line before the closure, indented the same.

A named argument's closure keeps its attribute inline, even in a split list, as `callback: #[Pure] function () {`.
Section 4.7 requires a single space between the colon and the value, and PHP doesn't allow the attribute before the
name.

Anywhere else, such as in an assignment or an array, PER says nothing, so it isn't checked.

An enum case isn't in section 12.2's list either. It's treated as a constant, so its attribute goes on the line
before, and `#[Label] case Active;` is reported, and fixed. Nor is a property hook:

- **A hook with a body in braces** is laid out like a method, so its attribute goes on the line before. `#[Label] get {`
  is reported, and fixed.
- **A short hook, or one with no body,** fits on a line, so `#[Label] get => 'a';` and `#[Label] get;` pass, as does the
  attribute on the line before.

A hook's parameter follows the parameter rule.

With two or more attribute blocks, the target goes on the line after them, as each block must be on its own line. So
`#[A] #[B] case X;` becomes three lines. [`PERCS31.Attributes.DisallowMultipleAttributesPerLine`](#percs31attributesdisallowmultipleattributesperline) puts each block on
its own line. One block holding several attributes can stay inline on a short hook, as in `#[A, B] get => 'a';`.

A single-line parameter or argument list is the exception, as section 12.2 only separates blocks "in a multi-line
context". There, two or more blocks stay inline, each followed by a single space, so `function f(#[A] #[B] int $c) {}`
passes, and `function f(#[A]  #[B] int $c) {}` is reported and fixed.

When code comes before the blocks on their line, the blocks after the first, and the target, are indented one level
more, as a continuation of the statement:

```php
$pure = #[A]
    #[B]
    static fn($x) => $x;
```

On a short hook, or a closure outside a call, an attribute on its target's line is followed by a single space, as
section 12.2 requires for a parameter in a single-line list. So `#[Label]  get => 'a';` is reported, and fixed.

**Why:** `SlevomatCodingStandard.Attributes.AttributeAndTargetSpacing` covers some of this, but its only option for an
attribute on its target's line, `allowOnSameLine`, is all or nothing, and its fix for a blank line deletes a comment
between the attribute and its target. So this sniff checks placement itself, and the PERCS31 ruleset doesn't use the
Slevomat sniff.

An attribute on an anonymous class follows [PER Coding Style 3.1, Section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes) instead. The attributes start on the line
after `new`, indented once, with `class`, or `readonly class`, on the line after them at the same indent.
`PERCS31.WhiteSpace.ScopeIndent` then indents the class body from `class`.

It also reports a blank line between a docblock and the attributes after it, between two attribute blocks, or between
the last attribute and its target, and anything but a single space between an attribute and its parameter on the same
line, as section 12.2 requires. A comment on a line of its own between an attribute and its target is reported too, as
the attributes go immediately prior to the structure. A comment at the end of the attribute's line, as in
`#[Override] // Stringable`, is fine. Section 12.2 only says "prior to the parameter", but a comment before a parameter
is reported too. A blank line between a parameter and the next parameter's attributes is allowed, as 12.2 also says.

**Auto-fixable:** yes, unless a comment is between the attribute and its target. For an anonymous class, the fix puts
each attribute block on its own line.

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

### PERCS31.Attributes.DisallowMultiLineAttributesJoining

Disallows several attributes in one attribute block when the block spans more than one line. A block on one line, such
as `#[Attribute, Deprecated]`, is allowed. This replaces `SlevomatCodingStandard.Attributes.DisallowAttributesJoining`.

**Why:** [PER Coding Style 3.1, Section 12.3](https://www.php-fig.org/per/coding-style/#123-compound-attributes) allows several attributes in one block, but says that if the list is split
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

### PERCS31.Attributes.DisallowMultipleAttributesPerLine

Requires separate attribute blocks on separate lines. This replaces
`SlevomatCodingStandard.Attributes.DisallowMultipleAttributesPerLine`, which it extends.

**Why:** [PER Coding Style 3.1, Section 12.2](https://www.php-fig.org/per/coding-style/#122-placement) says two separate attribute blocks MUST be on separate lines. The Slevomat
sniff checks this, with three problems:

- It reports blocks in a single-line parameter or argument list, as in `function f(#[A] #[B] int $c) {}`. Section 12.2
  only separates blocks "in a multi-line context", and requires a parameter's attribute inline in a single-line list,
  so this sniff skips that case. [`AttributePlacement`](#percs31attributesattributeplacement) checks the single space after each block.
- It misses blocks on a property hook, as PHP_CodeSniffer doesn't know about hooks. This sniff reports them.
- When code comes before the blocks on their line, as in `$pure = #[A] #[B]`, its fix lines the next block up with the
  line. This sniff indents it one level more, as a continuation of the statement.

**Auto-fixable:** yes, unless there's a comment between the blocks.

**Good:**

```php
#[A]
#[B]
final class Marker {}
```

**Bad:**

```php
#[A] #[B]
final class Marker {}
```

---

### PERCS31.Classes.AnonClassOpeningBrace

Requires one space before an anonymous class's opening brace when it's on the declaration's line, and nothing after the
brace on its line. This extends `Generic.Classes.OpeningBraceSameLine`.

**Why:** [PER Coding Style 3.1, Section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes) says anonymous classes MUST follow the same guidelines as closures, and its
examples show one space before the brace, and the body on the next line. `PSR12.Classes.AnonClassDeclaration` only
checks which line the brace is on. The Generic sniff makes both checks, but only for a class, interface, trait or enum,
and has no property to add to those. So this subclass changes one thing: it registers an anonymous class instead.

The parent also requires the brace on the declaration's line, as `BraceOnNewLine`. Section 8 allows the next line, and
requires it after a wrapped list of interfaces, so the ruleset excludes that code. An empty body, `{}`, is left to
[PERCS31.Formatting.EmptyBody](#percs31formattingemptybody). A comment after the brace is reported too, as it is for a closure.

**Auto-fixable:** yes, as the parent.

**Good:**

```php
$counter = new class extends Base {
    public int $count = 0;
};
```

**Bad:**

```php
$counter = new class extends Base{
    public int $count = 0;
};

$counter = new class { public int $count = 0;
};
```

---

### PERCS31.Classes.ClassDeclaration

Checks the declaration of a class, interface, trait or enum, and the placement of its braces, except in an empty body
written as `{}` on one line, e.g. `class Foo {}`. This replaces `PSR2.Classes.ClassDeclaration`.

**Why:** `PSR2.Classes.ClassDeclaration` rejects an empty body written as `{}` on one line, which
[PER Coding Style 3.1, Section 4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) recommends. This sniff skips that case, and leaves everything else to the parent. For an
empty body, the checks on `extends` and `implements` still run. See [PERCS31.Formatting.EmptyBody](#percs31formattingemptybody).

It also requires a backed enum's type in lower case, as in `enum Suit: string`, as [Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says all types MUST be in
lower case. `Generic.PHP.LowerCaseType` doesn't check a backing type.

**Auto-fixable:** yes.

---

### PERCS31.Classes.ClassInstantiationNoBrackets

Warns about parentheses around a class instantiation that's immediately dereferenced with `->`, `?->` or `::`, such as
`(new Foo())->bar()`.

**Why:** [PER Coding Style 3.1, Section 4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says the instantiation SHOULD NOT be wrapped in parentheses, and PHP 8.4 allows
`new Foo()->bar()`. As it's a SHOULD, this is a warning. `SlevomatCodingStandard.PHP.UselessParentheses` has a similar
check, but it misses `::`, and removes the parentheses from `(new Foo() ?: new Bar())->baz()`, changing its meaning.
This sniff only reports parentheses holding exactly one `new` expression. A `new` without an argument list, such as
`(new Foo)->bar()`, is left until `PSR12.Classes.ClassInstantiation` has added the `()`, as PHP needs them.

**Auto-fixable:** yes, unless a comment is between the parentheses and the expression.

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

### PERCS31.Classes.ClosingBrace

Disallows a comment or statement after the closing brace of a class, method or function, and after a property's hook
list or hook body. This replaces `PSR12.Classes.ClosingBrace`.

**Why:** [PER Coding Style 3.1, Section 4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says any closing brace MUST NOT be followed by any comment or statement on the
same line. The PSR-12 sniff checks classes, interfaces, traits, enums and functions, but not a property hook, as
PHP_CodeSniffer doesn't know about hooks. So this sniff also checks a hook list's `}` and each hook body's, as section 4
covers properties. Here, only a comment after one is reported, as [`PERCS31.Classes.PropertyHookLayout`](#percs31classespropertyhooklayout) reports code
after one. That includes after a promoted property's hook list, as in `{ set => trim($value); }, // Comment`. A phpcs
annotation is allowed, as it only works on that line.

An anonymous class's, a closure's and a control structure's closing brace aren't checked. Each is part of a larger
expression or statement, and what completes it, such as `};`, follows.

**Auto-fixable:** no.

**Good:**

```php
final class Counter
{
    public int $count = 0;
}
```

**Bad:**

```php
final class Counter
{
    public int $count = 0;
} // Counts calls.
```

---

### PERCS31.Classes.ConstantDeclaration

Requires a single space after a class constant's `const` keyword, and between its type and its name. A line break
counts as more than a single space.

**Why:** [PER Coding Style 3.1, Section 4.6](https://www.php-fig.org/per/coding-style/#46-modifier-keywords) says keywords MUST be on a single line, separated by a single space, and
[section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says there MUST be a space between a property's type and its name. Neither names a constant's type or name,
but a constant is declared like a property, so the same spacing is required. See [PER-CS 3.1 Judgement Calls](PER-CS_3.1_Judgement_Calls.md#spacing-in-a-constant-declaration).

`Squiz.WhiteSpace.ScopeKeywordSpacing` checks the space after a visibility or `final`, but not after `const` or the
type. `SlevomatCodingStandard.Classes.PropertyDeclaration` makes these checks for a property only, and
`SlevomatCodingStandard.Whitespaces.DuplicateSpaces` reports every run of spaces, which PER allows around operators.
No inherited sniff checks a constant alone.

A global constant isn't checked, as section 4 is about classes.

**Auto-fixable:** yes, unless a comment is in the way.

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
    public const  int  LIMIT = 10;
}
```

---

### PERCS31.Classes.EnumMemberVisibility

Requires a non-public method or constant in an enum to be `private`, not `protected`.

**Why:** [PER Coding Style 3.1, Section 9](https://www.php-fig.org/per/coding-style/#9-enumerations) says non-public methods and constants in an enum MUST use `private`, as enums
don't support inheritance. PER 3.0 only covered methods. No inherited sniff checks it.

**Auto-fixable:** yes. The fix doesn't change behaviour, since nothing can extend an enum. It also removes `final` if
present, as PHP warns that a private method can't be final, and rejects a final private constant.

**Good:**

```php
enum Suit
{
    case Hearts;

    private const string Red = 'red';

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

    protected const string Red = 'red';

    protected function symbol(): string
    {
        return '♥';
    }
}
```

---

### PERCS31.Classes.PromotedPropertyHooks

Allows a property hook on a promoted constructor parameter only if it's a single hook with a short `=>` body, on one
line. Otherwise, declare the property in the class body.

**Why:** [PER Coding Style 3.1, Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) says hooks on a constructor-promoted property MUST be only a single hook,
with a short-syntax body, defined on a single line, and that otherwise the property MUST NOT have hooks. No inherited
sniff checks it.

**Auto-fixable:** no. Moving a property and its hooks into the class body is a bigger rewrite than a fixer should risk.

**Good:**

```php
class User
{
    public function __construct(
        public string $name { set => trim($value); },
    ) {}
}
```

**Bad:**

```php
class User
{
    public function __construct(
        public string $name {
            set => trim($value);
            get => ucfirst($this->name);
        },
    ) {}
}
```

---

### PERCS31.Classes.PropertyDeclaration

Verifies that properties are declared correctly. This replaces `PSR2.Classes.PropertyDeclaration`, which it extends.

**Why:** PSR-12's `PSR2.Classes.PropertyDeclaration` misreads variables in PHP 8.4 property hook bodies, such as
`$value` and `$this`, as property declarations, and reports false errors on them, so this sniff skips them. The ruleset
excludes the parent's checks of modifier order, the spacing after the type, and several properties in one statement,
which Slevomat sniffs make. The last of those, `Multiple`, must stay excluded for a second reason: the parent reads a
hook list as a second property, so it reports every property with hooks. It also excludes the parent's `Underscore`
warning, which this sniff replaces with a `LeadingUnderscore` error, as section 4.3 says MUST NOT. The parent doesn't
check a promoted constructor property either, which [PER Coding Style 3.1, Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants)'s rules on underscores and
visibility cover. This sniff also checks that `get` comes before `set` in an abstract or interface property's hooks,
which no inherited sniff does.

A promoted constructor parameter with no visibility, such as `readonly int $a` or PHP 8.5's `final int $a`, is a
`ScopeMissing` error too, as section 4.3 says visibility MUST be declared on all properties. A set-visibility alone, as
in `private(set) int $a`, is allowed, as the public get-visibility MAY then be left out.

**Auto-fixable:** only `HookOrder`, unless there's a comment between the hooks.

It reports:

- A property name with a leading underscore (`LeadingUnderscore`), for any visibility, including promoted constructor
  parameters. Section 4.3 says "a single underscore", so a double one, as in `$__a`, passes, as it does on a method.
- The `var` keyword (`VarUsed`).
- A property with no visibility (`ScopeMissing`).
- `set` before `get` in a property's hooks (`HookOrder`). [PER Coding Style 3.1, Section 4.10](https://www.php-fig.org/per/coding-style/#410-interface-and-abstract-properties) requires `get` first for an
  abstract or interface property with no hook bodies, e.g. `{ get; set; }`, and every example in section 4.9 has `get`
  first too. So every hook list is checked, including ones with bodies, `&` or attributes.

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

interface Named
{
    public string $name { get; set; }
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

interface Named
{
    // The set hook before the get hook.
    public string $name { set; get; }
}
```

---

### PERCS31.Classes.PropertyHookLayout

Checks the layout of a property's hook list: its braces, and the `=>` of a short hook.

**Why:** [PER Coding Style 3.1, Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) sets the layout of property hooks, and no inherited sniff knows about them.
This sniff checks:

- The opening brace is on the same line as the property, after a single space.
- In the long form, the closing brace is on its own line. Code after it on its line, such as the next property, is
  reported as `CodeAfterCloser`. A comment after it is reported by [`PERCS31.Classes.ClosingBrace`](#percs31classesclosingbrace).
- In a list of short hooks over several lines, each hook starts on its own line, as only a single hook whose
  expression doesn't wrap may be inline. So `{ get => 1;` followed by `set => $value;` on the next line is reported.
- In a list with a `{}` body, each hook starts on its own line, each body starts on the line after its `{`, and each `}`
  is first on its line. An empty body, `{}`, has nothing to move, and a comment may follow the `{`.
- In a list over several lines, there's at most one blank line between hooks. Section 4.9 says hooks MAY be separated by
  an additional blank line, and its example shows them with none and with one.
- A short hook's `=>` has a single space either side, and its body starts on the same line.
- A short hook listed inline has a single space after the opening brace and before the closing one.
- A list with no bodies, as on an interface or abstract property, is on one line, written as `{ get; set; }`: a single
  space inside each brace, no space before each `;`, and a single space between operations ([Section 4.10](https://www.php-fig.org/per/coding-style/#410-interface-and-abstract-properties)).
- A bodiless hook in a list with bodies has no space before its `;`.

Indentation is checked by `PERCS31.WhiteSpace.ScopeIndent`, and several statements on a line by
`PERCS31.Formatting.DisallowMultipleStatements`. A hook on a promoted constructor parameter is reported by
`PERCS31.Classes.PromotedPropertyHooks`.

Section 4.9's text doesn't say where a hook's own `{` goes, how a `set` hook's parameter list is spaced, or whether a
by-reference hook's `&` is followed by a space. Its examples show `get {` and `set(string $value)`, and section 4.5 puts
no space after a by-reference parameter's `&`. So this sniff also requires:

- Exactly one space before a hook's own `{`, as in `get {`.
- A `set` hook's parameter list spaced like a method's: no space after `set`, and none just inside the parentheses.
- No space after a by-reference hook's `&`, as in `&get`.

**Auto-fixable:** yes, except a comment after the closing brace, or a comment in the way.

**Good:**

```php
class User
{
    public string $name {
        get => ucfirst($this->name);
        set => trim($value);
    }

    public string $email { get => strtolower($this->email); }
}

interface Named
{
    public string $name { get; set; }
}
```

**Bad:**

```php
class User
{
    public string $name
    {
        get  =>  ucfirst($this->name);
        set =>
            trim($value); }

    public string $email {get => strtolower($this->email);}

    public string $phone {
        get { return $this->phone; }
    }

    public string $city { get { return $this->city; } }
}

interface Named
{
    public string $name {
        get ;
        set;
    }
}
```

---

### PERCS31.ControlStructures.BodyOnNextLine

Requires a control structure's body to start on the line after its opening brace, allowing an empty body as `{}`. This
replaces the `NewlineAfterOpenBrace` check of `Squiz.ControlStructures.ControlSignature`.

**Why:** [PER Coding Style 3.1, Section 5](https://www.php-fig.org/per/coding-style/#5-control-structures) says the body MUST be on the next line after the opening brace. It says nothing
about an empty body, which has nothing to put on the next line, so `catch (Exception $e) {}` is allowed, as `{}` is for
an empty class, function or closure. The braces on separate lines are allowed too.

The Squiz check reports `{}`. It's in the middle of that sniff's `process()`, with no property to change it, so a
subclass can't skip it for one case. So the ruleset excludes its code, and this sniff makes the same check, copied from
PHP_CodeSniffer 4.0.4 with its message and code. It changes one thing: a `}` straight after the `{` isn't reported. No
other installed sniff makes the check with that exception.

A comment after the brace on its line is allowed, as by the Squiz check. A space between the braces, `{ }`, isn't `{}`,
so it's reported. The [Jumbo standard](Jumbo.md) requires `{}` for an empty body.

**Auto-fixable:** yes.

**Good:**

```php
try {
    run();
} catch (Exception $e) {}

while (next($items)) {
    count();
}
```

**Bad:**

```php
while (next($items)) { count();
}
```

---

### PERCS31.ControlStructures.ControlStructureSpacing

Checks the layout of a control structure's parentheses, as `PSR12.ControlStructures.ControlStructureSpacing` does,
except for a multi-line `foreach`. This extends that sniff.

**Why:** [PER Coding Style 3.1, Section 5.5](https://www.php-fig.org/per/coding-style/#55-foreach) has no rule for an expression split inside a `foreach`'s parentheses, unlike
`if`, `switch`, `while`, `do while` and `for`. The parent applies one anyway, so it rejects an array in the header.
Moving the array down then breaks section 11, which forbids `[` on a line of its own.

For a multi-line `foreach`, this sniff only checks what section 5 requires of every control structure: no space after
the `(` or before the `)`.

**Auto-fixable:** yes.

**Good:**

```php
foreach ([
    'first',
    'second',
] as $value) {
    echo $value;
}
```

**Bad:**

```php
foreach ( [
    'first',
    'second',
] as $value ) {
    echo $value;
}
```

---

### PERCS31.ControlStructures.MatchArmPlacement

Requires each `match` arm to start on a new line. Several conditions in one arm, as in `1, 2, 3 => 'a',`, are one arm.

**Why:** [PER Coding Style 3.1, Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match) says a `match` expression looks like its example, which has one arm per line.
No inherited sniff checks it.

**Auto-fixable:** yes, except with a comment in the way. The arm moves to the next line, and
`PERCS31.WhiteSpace.ScopeIndent` indents it.

**Good:**

```php
$label = match ($code) {
    200, 201 => 'OK',
    default => 'Error',
};
```

**Bad:**

```php
$label = match ($code) {
    200, 201 => 'OK', default => 'Error',
};
```

---

### PERCS31.ControlStructures.MultiLineTernaryOperator

Requires a ternary split at `?` or `:` to take three lines, with `?` and `:` each starting a line, indented once. This
replaces `SlevomatCodingStandard.ControlStructures.DisallowTrailingMultiLineTernaryOperator`.

**Why:** [PER Coding Style 3.1, Section 6.4](https://www.php-fig.org/per/coding-style/#64-operators-placement) says a multi-line ternary MUST occupy three lines, never two, with each
operator at the start of its line. The Slevomat sniff only reports a `?` at the end of a line. No inherited sniff
reports a ternary on two lines, a trailing `:`, or the wrong indentation. A short ternary (`?:`) is a binary operator in
PER, so it isn't checked here. Nor is the `?` of a nullable type in a `set` hook's parameter, which PHP_CodeSniffer
reads as a ternary's.

A ternary is split if there's a line break right before or after its `?` or `:`. A value or condition split over lines
doesn't split it, so it's left alone, as other binary operators are.

A ternary nested in another's middle operand is checked as a ternary of its own, so it's indented once past the line it
starts on:

```php
$label = $isActive
    ? $isAdmin
        ? 'Admin'
        : 'User'
    : 'Inactive';
```

The indentation is always 4 spaces, as [PER Coding Style 3.1, Section 2.4](https://www.php-fig.org/per/coding-style/#24-indenting) requires. This sniff doesn't use the `indent`
property of `PERCS31.WhiteSpace.ScopeIndent`.

**Auto-fixable:** yes, unless a comment is next to `?` or `:`.

**Good:**

```php
$label = $isActive
    ? 'Active'
    : 'Inactive';

$options = $isActive ? [
    'active' => true,
] : [];
```

**Bad:**

```php
$label = $isActive
    ? 'Active' : 'Inactive';

$label = $isActive ? 'Active' :
    'Inactive';

$options = $isActive ? [
    'active' => true,
]
    : [];
```

---

### PERCS31.ControlStructures.SwitchDeclaration

Checks `switch` statements. This replaces `PSR2.ControlStructures.SwitchDeclaration`, and adds these rules:

- The last non-empty case MUST end with a terminating statement, such as `break`, `return` or `throw`.
- A multi-line case condition MUST be wrapped in parentheses, with `):` on a line of its own.
- A non-empty `default` that continues into the next case MUST have a comment saying so, as a `case` must.

**Why:** [PER Coding Style 3.1, Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match) sets these rules. The parent only checks a case that's followed by another,
and doesn't check multi-line conditions.

The parent's own `BodyOnNextLineCASE` and `BodyOnNextLineDEFAULT` codes report code on a label's line, and a blank line
straight after a label, which section 5.2's example, the template for its rules, doesn't have. A comment on a label's
line is allowed, as in `case 1: // The first.`

A multi-line `case` condition must also follow the rule for any expression split inside parentheses. The first condition
must be on the line after `(`, and each line must be indented at least once past the `case`.

**Auto-fixable:** yes. The fix adds `break;` before the `}`, adds the parentheses, moves the `)` to its own line, moves
the first condition to the next line, or indents a line. It's left to a person when there's a comment in the way, or the
`}` follows code on its line. A `default` that falls through isn't fixable, as only a person knows if that's intended.

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

### PERCS31.Files.DeclareStatement

Checks the format of a declare statement, including one with several directives or a string value. This is a fork of
`PSR12.Files.DeclareStatement`.

**Why:** [PER Coding Style 3.1, Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements) says declare statements MUST NOT contain any spaces inside the parentheses. The
original assumes a single directive with an integer value, so it rejects `declare(ticks=1,strict_types=1);` and
`declare(encoding='UTF-8');`, however they're spaced, and its later checks, such as for a block declare's braces, then
look in the wrong place. The fork checks each directive in turn, with any value, so every check works for every declare
statement, including a block declare with several directives. Its codes and messages are the original's. It's temporary,
until PHP_CodeSniffer handles several directives.

The original's fix for the space before a block declare's `{` never ends on `){`, with no space at all. The fork's fix
removes whatever is between them, then adds one space.

The original's check that a block declare's closing brace is on a new line is removed, as
`PERCS31.WhiteSpace.ScopeClosingBrace` makes it. With both fixes, the result had a blank line before the brace.

**Auto-fixable:** yes.

**Good:**

```php
declare(ticks=1,strict_types=1);
```

**Bad:**

```php
declare(ticks=1, strict_types=1);
```

---

### PERCS31.Files.FileHeader

Checks the blocks of the file header, and the blank lines between them. This replaces `PSR12.Files.FileHeader`, and
allows a `declare` statement between its own PHP tags on the first line of a file with markup.

**Why:** [PER Coding Style 3.1, Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements) says that to declare strict types in a file containing markup outside PHP tags,
the declaration MUST be on the first line, with its own opening and closing tags. The parent reads that line as a
header, and requires a blank line after the tag and after the declaration, so it rejects PER's own example. This sniff
skips such a file, as the parent skips other files that mix PHP and markup. The `;` before the `?>` may be included or
left out.

In a file with markup, a `strict_types` declaration anywhere else is reported, as `StrictTypesNotOnFirstLine`. If it's
all that's in its PHP block, the fix collapses the block onto the first line. Otherwise, it adds
`<?php declare(strict_types=1) ?>` as the first line, and removes the declaration and the blank line after it. A file
starting with a shebang line can't have the first-line form, so it isn't checked.

Section 3 also lists "One or more declare statements" as one block, with no blank line in it. The parent ends the header
at a block declare, and wants a blank line before one. This sniff counts one as a declare statement, so there's no blank
line between it and a declare statement before it.

A file with markup before its first `<?php`, such as a template, has no file header, so its blocks and blank lines
aren't checked. The parent already skips a file that mixes PHP and markup when it has several PHP tags, but with only
one, it reported a `declare` after the markup as a misplaced header, and its fixes looped. An import in such a file is
still reported, as it can't be at the top of the file.

Section 3 also says the opening tag on the first line MUST be on its own line "unless it is a file containing markup
outside of PHP opening and closing tags". So a template may start with `<?php require 'header.php'; ?>`, or a docblock
between tags. With only that one PHP tag in the file, the parent read the line as a header, and its fix split the tag
over three lines. This sniff skips the header's spacing checks in a file with markup whose first tag shares its line.

The parent ends a `use` statement at its first comma, so it reports `use A, B;` as missing a blank line before `B`.
Section 3 doesn't forbid several imports in one statement, so this sniff reads the statement to its semicolon.

Section 3 puts the imports before the rest of the code, and says that in a file with markup they MUST be at the top. The
parent only looks at the header, so this sniff reports an import after the rest of the code, as `ImportAfterCode`. A
file with more than one namespace is skipped, as each namespace has its own imports.

**Auto-fixable:** yes, as the parent, except `ImportAfterCode`, as moving an import can change what a name resolves to.

**Good:**

```php
<?php declare(strict_types=1) ?>
<html>
<body>
    <?php echo $title ?>
</body>
</html>
```

---

### PERCS31.Files.LineLength

Warns about a line over 120 characters, and, at a severity of 4, about one over 80. This replaces
`Generic.Files.LineLength`.

**Why:** [PER Coding Style 3.1, Section 2.3](https://www.php-fig.org/per/coding-style/#23-lines) says the soft limit MUST be 120 characters, with no hard limit, and that
lines SHOULD NOT be longer than 80. The parent has one warning threshold, so it can't check both. The 80-character
warning's severity of 4 is below `phpcs`'s default of 5, so it's only shown with `--warning-severity=4`. A line is
measured as by the parent, and gets at most one warning. See [Line Length](LineLength.md).

**Auto-fixable:** no. Only a person can choose where to split a line.

**Configuration:**

| Property               | Type | Default | Description                                                                         |
| ---------------------- | ---- | ------- | ----------------------------------------------------------------------------------- |
| `lineLimit`            | int  | 120     | The soft limit, over which a line gets a `TooLong` warning.                         |
| `recommendedLineLimit` | int  | 80      | The limit over which a line gets an `OverRecommended` warning, or 0 to turn it off. |
| `absoluteLineLimit`    | int  | 0       | A hard limit, over which a line gets an error. PER says there MUST NOT be one.      |

---

### PERCS31.Formatting.DisallowMultipleStatements

Requires each statement to be on a line by itself. This replaces `Generic.Formatting.DisallowMultipleStatements`.

**Why:** the Generic sniff treats the operations of an abstract or interface property, `{ get; set; }`, as two
statements on one line. [PER Coding Style 3.1, Section 4.10](https://www.php-fig.org/per/coding-style/#410-interface-and-abstract-properties) requires them on one line. This sniff skips a semicolon that
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

### PERCS31.Formatting.EmptyBody

Reports an empty class, interface, trait, enum, function, method, closure or anonymous class body that isn't written as
`{}` on the same line as the declaration, after a single space. It's a warning, except for a closure or anonymous class,
where it's an error.

**Why:** [PER Coding Style 3.1, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says an empty class or function body SHOULD be abbreviated that way,
including a constructor that only promotes properties. [Section 7](https://www.php-fig.org/per/coding-style/#7-closures) says an empty closure body MUST be, and [section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes) says
an anonymous class MUST follow the same rules as a closure. No inherited sniff checks any of these. A body containing a
comment isn't empty.

Section 5 has no rule for a control structure's empty body, so it isn't checked: `catch (Exception $e) {}` passes, and
so does the same with the braces on separate lines. Set `checkControlStructures` to require `{}` there too, as an error,
as the [Jumbo standard](Jumbo.md) does.

**Auto-fixable:** yes. After a block comment, the fix puts `{}` after it, as in `function a() /* Why. */ {}`. A body
after a `//` or `#` comment is reported but not fixed, as `{}` can't go on the comment's line.

**Good:**

```php
final class NotFoundException extends Exception {}

final class Point
{
    public function __construct(public int $x, public int $y) {}

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
    public function __construct(public int $x, public int $y)
    {
    }

    public function reset(): void{}
}

$noop = static function (): void {
};
```

**Configuration:**

| Property                 | Type | Default | Description                                                     |
| ------------------------ | ---- | ------- | --------------------------------------------------------------- |
| `checkControlStructures` | bool | false   | Require `{}` for a control structure's empty body, as an error. |

---

### PERCS31.Formatting.MethodChaining

Requires the first method of a method chain split across lines to be on the next line, not on the line with the object.
Property accesses before the first method, as in `$this->query`, can stay on that line.

**Why:** [PER Coding Style 3.1, Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says that when method chaining is put on separate lines, the first method
MUST be on the next line. `PEAR.WhiteSpace.ObjectOperatorIndent` checks that each line is indented once, but lets the
first method stay on the object's line.

**Auto-fixable:** yes. The fix moves the first method to the next line, indented once.

**Good:**

```php
$someInstance
    ->create()
    ->prepare()
    ->run();

$query = $this->em
    ->createQueryBuilder()
    ->select('u');

$name = $user->getName();
```

**Bad:**

```php
$someInstance->create()
    ->prepare()
    ->run();
```

---

### PERCS31.Formatting.TrailingComma

Requires a trailing comma after the last arm of a multi-line `match`, the last name in a multi-line group `use`, the
last item in a multi-line `list()`, or the last argument in multi-line arguments to an anonymous class, `clone()`,
`exit()` or `die()`, and disallows one when the list is on a single line. It also disallows one in a `match` arm's
conditions on a single line, e.g. `1, 2, => 'a'`.

**Why:** [PER Coding Style 3.1, Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas) says the last item of a list split across lines MUST have a trailing comma,
and the last item of a list on one line MUST NOT. Slevomat's trailing comma sniffs cover calls, declarations and closure
`use` lists, and Slevomat's array sniff covers arrays and short lists, but none covers `match`, group `use`,
`new class (`, `list()`, `clone()`, `exit()` or `die()`. `PhpCollective.PHP.DisallowTrailingCommaInSingleLine` reports a
comma in a single-line `list()`, `new class (`, `clone()` or `exit()`, but it goes by the comma's line, not the list's,
so it also reports a comma PER requires in a split list. A single argument split over lines, as in `clone($a, [` ...
`]);`, doesn't split the argument list, so a comma after it is reported, as
[`PERCS31.Functions.DisallowTrailingCommaInCall`](#percs31functionsdisallowtrailingcommaincall) does for a call.

**Auto-fixable:** yes. A missing comma goes after the last item, before any comment.

**Good:**

```php
use Vendor\Package\{
    ClassA,
    ClassB,
};
use Vendor\Other\{ClassC, ClassD};

$label = match ($code) {
    200, 201 => 'OK',
    default => 'Error',
};

$first = new class (1, 2) {};
$second = new class (
    1,
    2,
) {};
```

**Bad:**

```php
use Vendor\Package\{
    ClassA,
    ClassB
};
use Vendor\Other\{ClassC, ClassD,};

$label = match ($code) {
    200, 201, => 'OK',
    default => 'Error'
};

$first = new class (1, 2,) {};
$second = new class (
    1,
    2
) {};
```

**Configuration:**

| Property                         | Type | Default | Description                                                                 |
| -------------------------------- | ---- | ------- | --------------------------------------------------------------------------- |
| `disallowInSplitMatchConditions` | bool | false   | Disallow a comma before `=>` when an arm's conditions are split over lines. |

---

### PERCS31.Functions.ArrowFunctionSemicolonSpacing

Disallows a space or line break before the semicolon that ends an arrow function.

**Why:** [PER Coding Style 3.1, Section 7.1](https://www.php-fig.org/per/coding-style/#71-short-closures) says the semicolon at the end of the expression MUST NOT be preceded by a
space. `SlevomatCodingStandard.Functions.ArrowFunctionDeclaration` checks the spacing around `fn` and `=>`, but not
this. PER has no such rule for other statements, so `$a = 1 ;` isn't checked.

**Auto-fixable:** yes.

**Good:**

```php
$double = fn(int $x): int => $x * 2;
```

**Bad:**

```php
$double = fn(int $x): int => $x * 2 ;
```

---

### PERCS31.Functions.DisallowTrailingCommaInCall

Disallows a trailing comma after the last argument of a call, unless the argument list is split across lines. This
replaces `SlevomatCodingStandard.Functions.DisallowTrailingCommaInCall`, which it extends.

**Why:** [PER Coding Style 3.1, Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas) says the last item of a list MUST NOT have a trailing comma unless the list
is split across lines. [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says a single argument split across lines, such as an array or a closure, doesn't
split the argument list. So in `foo([` ... `],);` the list isn't split, and the comma isn't allowed.

An argument list is split when its first argument starts on the line after the `(`, or its `)` is on a line of its own.

The Slevomat sniff checks every call, or with its `onlySingleLine` property, only one whose parentheses are on the same
line, which misses `],);`. This subclass skips a split list, and leaves the rest to the parent, with `onlySingleLine`
off. [`PERCS31.Formatting.TrailingComma`](#percs31formattingtrailingcomma) applies the same rule to the arguments of an anonymous class, `clone()`,
`exit()` and `die()`.

**Auto-fixable:** yes, as the parent.

**Good:**

```php
$total = sum([
    1,
    2,
]);

$total = sum(
    [1, 2],
    $precision,
);
```

**Bad:**

```php
$total = sum([
    1,
    2,
],);

$total = sum($first, $second,);
```

---

### PERCS31.Functions.FunctionCallLayout

Checks the spacing and layout of a function call's argument list, including `clone()`'s. This replaces
`PSR2.Methods.FunctionCallSignature`, which it extends.

**Why:** [PER Coding Style 3.1, Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) sets the rules for a call: no space before or just inside the parentheses,
and when split, the first argument on the next line and one per line. The parent checks those, with two gaps:

- It doesn't check `clone()`, which section 4.7 calls a function. This sniff does. Parentheses that may only group the
  object, as in `clone ($a)->b`, aren't an argument list. Nor are parentheses after a space, as in `clone ($a)`, which
  [`PERCS31.PHP.RequireCloneParentheses`](#percs31phprequirecloneparentheses) reports.
- It reads a `set` hook's parameter list, as in `set (string $value)`, as a call to `set()`. That list is part of the
  hook's declaration, so this sniff skips it, and [`PERCS31.Classes.PropertyHookLayout`](#percs31classespropertyhooklayout) checks it instead.
- It reads the `...` of a first-class callable split over lines, as in `foo(` then `...` then `)`, as an argument on a
  line of its own, which is how a split argument list is written. [Section 4.8](https://www.php-fig.org/per/coding-style/#48-function-callable-references) says the `...` MUST NOT have whitespace
  before or after it, so this sniff reports it as `SplitFirstClassCallable`, and fixes it to `foo(...)`. With a comment
  in the parentheses, it's reported but not fixed. On one line, the parent already reports `foo( ... )`.

It's named Layout, not Signature like its parent, as a function's signature is its declaration. PSR-12 turns off the
parent's `SpaceAfterCloseBracket` and `OpeningIndent` codes, so they're off here too. A blank line between arguments,
or before the closing parenthesis, is reported as `EmptyLine`, as 4.7's examples, which "show correct argument usage",
have none.

**Auto-fixable:** yes, as the parent.

**Good:**

```php
$copy = clone($original, [
    'name' => 'Copy',
]);

$length = strlen(...);
```

**Bad:**

```php
$copy = clone( $original );
$copy = clone($original,
    ['name' => 'Copy'],
);

$length = strlen(
    ...
);
```

---

### PERCS31.Functions.MultiLineFunctionDeclaration

Checks the layout of function, closure and arrow function declarations, including the placement of the opening brace,
except in an empty body written as `{}` on one line, e.g. `function reset(): void {}`. This replaces
`Squiz.Functions.MultiLineFunctionDeclaration`.

**Why:** `Squiz.Functions.MultiLineFunctionDeclaration` rejects an empty body written as `{}` on one line, which [PER
Coding Style 3.1, Sections 4.4 and 7](https://www.php-fig.org/per/coding-style/#7-closures) requires for a closure and
recommends for a function. This sniff skips that case, and leaves everything else to the parent. For an empty body after
a single-line signature, the parent's `CloseBracketNewLine` check is skipped too, as it can't be run on its own. See
[PERCS31.Formatting.EmptyBody](#percs31formattingemptybody).

The parent also reports every blank line in a multi-line parameter or `use` list, but [section 12.4](https://www.php-fig.org/per/coding-style/#124-example)'s example has one
between two parameters, both before an attribute and before a parameter with none. So the ruleset excludes the parent's
`EmptyLine` code, and this sniff reports blank lines itself, as `BlankLine`. A single blank line between parameters is
allowed, as the example shows. Any other is reported, such as two in a row, one straight after `(` or before `)`, one
after a parameter's attributes, or one between a closure's `use` items.

The parent reads a parameter list split only by an attribute, as in `function a(#[Sensitive]` then `$password)`, as a
single-line declaration, so the split wasn't reported. This sniff reads any list whose parentheses are on different
lines as multi-line, so the first parameter goes on the line after `(`, and `) {` on a line of their own, as
[section 4.5](https://www.php-fig.org/per/coding-style/#45-method-and-function-parameters) requires.

The parent doesn't check an arrow function. [Section 7.1](https://www.php-fig.org/per/coding-style/#71-short-closures) says an arrow function follows the same rules as a closure, so
this sniff checks its split parameter list too:

- The first parameter on the line after `fn(`.
- One parameter per line, each indented once.
- The closing parenthesis on a line of its own.
- Blank lines, as above.

An arrow function has no braces, so the brace checks don't apply.

The parent would also indent every line of a promoted parameter's multi-line hook list as a parameter, fighting
[`PERCS31.WhiteSpace.ScopeIndent`](#percs31whitespacescopeindent) until `phpcbf` fails. [`PERCS31.Classes.PromotedPropertyHooks`](#percs31classespromotedpropertyhooks) already reports that
code, which has to be rewritten, so this sniff leaves that parameter list alone.

The parent's fix for a closure's `use` on the line after the closing parenthesis never finished. One of its fixes added
a space before `use`, leaving it on its line, and another indented that line, each undoing the other until `phpcbf`
failed. This sniff reports that `use` itself, with the parent's `SpaceBeforeUse` code, and joins it to the
parenthesis's line, giving `) use ($var) {`. It's a workaround for a bug in `PEAR.Functions.FunctionDeclaration`, to
remove once that's fixed.

**Auto-fixable:** yes, as the parent.

---

### PERCS31.Functions.NullableTypeDeclaration

Disallows a space between the `?` of a nullable type and the type, wherever a type can appear. This replaces
`PSR12.Functions.NullableTypeDeclaration`, and the same check in `SlevomatCodingStandard.Classes.PropertyDeclaration`
(`WhitespaceAfterNullabilitySymbol`), which only covers a property.

**Why:** [PER Coding Style 3.1, Section 4.5](https://www.php-fig.org/per/coding-style/#45-method-and-function-parameters) says there MUST NOT be a space between the question mark and the type. The
PSR-12 sniff checks every position but one: PHP_CodeSniffer doesn't know about hooks, so the sniff doesn't see the `?`
in a `set` hook's parameter.

**Auto-fixable:** yes, unless a comment comes after the `?`.

**Good:**

```php
class Counter
{
    public ?int $count {
        set(?int $value) => $value;
    }
}
```

**Bad:**

```php
class Counter
{
    public ? int $count {
        set(? int $value) => $value;
    }
}
```

---

### PERCS31.Functions.RequireTrailingCommaInCall

Requires a trailing comma after the last argument of a call split across lines, but not after the `...` of a first-class
callable. This replaces `SlevomatCodingStandard.Functions.RequireTrailingCommaInCall`, which it extends.

**Why:** [PER Coding Style 3.1, Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas) says the last item of a list split across lines MUST have a trailing comma.
The Slevomat sniff checks that, but reads the `...` of a first-class callable as the last argument. So for one split
over lines, its fix adds a comma, giving `...,`, which is a parse error. It has no property to turn that off, so this
subclass skips a first-class callable, and leaves everything else to the parent.
[`PERCS31.Functions.FunctionCallLayout`](#percs31functionsfunctioncalllayout) reports the split, and fixes it to `foo(...)`.

**Auto-fixable:** yes, as the parent.

**Good:**

```php
$largest = max(
    $first,
    $second,
);
```

**Bad:**

```php
$largest = max(
    $first,
    $second
);
```

---

### PERCS31.Namespaces.GroupUseLayout

Requires a group `use` split across lines to have one name per line, with the first on the line after the `{`, and the
`}` on a line of its own. A group `use` on one line is left alone.

**Why:** [PER Coding Style 3.1, Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements) shows a group `use` on one line, and one split with a name on each line. Its
text sets no layout, so the two examples are read as the two allowed forms, as for a single-line and a multi-line array.
See [PER-CS 3.1 Judgement Calls](PER-CS_3.1_Judgement_Calls.md#a-group-use-split-over-lines). No inherited sniff checks which line a name is on.

`PERCS31.WhiteSpace.ConsistentIndent` checks each name's indent, and
[`PERCS31.Formatting.TrailingComma`](#percs31formattingtrailingcomma) the comma after the last name.

**Auto-fixable:** yes, unless a comment is in the way.

**Good:**

```php
use Vendor\Package\{ClassA, ClassB};
use Vendor\Other\{
    ClassC,
    ClassD,
};
```

**Bad:**

```php
use Vendor\Other\{ClassC,
    ClassD, };
```

---

### PERCS31.NamingConventions.AcronymCasing

Warns about an acronym in capitals in a name: a class, interface, trait, enum, enum case, enum constant, namespace,
function, method, property, parameter, variable, global constant, import alias or trait alias.

**Why:** [PER Coding Style 3.1, Section 2.7](https://www.php-fig.org/per/coding-style/#27-naming) RECOMMENDS treating acronyms like regular words.

The sniff splits a name into words, working back from its last character:

- Underscores separate parts, and each part is split on its own.
- If the last letter is lower-case, the word goes back to the first capital, which is included.
- If it's upper-case, the word goes back to the first character that isn't a capital, which isn't included.
- A digit stays with the word on its left, and separates capitals, so `B2B` is `B2` and `B`.

An acronym is a word of two or more capitals. So `HTTPClient`, `parseURL`, `getID`, `getURLs` and `IHeartAI` are all
reported.

It doesn't report:

- A single capital, which is an initial, not an acronym, as in `ClassA`, `EDayOfWeek`, `getX` or `getTType`. So `getIDs`
  passes, as it's `get`, `I` and `Ds`.
- A class, interface, trait, enum, enum case or constant with no lower-case letter, such as `FOO` or `HTTP_OK`. That's
  UPPER_CASE, not an acronym in a name. It's allowed for a global or enum constant, and [`ValidClassName`](#percs31namingconventionsvalidclassname) and
  [`ValidEnumCaseName`](#percs31namingconventionsvalidenumcasename) report it for the others.
- A class constant outside an enum, as UPPER_CASE is required.
- PHP's reserved variables, such as `$_SERVER`.
- A property where it's used, such as `$this->userID` or `self::$userID`, as it may belong to a class you don't control.
  It's reported where it's declared.
- A class where it's used, such as `new HTTPClient()`, for the same reason.

A name you can't change, such as a method overriding a parent's, is reported too. A variable is reported at each use.

The `excludedNames` property allows whole names, as a comma-separated list. Matching is case-sensitive, and a
variable's `$` is optional. Each part of a namespace counts as a name of its own, so `PERCS31` allows `PERCS31\Sniffs`,
but not `PERCS31\HTTP`. It suits a name used in many files, such as a namespace:

```xml
<rule ref="PERCS31.NamingConventions.AcronymCasing">
    <properties>
        <property name="excludedNames" value="PERCS31,legacyID"/>
    </properties>
</rule>
```

For a name declared once, a `phpcs:ignore` comment on its line does the same job.

**Auto-fixable:** no, as references to the name elsewhere wouldn't change.

**Good:**

```php
class HttpClient
{
    private int $userId = 0;

    public function getId(): int {}
}
```

**Bad:**

```php
class HTTPClient
{
    private int $userID = 0;

    public function getID(): int {}
}
```

---

### PERCS31.NamingConventions.UpperCaseConstantName

Requires class constant names in UPPER_CASE, except in an enum, where PascalCase is also allowed, and recommended. This
replaces `Generic.NamingConventions.UpperCaseConstantName`.

**Why:** PSR-1, which PER Coding Style builds on, requires class constants in UPPER_CASE, and the Generic sniff applies
that to every constant. [PER Coding Style 3.1, Section 9](https://www.php-fig.org/per/coding-style/#9-enumerations) says constants in an enum MAY use either PascalCase or
UPPER_CASE, and PascalCase is RECOMMENDED. So for an enum constant:

- PascalCase passes, as [`ValidClassName`](#percs31namingconventionsvalidclassname) defines it.
- UPPER_CASE gets an `EnumConstantNotPascalCase` warning. A name with no lower-case letter, such as `OK` or `A`, is
  UPPER_CASE.
- Anything else, such as camelCase or `Default_Colour`, gets an `EnumConstantInvalidCase` error.

An acronym in capitals in a PascalCase name, as in `HTTPStatus`, passes here. [`AcronymCasing`](#percs31namingconventionsacronymcasing) warns about it.

A global constant, from `const` or `define()`, isn't checked by default, as neither PSR-1 nor PER has a rule for it. Set
`checkGlobalConstants` to require UPPER_CASE of these, and of an alias of a constant, as in `use const Foo\BAR as BAZ;`.
It reports them with the `ConstantNotUpperCase` code:

```xml
<rule ref="PERCS31.NamingConventions.UpperCaseConstantName">
    <properties>
        <property name="checkGlobalConstants" value="true"/>
    </properties>
</rule>
```

Set `requireUpperSnakeCase` to require strict UPPER_SNAKE_CASE of every constant in upper case that the sniff checks:
words of capitals and digits, separated by single underscores. So `__HIDDEN`, `TWO__WORDS` and `TRAILING_` are
reported, as `ConstantNotUpperSnakeCase`. PER has no such rule, so it's off by default. The [Jumbo standard](Jumbo.md) turns it on.
A single leading underscore on a `const` is left to [`ValidConstantName`](#percs31namingconventionsvalidconstantname).

Other class, interface and trait constants are left to the parent.

**Auto-fixable:** no.

**Good:**

```php
const MAX_RETRIES = 3;

enum Suit: string
{
    case Hearts = 'h';

    public const string DefaultColour = 'red';
}
```

**Bad:**

```php
class Retry
{
    public const int maxRetries = 3;
}

enum Suit: string
{
    case Hearts = 'h';

    public const string defaultColour = 'red';
}
```

**Configuration:**

| Property                | Type | Default | Description                                                           |
| ----------------------- | ---- | ------- | --------------------------------------------------------------------- |
| `checkGlobalConstants`  | bool | false   | Require UPPER_CASE for a global constant, and an alias of a constant. |
| `requireUpperSnakeCase` | bool | false   | Require strict UPPER_SNAKE_CASE of a constant in upper case.          |

---

### PERCS31.NamingConventions.ValidClassName

Requires class, interface, trait and enum names to be in PascalCase: a capital first letter, only letters and digits,
and at least one lower-case letter. This replaces `Squiz.Classes.ValidClassName`.

**Why:** PSR-1 says class names MUST be in `StudlyCaps`, and [PER Coding Style 3.1, Section 2.1](https://www.php-fig.org/per/coding-style/#21-basic-coding-standard) reads that as PascalCase,
with the first letter of every word capitalized. [Section 4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says "class" covers classes, interfaces, traits and enums.
PascalCase mixes upper and lower case, so a name with no lower-case letter isn't in it. The Squiz sniff allows one, and
this sniff extends it to report one.

- **Passes:** `Client`, `HttpClient`, `Http2`, `ClassA`, `Vector3D`, `X509Certificate` and `IAnimal`.
- **Fails:** `FOO`, `A`, `C2`, `B2B`, `HTTP2`, `client` and `Foo_Bar`.

An acronym in capitals, as in `HTTPClient` or `XMLParser`, is still PascalCase, so it passes here.
[`AcronymCasing`](#percs31namingconventionsacronymcasing) reports it as a warning, as section 2.7 only RECOMMENDS writing an acronym like a word.

**Auto-fixable:** no. Renaming a class would break references to it in other files.

**Good:**

```php
class HttpClient {}

interface JsonParser {}
```

**Bad:**

```php
class http_client {}

interface JSON {}
```

---

### PERCS31.NamingConventions.ValidConstantName

Disallows a class constant name that starts with an underscore. A double underscore, as in `__A`, passes, as section
4.3 says "a single underscore", and it passes on a method.

A global constant isn't checked by default, as the rule is about showing visibility, which only a class constant has.
Set `checkGlobalConstants` to check a `const` statement at file scope too, as the [Jumbo standard](Jumbo.md) does. A constant made
with `define()` isn't checked.

**Why:** [PER Coding Style 3.1, Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says constant names MUST NOT start with an underscore.
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

**Configuration:**

| Property               | Type | Default | Description                                             |
| ---------------------- | ---- | ------- | ------------------------------------------------------- |
| `checkGlobalConstants` | bool | false   | Check a global constant, from a `const` statement, too. |

---

### PERCS31.NamingConventions.ValidEnumCaseName

Requires enum case names to be in PascalCase: a capital first letter, only letters and digits, and at least one
lower-case letter.

**Why:** [PER Coding Style 3.1, Section 9](https://www.php-fig.org/per/coding-style/#9-enumerations) says enum case declarations MUST use PascalCase. This replaces
`PhpCollective.Classes.EnumCaseCasing`, from php-collective/code-sniffer, which skips a name with no capitals, such as
`hearts`, and allows a single capital, such as `A`.

PascalCase is as [`ValidClassName`](#percs31namingconventionsvalidclassname) describes:

- **Passes:** `Hearts`, `BigJoker`, `Http2`, `ClassA`, `Vector3D` and `HTTPStatus`. [`AcronymCasing`](#percs31namingconventionsacronymcasing) warns about the
  acronym in the last one.
- **Fails:** `FOO`, `OK`, `A`, `C2`, `B2B`, `hearts`, `bigJoker` and `Foo_Bar`.

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

### PERCS31.Operators.OperatorPlacement

Requires a binary operator to start the next line when a statement wraps at it, not end this one, and that line to be
indented once past the line its expression starts on. Operators within a line aren't affected.

The expression starts after the nearest `;`, `,`, opening bracket, or full ternary `?` or `:`. Where it is decides the
indent:

- **A statement, a list item or grouping parentheses:** exactly once past the line the expression starts on. A list item
  is a call argument, an array element or a `match` arm, and grouping parentheses are as in `$x = (`.
- **An arrow function's `=>`:** exactly once past the line `fn` starts on.
- **A control structure's condition:** at least once past the line with the `(`. Set `requireExactConditionIndent` to
  require exactly once for a line starting with a boolean operator, and exactly once past its operand's line for any
  other operator, as the Jumbo standard does. An expression nested in the condition, such as a call's argument, follows
  the rule above.
- **A multi-line `case` condition:** not checked here. See [`PERCS31.ControlStructures.SwitchDeclaration`](#percs31controlstructuresswitchdeclaration).

**Why:** [PER Coding Style 3.1, Section 6.4](https://www.php-fig.org/per/coding-style/#64-operators-placement) says that when a statement is split at an operator, the operator MUST be at
the start of the new line, and each subsequent line is indented once. Section 7.1 says the same of an arrow function's
`=>`. That's read as applying wherever an expression is split, including a list item or grouping parentheses. Sections
5.1 to 5.4 say a split condition is indented "at least once", which, being in section 5, applies to a control
structure's parentheses only. Section 5.2's `case` example lines the operators up with the first condition.

Two inherited sniffs check the placement here, each for one case: `Universal.Operators.ConcatPosition` for `.`, and
`PSR12.ControlStructures.BooleanOperatorPlacement` for `&&` and `||` in a condition. Two more check part of it, but
aren't used. `PhpCollective.Operators.OperatorPosition` misses `=`, the comparison operators, `|`, `instanceof`, an
arrow function's `=>` and `|>`, and doesn't check the indent. `PEAR.Formatting.MultiLineAssignment` only checks `=`, and
can't fix it. This sniff covers every other binary operator: assignment, arithmetic, comparison, bitwise and logical
operators, `??`, `instanceof`, the short ternary `?:`, the arrow function's `=>`, and the pipe operator `|>` (PHP 8.5).
It checks the indent of all of them, including `.`. A full ternary is checked by
`PERCS31.ControlStructures.MultiLineTernaryOperator`, and section 6.2 doesn't list `=>`, so one in an array or `match`
arm isn't checked. An assignment operator before a multi-line array's `[` on the next line is left to
[`PERCS31.Arrays.OpeningBracketPlacement`](#percs31arraysopeningbracketplacement), whose fix gives `$a = [`.

A short ternary must be written `?:`, with nothing between the `?` and `:`, as [section 6.3](https://www.php-fig.org/per/coding-style/#63-ternary-operators) says it follows the rules for
other binary operators, and its examples write it as one. Anything between them, such as `$a ? : $b`, is reported as
`SplitShortTernary`, and the fix joins them where the `:` is.

**Auto-fixable:** yes, unless a comment follows an operator at the end of a line, or is between a short ternary's `?`
and `:`.

**Good:**

```php
$total = $price * $quantity
    + $shipping
    - $discount;

$label
    = $isActive ? 'Active' : 'Inactive';

$this->expectOutputString(
    'first'
        . 'second',
);

if (
    $isActive
    && $prefix
        . $name === 'admin'
) {
    run();
}
```

**Bad:**

```php
$total = $price * $quantity +
    $shipping -
    $discount;

$label =
    $isActive ? 'Active' : 'Inactive';

$sum = $a
        + $b;

$this->expectOutputString(
    'first'
    . 'second',
);
```

**Configuration:**

| Property                      | Type | Default | Description                                                      |
| ----------------------------- | ---- | ------- | ---------------------------------------------------------------- |
| `requireExactConditionIndent` | bool | false   | Require the exact indent in a condition, not just at least once. |

---

### PERCS31.Operators.OperatorSpacing

Requires at least one space around binary operators, including `&&`, `||`, `and`, `or`, `xor`, `.` and the pipe operator
`|>`. This replaces `PSR12.Operators.OperatorSpacing`.

**Why:** [PER Coding Style 3.1, Section 6.2](https://www.php-fig.org/per/coding-style/#62-binary-operators) says all binary operators MUST be preceded and followed by at least one
space, and section 6 allows more for readability. PSR-12's sniff checks that, but doesn't know `|>` (PHP 8.5).
PHP_CodeSniffer 4.0 doesn't define `T_PIPE` itself, but passes PHP's own token through on PHP 8.5, so this sniff
registers it when it's defined. On PHP 8.4, where `|>` is a parse error, PHP_CodeSniffer reads it as `|` and `>`, and
reports them.

The parent reads a `+` or `-` that starts a statement, as in `foo();` then `-$a->b();`, as binary, and reports the
missing space before it. A unary operator is left undefined by section 6, so this sniff skips it.

The parent skips an `=` directly followed by `&`, so this sniff checks it: `$x=&$a` and `$x =&$a` are reported, and
fixed to `$x = &$a`. `$x =& $a` passes, as it has a space either side if `=&` is read as one operator, and PER doesn't
say whether it is.

Unlike the PSR-12 sniff, it doesn't check `=>`, which section 6.2 doesn't list. Each use of `=>` has its own sniff, such
as [`PERCS31.WhiteSpace.ArrowSpacing`](#percs31whitespacearrowspacing) for a `match` arm's and an array's.

It extends `Squiz.WhiteSpace.OperatorSpacing`, which requires exactly one space, and covers PSR-12's operators too. The
ruleset excludes the codes for more than one space, `SpacingBefore` and `SpacingAfter` and their `Amp` versions, so only
a missing space is reported. A standard that wants exactly one space turns them back on, as the [Jumbo standard](Jumbo.md) does.

The `=` of a parameter's default value is a binary operator too, so `int $a    = 1` passes and `int $a=1` is reported.
`Squiz.Functions.FunctionDeclarationArgumentSpacing` wants exactly one space, so the ruleset excludes its
`SpaceBeforeEquals` and `SpaceAfterEquals` codes, and this sniff checks the `=` instead. Set `checkDefaultValues` to
`false` to leave it to that sniff, as the Jumbo standard does.

The `|` in a multi-catch, e.g. `catch (A|B $e)`, is a union type, not an operator, so it's skipped. [Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says the
union symbol MUST NOT have a space either side, which `PERCS31.TypeHints.CompoundTypeOperatorSpacing` checks. The
parent would require one.

The `&` of a by-reference hook, e.g. `&get => $this->items;`, isn't an operator either, so it's skipped, here and by
`PERCS31.Operators.OperatorPlacement`. So are the `|`, `&` and `?` of a `set` hook's parameter type, e.g.
`set(int|string $value)`, which `PERCS31.TypeHints.CompoundTypeOperatorSpacing` checks instead.

**Auto-fixable:** yes, as the parent.

**Good:**

```php
$slug = $title |> trim(...) |> strtolower(...);
```

**Bad:**

```php
$slug = $title|>trim(...)|>strtolower(...);
```

**Configuration:**

| Property             | Type | Default | Description                                   |
| -------------------- | ---- | ------- | --------------------------------------------- |
| `checkDefaultValues` | bool | true    | Check the `=` of a parameter's default value. |

---

### PERCS31.PHP.LowerCaseKeyword

Requires PHP's keywords in lower case, including a property hook's name. This replaces `Generic.PHP.LowerCaseKeyword`,
which it extends.

**Why:** [PER Coding Style 3.1, Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says all PHP reserved keywords MUST be in lower case. The Generic sniff
checks them, but not a hook's `get` or `set`, as in `GET => 1;`. They aren't on PHP's list of reserved keywords, as
they're only keywords in a hook list, and PHP_CodeSniffer reads them as plain names. [Section 4.10](https://www.php-fig.org/per/coding-style/#410-interface-and-abstract-properties) calls one "the hook
keyword", and every example writes it in lower case, so this subclass passes a hook's name to the parent's check too.

A method named `get`, or a call to a function named `set`, isn't a hook, so it isn't checked.

**Auto-fixable:** yes, as the parent.

**Good:**

```php
class Counter
{
    public int $count {
        get => $this->count;
        set(int $value) => max(0, $value);
    }
}
```

**Bad:**

```php
class Counter
{
    public int $count {
        GET => $this->count;
        Set(int $value) => max(0, $value);
    }
}
```

---

### PERCS31.PHP.LowerCaseType

Requires PHP's types in lower case, including in a `set` hook's parameter. This replaces `Generic.PHP.LowerCaseType`,
which it extends.

**Why:** [PER Coding Style 3.1, Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says all PHP types MUST be in lower case. The Generic sniff checks
parameters, return types, properties, typed constants and casts, with two gaps:

- It doesn't see the type of a `set` hook's parameter, as in `set(INT $value)`. PHP_CodeSniffer doesn't know about
  hooks, so it reads that type as an expression. This sniff checks it, with the parent's `ParamTypeFound` code. A type
  read there as a keyword, such as `SELF` or `NULL`, is already reported by `PERCS31.PHP.LowerCaseKeyword` or
  `Generic.PHP.LowerCaseConstant`.
- After an attribute, as in `set(#[Sensitive] INT $value)`, it reads the parameter as a property, and reports its type
  twice. This sniff drops those two reports, and makes its own.

A backed enum's type is checked by [`PERCS31.Classes.ClassDeclaration`](#percs31classesclassdeclaration).

**Auto-fixable:** yes, as the parent.

**Good:**

```php
class Counter
{
    public int|string $count {
        set(int|string $value) => (int) $value;
    }
}
```

**Bad:**

```php
class Counter
{
    public int|string $count {
        set(INT|String $value) => (int) $value;
    }
}
```

---

### PERCS31.PHP.RequireCloneParentheses

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

### PERCS31.PHP.TypeCast

Requires the canonical form of a type in a cast: `(bool)`, `(int)`, `(float)` and `(string)`, not `(boolean)`,
`(integer)`, `(double)` or `(binary)`. This replaces `PSR12.Keywords.ShortFormTypeKeywords`, and extends
`SlevomatCodingStandard.PHP.TypeCast`.

**Why:** [PER Coding Style 3.1, Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says the short form of a type keyword MUST be used, "i.e. `bool`
instead of `boolean`, `int` instead of `integer` etc." PHP 8.5 deprecates all four long forms alike, as non-canonical
casts. The PSR-12 sniff only knows `(boolean)` and `(integer)`. The Slevomat sniff knows all four, with one bug:

- It says a `(binary)` cast has no effect, and its fix deletes it. It's an alias of `(string)`, so `(binary) 5` is
  `'5'`, and deleting it can change what the code does. This sniff reports it with the `InvalidCastUsed` code, as the
  other three are, and fixes it to `(string)`.

A sniff's fix can't be changed from a ruleset, so this needs a subclass. A binary string's prefix, as in `b'abc'`,
isn't a cast, and isn't reported.

**Auto-fixable:** yes.

**Good:**

```php
$total = (float) $input;
$name = (string) $input;
```

**Bad:**

```php
$total = (double) $input;
$name = (binary) $input;
```

---

### PERCS31.Strings.HeredocIndentation

Requires a heredoc or nowdoc to begin on the line of its context, and its body and closing marker to be indented once
past the line the `<<<` is on.

The `<<<` can't follow `=`, `=>`, or a keyword such as `return`, on an earlier line. `$x` followed by `= <<<` is
reported too. A `<<<` on its own line is allowed as a list item, after `(`, `[` or `,`. After a binary operator, that
operator's own sniff moves it to the start of the `<<<` line.

**Why:** [PER Coding Style 3.1, Section 10](https://www.php-fig.org/per/coding-style/#10-heredoc-and-nowdoc) says subsequent lines in a heredoc or nowdoc MUST be indented once past the
scope they're declared in, and its examples indent them once past the line holding `<<<`. No inherited sniff checks it.
PHP removes the closing marker's indentation from every body line, and rejects a body line indented less than the
marker, so this sniff checks the marker.

**Why the `=` rule:** section 10 says the heredoc MUST be on the same line as the variable it's set against. Section 6.4
only allows a split before `=`, so nothing requires one here.

**Auto-fixable:** yes, except a comment in the way of joining two lines. The indent fix moves the marker and every body
line by the same amount, keeping any extra indentation within the text.

**Good:**

```php
function greet(string $name): string
{
    return <<<EOT
        Hello, {$name}.
        EOT;
}

var_dump(
    'foo',
    <<<'EOT'
        Hello
        EOT,
);
```

**Bad:**

```php
function greet(string $name): string
{
    return <<<EOT
Hello, {$name}.
EOT;
}

function farewell(): string
{
    return
        <<<'EOT'
            Goodbye
            EOT;
}

$notAllowed =
    <<<'EOT'
        Hello
        EOT;

$alsoNotAllowed
    = <<<'EOT'
        Hello
        EOT;
```

---

### PERCS31.TypeHints.CompoundTypeLayout

Requires a compound type split across lines to have one type per line, or for a DNF type, one union segment per line. A
type is split if any `|` or `&` outside parentheses starts a line. Then every one of them must, and a `&` inside a DNF
type's parentheses never can.

**Why:** [PER Coding Style 3.1, Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says each line of a split union or intersection MUST have a single type, and
each line of a split DNF type MUST have a single union segment, with all of its intersections on that line. No inherited
sniff checks it. This includes a `set` hook's parameter type, e.g. `set(int|string $value)`, and a multi-catch's
types, e.g. `catch (A|B $e)`.

**Auto-fixable:** yes, unless a comment comes before a `&` that starts a line inside parentheses.

**Good:**

```php
function reflect(
    \ReflectionObject
    |\ReflectionClass
    |\ReflectionMethod $reflect,
) {}

function iterate(
    array
    |(ArrayAccess&Traversable)
    |(Traversable&Countable) $input,
) {}
```

**Bad:**

```php
function reflect(
    \ReflectionObject|\ReflectionClass
    |\ReflectionMethod $reflect,
) {}

function iterate(
    array
    |(ArrayAccess
    &Traversable) $input,
) {}
```

---

### PERCS31.TypeHints.CompoundTypeOperatorSpacing

Disallows spaces around the `|` and `&` of a union, intersection or DNF type, but allows a type split across lines
before one. This replaces the `DisallowedWhitespaceAroundOperator` check of
`SlevomatCodingStandard.TypeHints.DNFTypeHintFormat`.

**Why:** [PER Coding Style 3.1, Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says the union and intersection symbols MUST NOT have a leading or trailing
space, and that a compound type MAY be split across lines, with the symbol at the start of each line. The Slevomat check
counts the line break as a space, so it rejects every split type, including PER's own examples. Which types go on which
line is checked by `PERCS31.TypeHints.CompoundTypeLayout`.

It also checks the `|` between a multi-catch's types, e.g. `catch (A|B $e)`, which is read as a union type's, so the
types may be split before a `|` too. `PhpCollective.WhiteSpace.CatchTypeSeparatorSpacing` reports that split, and can't
fix a `|` at the end of a line, so the ruleset doesn't use it.

It also checks a `set` hook's parameter type, e.g. `set(int|string $value)`, and the parentheses of a DNF type there, as
PHP_CodeSniffer reads that type as an expression, and the Slevomat sniff doesn't see it.

**Auto-fixable:** yes. A symbol at the end of a line is moved to the start of the next.

**Good:**

```php
function reflect(
    \ReflectionObject
    |\ReflectionClass
    |\ReflectionProperty $reflect,
): int|string {
    // ...
}
```

**Bad:**

```php
function reflect(
    \ReflectionObject |
    \ReflectionClass $reflect,
): int | string {
    // ...
}
```

---

### PERCS31.TypeHints.NullType

Requires `null` to be the last type in a union, and recommends `?T` for a union of one type with `null`, wherever a
type can appear. This replaces the `NullTypeHintNotOnLastPosition` and `RequiredShortNullable` checks of
`SlevomatCodingStandard.TypeHints.DNFTypeHintFormat`.

**Why:** [PER Coding Style 3.1, Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says `null` MUST be the last item, and that a union of a single simple type
with `null` SHOULD be written `?T`. The Slevomat sniff gets some cases wrong:

- It skips the `null` position in a type with an intersection, such as `null|(A&B)`.
- For `null|int`, it only gives the `?T` warning, so the MUST is lost below warning level.
- It doesn't check a typed constant, or a `set` hook's parameter, which PHP_CodeSniffer reads as an expression.
- Its `?T` fix deletes `static` from a return type, so `static|null` becomes `?`, which is a parse error.

So this sniff makes both checks everywhere. `?T` is a SHOULD, so a warning.

**Auto-fixable:** yes. A type split across lines, or with a comment in it, is reported but not fixed.

**Good:**

```php
function find((A&B)|null $a): int|string|null
{
    // ...
}

class Limits
{
    public const ?int MAX = null;
}
```

**Bad:**

```php
function find(null|(A&B) $a): int|null|string
{
    // ...
}

class Limits
{
    public const int|null MAX = null;
}
```

---

### PERCS31.WhiteSpace.ArrowSpacing

Requires exactly one space before and after the `=>` in a `match` arm, and at least one in an array element, so arrows
can be lined up.

**Why:** [PER Coding Style 3.1, Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match) shows a `match` expression with one space each side of `=>`, and says to
"note the placement of parentheses, spaces, and braces". Although the specification doesn't say so explicitly, it
appears to imply the same rule as for the `=>` of a property hook (4.9) and an arrow function (7.1). No inherited sniff
checks a `match` arm, which has its own token, so aligned arms aren't caught.

[Section 11](https://www.php-fig.org/per/coding-style/#11-arrays)'s example, which "shows correct array usage", has one space each side of an array's `=>`, but too few
examples to rule out lining arrows up. So at least one space is required. The [Jumbo standard](Jumbo.md) turns
`allowSeveralSpacesInArray` off, to require exactly one.

PER says nothing about a `yield`'s `=>`, so it isn't checked. The [Jumbo standard](Jumbo.md) turns `checkYield` on, to require
exactly one space each side. A `foreach`'s, an arrow function's and a property hook's `=>` have their own sniffs.

A line break before or after the arrow is allowed, for wrapping a long arm, element or value.

**Auto-fixable:** yes.

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

**Configuration:**

| Property                    | Type | Default | Description                                              |
| --------------------------- | ---- | ------- | -------------------------------------------------------- |
| `allowSeveralSpacesInArray` | bool | true    | Allow more than one space each side of an array's `=>`.  |
| `checkYield`                | bool | false   | Require exactly one space each side of a `yield`'s `=>`. |

---

### PERCS31.WhiteSpace.ConsistentIndent

Reports a line indented further than its scope, when the line before it ends a statement. This extends
`PhpCollective.WhiteSpace.ConsistentIndent`, from [php-collective/code-sniffer](https://github.com/php-collective/code-sniffer).

**Why:** [PER Coding Style 3.1, Section 2.4](https://www.php-fig.org/per/coding-style/#24-indenting) sets an indent of 4 spaces for each level, and section 5 says a structure's
body is indented once. `PERCS31.WhiteSpace.ScopeIndent` reports too little indentation, and too much on a body's first
line. The php-collective sniff reports a later line indented too far after a complete statement, including inside a
property hook. It skips continuation lines and arrays, which it can't judge safely. It skips closures, anonymous classes
and `switch` bodies too, as it counts levels from column 0, so this sniff checks those itself, measuring from the line
each one opens on.

This sniff also adds to the operators that may start a continuation line, which are skipped: every binary operator
section 6.4 allows a statement to be split at. The original's list predates the pipe operator `|>`, so it reported
section 6.4's own example. It also missed `%`, `**`, `instanceof`, bitwise, comparison and assignment operators, `::`,
and the `=>` of an arrow function or a `match` arm.

It also skips the lines under a PHP opening tag that isn't at the start of its line, as in a template. The original
measures from column 1, so it reported PHP indented to match the markup around it. So in a block, either 0 or 1 levels
past the tags is allowed, as PER doesn't say, and its example in section 3 uses 1. `PERCS31.WhiteSpace.ScopeIndent`
accepts both too, and doesn't check the indent of `?>`, as PER has no rule on it. The [Jumbo standard](Jumbo.md) requires 0 levels,
and `?>` level with `<?php`.

The original counts whole levels, rounding down, so a line indented 1 to 3 spaces too far passed, and its message gave
the indent as a number of levels. So this sniff makes the original's check itself, copied from php-collective 0.6.10,
counting spaces. Any extra space is reported, with the real number. It still extends the original, for the methods that
decide which lines can be judged.

The original skips a line that starts with a comment, and PER has no rule for a comment's indent, so that's the
default here. Set `checkComments` to report a comment indented further than its scope after a complete statement, as
the [Jumbo standard](Jumbo.md) does. It's reported as `Comment`. A comment at the top level isn't checked, and a comment over
several lines isn't fixed.

Three more lines are checked, each indented exactly, as section 2.4 sets:

- **A value wrapped after an array's `=>`:** once past the key's line. A heredoc or nowdoc is left to
  [`PERCS31.Strings.HeredocIndentation`](#percs31stringsheredocindentation).
- **A group `use`'s items:** once past the `use` line, as section 3's example shows.
- **Dead code in a `switch`, after the `break`, `return` or other statement that ends a case:** level with that
  statement, as there's no `case` to measure from.

**Auto-fixable:** yes.

**Good:**

```php
function total(array $items): int
{
    $sum = array_sum($items);
    return $sum;
}
```

**Bad:**

```php
function total(array $items): int
{
    $sum = array_sum($items);
        return $sum;
}
```

**Configuration:**

| Property        | Type | Default | Description                                                          |
| --------------- | ---- | ------- | -------------------------------------------------------------------- |
| `checkComments` | bool | false   | Report a comment indented further than its scope, after a statement. |

---

### PERCS31.WhiteSpace.ScopeClosingBrace

Checks that a scope's closing brace is on a line by itself, and indented to match, except in an empty body written as
`{}` on one line, e.g. `class Foo {}` or `function () {}`. This replaces `Squiz.WhiteSpace.ScopeClosingBrace`.

**Why:** `Squiz.WhiteSpace.ScopeClosingBrace` rejects an empty body written as `{}` on one line, which
[PER Coding Style 3.1, Sections 4, 4.4 and 7](https://www.php-fig.org/per/coding-style/#7-closures) requires for a closure and recommends for a class or function. This sniff
skips that case, and leaves everything else to the parent. See [PERCS31.Formatting.EmptyBody](#percs31formattingemptybody). It skips a control
structure's empty body written as `{}` too, which [PERCS31.ControlStructures.BodyOnNextLine](#percs31controlstructuresbodyonnextline) allows.

**Auto-fixable:** yes, as the parent.

---

### PERCS31.WhiteSpace.ScopeIndent

Checks that code is indented correctly, including property hooks (PHP 8.4+), and an anonymous class with attributes.
This is a fork of `Generic.WhiteSpace.ScopeIndent` from PHP_CodeSniffer 4.0.4. It isn't a subclass, as every change is
inside `process()`, which is one long method with no smaller one to override.

**Why:** the original gets these wrong for PER Coding Style 3.1, so the fork changes them:

- **Property hooks:** PHP_CodeSniffer's tokenizer doesn't treat property hook braces as scopes, so the original doesn't
  check the indentation inside a hook.
- **An anonymous class with attributes:** [Section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes) indents it once from the line with `new`, and its body from `class`.
  The original expects `class` at the statement's indent. See [PERCS31.Attributes.AttributePlacement](#percs31attributesattributeplacement).
- **A `match` arm:** a line starting an arm must be indented exactly once past the line the `match`'s `{` is on, as
  section 5.2 shows. PER says nothing about a later condition in an arm, split over lines, so that isn't checked. The
  original only reports indentation that's too small, so nothing else reports an arm indented too far.
- **A body's first line:** it must be indented exactly once, past the line its `{` is on, or past the line a `)` before
  the `{` opens on. This includes closures, `case` bodies and property hooks.
- **PHP in markup:** the code in a PHP block may be level with its opening tag, or indented once past it, and the indent
  of `?>` isn't checked, as PER has no rule on either. The original reports a `?>` that isn't level with its `<?php`.
- **`phpcs:set`:** a `phpcs:set` comment for this sniff's own code sets its `exact` property, as one for the original's
  code does there.

Every change is marked with a `PERCS31:` comment in the source, so a diff against the original shows only these.

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

These are the original's properties. PER Coding Style 3.1 needs their defaults, so the ruleset sets none.

| Property                  | Type  | Default | Description                                                             |
| ------------------------- | ----- | ------- | ----------------------------------------------------------------------- |
| `indent`                  | int   | 4       | Number of spaces per indentation level.                                 |
| `exact`                   | bool  | false   | Require exactly the expected indent on every line, not at least it.     |
| `tabIndent`               | bool  | false   | Fix with tabs, not spaces. PER requires spaces.                         |
| `ignoreIndentationTokens` | array | `[]`    | Tokens whose indent isn't checked. PSR-12 sets two, for comments.       |

```xml
<rule ref="PERCS31.WhiteSpace.ScopeIndent">
    <properties>
        <property name="indent" value="2"/>
    </properties>
</rule>
```

---

## Not Checked

- **An empty closure SHOULD be an arrow function (7).** Rewriting `function () {}` as `fn() => null` changes the return
  value, and `function (): void {}` has no arrow function equivalent.
- **A trailing comma in a split list of `match` conditions (2.6).** A comma before `=>` isn't required when an arm's
  conditions are on several lines. Set `disallowInSplitMatchConditions` on `PERCS31.Formatting.TrailingComma` to
  disallow one, as the [Jumbo standard](Jumbo.md) does.
- **A trailing comma in a split attribute block holding one attribute (2.6).** No sniff requires one. Splitting such a
  block already breaks section 12.1, as nothing may come between `#[` and the attribute's name.
- **Whether a fall-through comment is clear (5.2).** Any comment just before the next `case` or `default` passes, as the
  spec doesn't set any wording.
- **Avoiding abbreviations (2.7).** Whether a name could avoid an abbreviation can't be judged by a sniff.
- **A comment or statement after an anonymous class's, closure's or control structure's closing brace (4).** Section 4
  says "any closing brace", but each of these is part of a larger expression or statement, so this standard reads it as
  covering classes, methods, functions and property hooks only. After a block `declare` in the file header, a statement
  is still reported, as section 3 wants a blank line after the block. The [Jumbo standard](Jumbo.md) reports a statement after a
  control structure's closing brace.
- **The pipe operator `|>` on PHP 8.4.** It's only checked when PHP_CodeSniffer runs on PHP 8.5. On PHP 8.4, where `|>`
  is a parse error, PHP_CodeSniffer reads it as `|` and `>`, and reports them.
