# PERCS31 Coding Standard

A PHP_CodeSniffer standard for [PER Coding Style 3.1](https://www.php-fig.org/per/coding-style/), and nothing more. It's built on PSR-12, which PER Coding Style
extends.

No complete PHP_CodeSniffer standard for PER Coding Style exists at the time of development. PSR-12's sniffs get some
PER rules wrong, such as rejecting an empty body written as `{}`, and don't yet support PHP 8.4 and 8.5 features such as
property hooks and the pipe operator. This standard fixes both, using sniffs from PHP_CodeSniffer, Slevomat, PHPCSExtra
and php-collective where they fit, and custom sniffs where nothing did.

The [Jumbo standard](Jumbo.md) is PERCS31 plus my own preferences. Use PERCS31 on its own to follow PER Coding Style without them.

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

> [!IMPORTANT]
> The standard is called PERCS31, but its folder and namespace are `PERCS31`. Use that spelling wherever
> PHP_CodeSniffer needs the name: `<rule ref="PERCS31"/>`, `--standard=PERCS31` and sniff codes such as
> `PERCS31.Files.LineLength`. `PERCS31` only works there on a case-insensitive filesystem, so it fails on Linux.

---

## PSR-12

The complete [PSR-12](https://www.php-fig.org/psr/psr-12/) standard is included. These PSR-12 sniffs are replaced by PERCS31 versions:

- **PSR2.Classes.PropertyDeclaration** and **Generic.WhiteSpace.ScopeIndent**, by [`PERCS31.Classes.PropertyDeclaration`](#percs31classespropertydeclaration)
  and [`PERCS31.WhiteSpace.ScopeIndent`](#percs31whitespacescopeindent), which support property hooks (PHP 8.4).
- **Generic.Formatting.DisallowMultipleStatements**, by [`PERCS31.Formatting.DisallowMultipleStatements`](#percs31formattingdisallowmultiplestatements), which allows
  `{ get; set; }` on an abstract or interface property.
- **PSR2.Classes.ClassDeclaration**, **Squiz.Functions.MultiLineFunctionDeclaration** and
  **Squiz.WhiteSpace.ScopeClosingBrace**, by versions that allow an empty body as `{}`. See
  [`PERCS31.Formatting.EmptyBody`](#percs31formattingemptybody).
- **Generic.Files.LineLength**, by [`PERCS31.Files.LineLength`](#percs31fileslinelength), which also warns, at a lower severity, about a line over
  80 characters.
- **Generic.NamingConventions.UpperCaseConstantName**, from PSR-1, by [`PERCS31.NamingConventions.UpperCaseConstantName`](#percs31namingconventionsuppercaseconstantname),
  which allows PascalCase for an enum constant.
- **PSR12.Files.FileHeader**, by [`PERCS31.Files.FileHeader`](#percs31filesfileheader), which allows `<?php declare(strict_types=1) ?>` on the
  first line of a file with markup.
- **PSR12.Files.DeclareStatement**, by [`PERCS31.Files.DeclareStatement`](#percs31filesdeclarestatement), which accepts a declare statement with several
  directives, such as `declare(ticks=1,strict_types=1);`, or a string value, such as `declare(encoding='UTF-8');`.
- **PSR2.ControlStructures.SwitchDeclaration**, by [`PERCS31.ControlStructures.SwitchDeclaration`](#percs31controlstructuresswitchdeclaration), which adds PER's
  `switch` rules.
- **PSR12.Operators.OperatorSpacing**, by [`PERCS31.Operators.OperatorSpacing`](#percs31operatorsoperatorspacing), which also covers the pipe operator `|>`.
- **PSR12.Properties.ConstantVisibility** is kept, but a constant with no visibility is an error, not a warning, as
  [Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says MUST.
- **PSR12.ControlStructures.BooleanOperatorPlacement** is kept, with `allowOnly` set to `first`, so a wrapped `&&` or
  `||` in a condition starts the line (5.1, 6.4). PSR-12 only requires consistency.

---

## Inherited Sniffs

Sniffs from PHP_CodeSniffer's other standards and the two base packages, each for the PER Coding Style rule in brackets.

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

- **Squiz.Arrays.ArrayDeclaration**: Requires the first value of a multi-line array on the next line, one value per
  line, and the closing bracket on the next line after the last value (11), and no trailing comma on a single-line
  array (2.6). Its other checks aren't PER rules, so they're excluded: alignment, spacing around `=>` and commas, the
  spacing in `[]`, mixing keyed and unkeyed values, and which line a comma goes on. Its check for a multi-line array's
  trailing comma is replaced by Slevomat's `Arrays.TrailingArrayComma`, as it missed a keyed last element, and wanted
  the comma after a heredoc on the next line, as before PHP 7.3.

### PHPCSExtra

Reference: [PHPCSExtra repository and documentation](https://github.com/PHPCSStandards/PHPCSExtra)

- **Universal.Attributes.BracketSpacing**: Disallows a space after `#[` or before `]`, e.g. `#[ Override ]` (12.1).
- **Universal.Attributes.TrailingComma**: Disallows a trailing comma in an attribute block on one line, and requires one
  in a split block of several attributes (2.6). Its `ForbiddenSingleAttributeMultiLine` code is excluded, as it forbids
  the comma in a split block of one attribute, where 2.6 requires it.
- **Universal.Attributes.DisallowAttributeParentheses**: Disallows `()` on an attribute with no arguments, e.g.
  `#[Override()]` (12.1).
- **Universal.Classes.DisallowAnonClassParentheses**: Disallows `()` on an anonymous class with no arguments, e.g.
  `new class () {}` (8).
- **Universal.ControlStructures.DisallowAlternativeSyntax**: Disallows the alternative syntax, e.g.
  `if ($a): ... endif;`, as a structure's body must be in braces (5).
- **Universal.Constants.LowercaseClassResolutionKeyword**: Requires `::class` in lower case (2.5).
- **Universal.Operators.ConcatPosition**: Requires a wrapped `.` at the start of the line, not the end (6.4).
- **Universal.PHP.LowercasePHPTag**: Requires the `<?php` tag in lower case (3).
- **Universal.PHP.RequireExitDieParentheses**: Warns about `exit` or `die` without parentheses, as a SHOULD (4.7).
- **Universal.WhiteSpace.CommaSpacing**: In an attribute block, requires no space before a comma and one after, e.g.
  `#[Foo, Bar]` (12.3). Only its attribute block codes are used, as other commas are checked elsewhere.
- **Universal.UseStatements.LowercaseFunctionConst**: Requires `function` and `const` in lower case in a `use` statement
  (2.5).

### Slevomat

Reference: [Slevomat coding standard repository and documentation](https://github.com/slevomat/coding-standard)

- **Arrays.MultiLineArrayEndBracketPlacement**: Requires the closing bracket of a multi-line array on its own line (11).
- **Arrays.TrailingArrayComma**: Requires a trailing comma after the last element of a multi-line array or destructuring
  list, including after a heredoc's closing marker (2.6).
- **Attributes.DisallowMultipleAttributesPerLine**: Requires separate attribute blocks on separate lines (12.3).
- **Attributes.RequireAttributeAfterDocComment**: Requires a doc comment before the attributes, not after (12.2).
- **Classes.BackedEnumTypeSpacing**: Requires no space before a backed enum's colon, and one after (9).
- **Classes.ClassKeywordOrder**: Requires `abstract` or `final` before `readonly` on a class, e.g.
  `final readonly class` (4.6).
- **Classes.DisallowMultiPropertyDefinition**: Disallows several properties in one statement (4.3).
- **Classes.PropertyDeclaration**: Requires the modifier order, and the spacing around the modifiers and the type,
  including on promoted properties (4.3, 4.6).
- **Functions.ArrowFunctionDeclaration**: Requires no space after `fn`, e.g. `fn($x) => $x`. The expression may be on
  the next line (7.1).
- **Functions.NamedArgumentSpacing**: Requires no space before a named argument's colon, and one after (4.7).
- **Functions.DisallowTrailingCommaInCall**, **...InClosureUse** and **...InDeclaration**: Disallow a trailing comma in
  a single-line list (2.6).
- **Functions.RequireTrailingCommaInCall**, **...InClosureUse** and **...InDeclaration**: Require a trailing comma in a
  multi-line list (2.6).
- **TypeHints.DNFTypeHintFormat**: Requires no spaces inside the parentheses, and `null` last (2.5). It warns about
  `T|null`, which SHOULD be `?T`. Its check for spaces around `|` and `&` is replaced by
  [`PERCS31.TypeHints.CompoundTypeOperatorSpacing`](#percs31typehintscompoundtypeoperatorspacing), which allows a split type.

---

## Custom Sniffs

Each one fills a gap in the inherited sniffs, or replaces an inherited sniff that conflicts with PER Coding Style. Each
section gives the reason.

| Sniff                                                   | Checks                                                            | Fixable |
| ------------------------------------------------------- | ----------------------------------------------------------------- | ------- |
| `PERCS31.Arrays.ArrayBracketSpacing`                    | No blank line before a multi-line array's `]`                     | Yes     |
| `PERCS31.Arrays.OpeningBracketPlacement`                | A multi-line array's `[` isn't on a line of its own               | Yes     |
| `PERCS31.Attributes.AttributePlacement`                 | An attribute is on its own line, except on a parameter            | Yes     |
| `PERCS31.Attributes.DisallowMultiLineAttributesJoining` | One attribute per block when a block spans lines                  | Yes     |
| `PERCS31.Classes.ClassDeclaration`                      | Class declarations, allowing an empty body as `{}`                | Yes     |
| `PERCS31.Classes.ClassInstantiationNoBrackets`          | A warning for `(new Foo())->bar()`                                | Yes     |
| `PERCS31.Classes.EnumMemberVisibility`                  | `private`, not `protected`, for a method or constant in an enum   | Yes     |
| `PERCS31.Classes.PropertyHookLayout`                    | A hook list's and hook body's braces, and a short hook's `=>`     | Partly  |
| `PERCS31.Classes.PromotedPropertyHooks`                 | A hook on a promoted parameter is a single short hook on one line | No      |
| `PERCS31.Classes.PropertyDeclaration`                   | No leading `_`, no `var`, a visibility, and `get` before `set`    | Partly  |
| `PERCS31.ControlStructures.MultiLineTernaryOperator`    | A multi-line ternary takes three lines, `?` and `:` leading       | Yes     |
| `PERCS31.ControlStructures.SwitchDeclaration`           | `switch` rules, with a `break` in the last case                   | Yes     |
| `PERCS31.Files.DeclareStatement`                        | No spaces in `declare(...)`, with one directive or several        | Yes     |
| `PERCS31.Files.FileHeader`                              | The file header, allowing `<?php declare(...) ?>` above markup    | Yes     |
| `PERCS31.Files.LineLength`                              | A warning over 120 characters, and a hidden one over 80           | No      |
| `PERCS31.Formatting.DisallowMultipleStatements`         | One statement per line, allowing `{ get; set; }`                  | Yes     |
| `PERCS31.Formatting.EmptyBody`                          | An empty class, function, closure or anonymous class body is `{}` | Yes     |
| `PERCS31.Formatting.MethodChaining`                     | The first method of a split chain on the next line                | Yes     |
| `PERCS31.Formatting.TrailingComma`                      | Trailing commas in `match`, group `use` and `new class (...)`     | Yes     |
| `PERCS31.Functions.ArrowFunctionSemicolonSpacing`       | No space before the `;` that ends an arrow function               | Yes     |
| `PERCS31.Functions.MultiLineFunctionDeclaration`        | Function, closure and `fn` declarations, allowing `{}` bodies     | Yes     |
| `PERCS31.NamingConventions.AcronymCasing`               | A severity 4 warning for an acronym in capitals in a name         | No      |
| `PERCS31.NamingConventions.UpperCaseConstantName`       | Constants in UPPER_CASE, or PascalCase in an enum                 | No      |
| `PERCS31.NamingConventions.ValidConstantName`           | No leading underscore on a constant                               | No      |
| `PERCS31.NamingConventions.ValidEnumCaseName`           | Enum cases in PascalCase                                          | No      |
| `PERCS31.Operators.OperatorPlacement`                   | A wrapped binary operator starts the next line, indented once     | Yes     |
| `PERCS31.Operators.OperatorSpacing`                     | At least one space around binary operators, including `\|>`       | Yes     |
| `PERCS31.PHP.DisallowMultiConstantDefinition`           | One constant per statement                                        | Yes     |
| `PERCS31.PHP.RequireCloneParentheses`                   | A warning for `clone $a`, not `clone($a)`                         | Yes     |
| `PERCS31.Strings.HeredocIndentation`                    | Where a heredoc or nowdoc begins, and its indentation             | Yes     |
| `PERCS31.TypeHints.CompoundTypeLayout`                  | One type, or one DNF union segment, per line of a split type      | Yes     |
| `PERCS31.TypeHints.CompoundTypeOperatorSpacing`         | No space around `\|` or `&` in a type, but a split before one     | Yes     |
| `PERCS31.WhiteSpace.ArrowSpacing`                       | One space around `=>` in `match` arms                             | Yes     |
| `PERCS31.WhiteSpace.ConsistentIndent`                   | No line indented further than its scope, after a statement        | Yes     |
| `PERCS31.WhiteSpace.ScopeClosingBrace`                  | A closing brace on its own line, allowing `{}`                    | Yes     |
| `PERCS31.WhiteSpace.ScopeIndent`                        | Indentation, including property hooks and anonymous classes       | Yes     |

### PERCS31.Arrays.ArrayBracketSpacing

Disallows a blank line before the closing bracket of a multi-line array. This extends
`PhpCollective.Arrays.ArrayBracketSpacing`, from [php-collective/code-sniffer](https://github.com/php-collective/code-sniffer).

**Why:** [PER Coding Style 3.1, Section 11](https://www.php-fig.org/per/coding-style/#11-arrays) says the closing bracket MUST be on the next line after the last value.
Slevomat's `MultiLineArrayEndBracketPlacement` only checks that it starts a line. This subclass skips a destructuring
assignment, which isn't an array declaration, so section 2.3 allows the blank line.

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

Disallows the opening bracket of a multi-line array on a line of its own, e.g. as a function call's argument. An array
that's an element of a multi-line array is allowed, as is a destructuring assignment such as `[$a, $b] = pair();` split
over lines.

**Why:** [PER Coding Style 3.1, Section 11](https://www.php-fig.org/per/coding-style/#11-arrays) says the opening bracket of a multi-line array MUST NOT be on its own line.
PER's own example puts a nested array's `[` on its own line, hence that exception. No inherited sniff checks it.

**Auto-fixable:** yes. The fix moves the `[` to the end of the previous line, and `Generic.Arrays.ArrayIndent` then
re-indents the elements. For a call's only argument, it also moves the `)` up after the `]`, giving `foo([` ... `]);`.
It's left to a person when the array is next to another argument, or after a comment.

**Good:**

```php
$response = $client->post([
    'name' => $customer->fullName(),
    'email' => $customer->primaryEmailAddress(),
    'message' => 'Thanks for signing up. Your account is now active.',
]);

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
$response = $client->post(
    [
        'name' => $customer->fullName(),
        'email' => $customer->primaryEmailAddress(),
        'message' => 'Thanks for signing up. Your account is now active.',
    ],
);
```

---

### PERCS31.Attributes.AttributePlacement

Requires an attribute to be on the line before its target, as [PER Coding Style 3.0, Section 12.2](https://www.php-fig.org/per/coding-style/#122-placement) requires. The exception
is a parameter in a single-line parameter list, whose attribute must be inline.

A closure or arrow function isn't a structure in section 12.2's list. As a call's argument, its attribute follows the
rule for a parameter:

- **Argument list on one line:** inline, after a single space, e.g. `array_map(#[Pure] static fn($x) => $x, $items)`.
  A closure split over lines doesn't split the list, as [section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says, so
  `usort($items, #[Pure] static function ($a, $b) {` keeps its attribute inline.
- **Argument list split:** on its own line before the closure, indented the same.

Anywhere else, such as in an assignment or an array, PER says nothing, so it isn't checked. Set
`checkClosureAttributesOutsideCalls` to require the attribute inline after a single space there too, as the Jumbo
standard does.

**Why:** `SlevomatCodingStandard.Attributes.AttributeAndTargetSpacing` covers some of this, but its only option for an
attribute on its target's line, `allowOnSameLine`, is all or nothing, and its fix for a blank line deletes a comment
between the attribute and its target. So this sniff checks placement itself, and the PERCS31 ruleset doesn't use the
Slevomat sniff.

An attribute on an anonymous class follows [PER Coding Style 3.1, Section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes) instead. The attributes start on the line
after `new`, indented once, with `class` on the line after them at the same indent. `PERCS31.WhiteSpace.ScopeIndent`
then indents the class body from `class`.

It also reports a blank line between a docblock and the attributes after it, between two attribute blocks, or between
the last attribute and its target, and anything but a single space between an attribute and its parameter on the same
line, as section 12.2 requires. A comment on a line of its own between an attribute and its target is reported too, as
the attributes go immediately prior to the structure. A comment at the end of the attribute's line, as in
`#[Override] // Stringable`, is fine. A blank line between a parameter and the next parameter's attributes is allowed,
as 12.2 also says.

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

**Configuration:**

| Property                             | Type | Default | Description                                                                 |
| ------------------------------------ | ---- | ------- | --------------------------------------------------------------------------- |
| `checkClosureAttributesOutsideCalls` | bool | false   | Require a closure's attribute inline, after a single space, outside a call. |

---

### PERCS31.Attributes.DisallowMultiLineAttributesJoining

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

### PERCS31.Classes.ClassDeclaration

Checks the declaration of a class, interface, trait or enum, and the placement of its braces, except in an empty body
written as `{}` on one line, e.g. `class Foo {}`. This replaces `PSR2.Classes.ClassDeclaration`.

**Why:** `PSR2.Classes.ClassDeclaration` rejects an empty body written as `{}` on one line, which
[PER Coding Style 3.0, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) recommends. This sniff skips that case, and leaves everything else to the
parent. For an empty body, the checks on `extends` and `implements` still run. See [PERCS31.Formatting.EmptyBody](#percs31formattingemptybody).

**Auto-fixable:** yes, as the parent.

---

### PERCS31.Classes.ClassInstantiationNoBrackets

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

    private const string RED = 'red';

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

    protected const string RED = 'red';

    protected function symbol(): string
    {
        return '♥';
    }
}
```

---

### PERCS31.Classes.PropertyHookLayout

Checks the layout of a property's hook list: its braces, and the `=>` of a short hook.

**Why:** [PER Coding Style 3.1, Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) sets the layout of property hooks, and no inherited sniff knows about them.
This sniff checks:

- The opening brace is on the same line as the property, after a single space.
- In the long form, the closing brace is on its own line, with no comment after it.
- In a list with a `{}` body, each hook starts on its own line, each body starts on the line after its `{`, and each
  `}` is first on its line. An empty body, `{}`, has nothing to move, and a comment may follow the `{`.
- A short hook's `=>` has a single space either side, and its body starts on the same line.
- A short hook listed inline has a single space after the opening brace and before the closing one.
- A list with no bodies, as on an interface or abstract property, is on one line, written as `{ get; set; }`: a single
  space inside each brace, no space before each `;`, and a single space between operations ([Section 4.10](https://www.php-fig.org/per/coding-style/#410-interface-and-abstract-properties)).
- A bodiless hook in a list with bodies has no space before its `;`.

Indentation is checked by `PERCS31.WhiteSpace.ScopeIndent`, and several statements on a line by
`PERCS31.Formatting.DisallowMultipleStatements`. A hook on a promoted constructor parameter is reported by
`PERCS31.Classes.PromotedPropertyHooks`.

PER says nothing about a space in a by-reference hook, `& get`, so it passes. Set `checkReferenceSpacing` to report
it, as the Jumbo standard does.

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

**Configuration:**

| Property                | Type | Default | Description                                                    |
| ----------------------- | ---- | ------- | -------------------------------------------------------------- |
| `checkReferenceSpacing` | bool | false   | Report a space between a by-reference hook's `&` and its name. |

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

Verifies that properties are declared correctly. This replaces `PSR2.Classes.PropertyDeclaration`.

**Why:** PSR-12's `PSR2.Classes.PropertyDeclaration` misreads variables in PHP 8.4 property hook bodies, such as
`$value` and `$this`, as property declarations, and reports false errors on them. It also only warns about a leading
underscore, and doesn't check promoted constructor properties, where [PER Coding Style 3.0, Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says MUST NOT.
This sniff also checks that `get` comes before `set` in an abstract or interface property's hooks, which no inherited
sniff does.

**Auto-fixable:** only `HookOrder`, unless there's a comment between the hooks.

It reports:

- A property name with a leading underscore (`Underscore`), for any visibility, including promoted constructor
  parameters. PER Coding Style 3.0 disallows it too.
- The `var` keyword (`VarUsed`).
- A property with no visibility (`ScopeMissing`).
- `set` before `get` in a property's hooks (`HookOrder`). [PER Coding Style 3.1, Section 4.10](https://www.php-fig.org/per/coding-style/#410-interface-and-abstract-properties) requires this for an
  abstract or interface property with no hook bodies, e.g. `{ get; set; }`, so only a list with no bodies is checked.
  Set `orderHooksWithBodies` to check every hook list, including ones with bodies, `&` or attributes. The Jumbo standard
  does.

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

**Configuration:**

| Property               | Type | Default | Description                                                      |
| ---------------------- | ---- | ------- | ---------------------------------------------------------------- |
| `orderHooksWithBodies` | bool | false   | Check the hook order in every hook list, not just bodiless ones. |

---

### PERCS31.ControlStructures.MultiLineTernaryOperator

Requires a ternary that spans lines to take three, with `?` and `:` each starting a line, indented once. This replaces
`SlevomatCodingStandard.ControlStructures.DisallowTrailingMultiLineTernaryOperator`.

**Why:** [PER Coding Style 3.0, Section 6.4](https://www.php-fig.org/per/coding-style/#64-operators-placement) says a multi-line ternary MUST occupy three lines, never two, with each
operator at the start of its line. The Slevomat sniff only reports a `?` at the end of a line. No inherited sniff
reports a ternary on two lines, a trailing `:`, or the wrong indentation. A short ternary (`?:`) is a binary operator in
PER, so it isn't checked here.

**Auto-fixable:** yes, unless a comment is next to `?` or `:`.

The indentation is always 4 spaces, as [PER Coding Style 3.0, Section 2.4](https://www.php-fig.org/per/coding-style/#24-indenting) requires. This sniff doesn't use the `indent`
property of `PERCS31.WhiteSpace.ScopeIndent`.

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

### PERCS31.ControlStructures.SwitchDeclaration

Checks `switch` statements. This replaces `PSR2.ControlStructures.SwitchDeclaration`, and adds three rules:

- The last non-empty case MUST end with a terminating statement, such as `break`, `return` or `throw`.
- A multi-line case condition MUST be wrapped in parentheses, with `):` on a line of their own.
- A non-empty `default` that continues into the next case MUST have a comment saying so, as a `case` must.

**Why:** [PER Coding Style 3.1, Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match) adds both rules. The parent only checks a case that's followed by another,
and doesn't check multi-line conditions.

**Auto-fixable:** yes. The fix adds `break;` before the `}`, or adds the parentheses, or moves the `)` to its own line.
It's left to a person when there's a comment in the way, or the `}` follows code on its line. A `default` that falls
through isn't fixable, as only a person knows if that's intended.

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
statement, including a block declare with several directives. Its codes and messages are the original's. It's
temporary, until PHP_CodeSniffer handles several directives.

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

Section 3 also lists "One or more declare statements" as one block, with no blank line in it. The parent ends the header
at a block declare, and wants a blank line before one. This sniff counts one as a declare statement, so there's no blank
line between it and a declare statement before it.

**Auto-fixable:** yes, as the parent.

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

### PERCS31.Formatting.EmptyBody

Reports an empty class, interface, trait, enum, function, method, closure or anonymous class body that isn't written as
`{}` on the same line as the declaration, after a single space. It's a warning, except for a closure or anonymous class,
where it's an error.

**Why:** [PER Coding Style 3.1, Sections 4 and 4.4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says an empty class or function body SHOULD be abbreviated that way,
including a constructor that only promotes properties. [Section 7](https://www.php-fig.org/per/coding-style/#7-closures) says an empty closure body MUST be, and [section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes) says
an anonymous class MUST follow the same rules as a closure. No inherited sniff checks any of these. A body containing a
comment isn't empty.

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

Requires a trailing comma after the last arm of a multi-line `match`, the last name in a multi-line group `use`, or the
last argument in an anonymous class's multi-line arguments, and disallows one when the list is on a single line. It also
disallows one in a `match` arm's conditions on a single line, e.g. `1, 2, => 'a'`.

**Why:** [PER Coding Style 3.1, Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas) says the last item of a list split across lines MUST have a trailing comma, and
the last item of a list on one line MUST NOT. Slevomat's trailing comma sniffs cover calls, declarations and closure
`use` lists, and the Squiz array sniff covers arrays, but none covers `match`, group `use` or `new class (`. Like
Slevomat's call sniffs, it doesn't require a comma when the closing parenthesis follows the last argument on its line.

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
$short = match ($code) { 200 => 'OK', default => 'Error' };

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

---

### PERCS31.Functions.ArrowFunctionSemicolonSpacing

Disallows a space or line break before the semicolon that ends an arrow function.

**Why:** [PER Coding Style 3.1, Section 7.1](https://www.php-fig.org/per/coding-style/#71-short-closures) says the semicolon at the end of the expression MUST NOT be preceded by a
space. `SlevomatCodingStandard.Functions.ArrowFunctionDeclaration` checks the spacing around `fn` and `=>`, but not this.
PER has no such rule for other statements, so `$a = 1 ;` isn't checked.

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

### PERCS31.Functions.MultiLineFunctionDeclaration

Checks the layout of function, closure and arrow function declarations, including the placement of the opening brace,
except in an empty body written as `{}` on one line, e.g. `function reset(): void {}`. This replaces
`Squiz.Functions.MultiLineFunctionDeclaration`.

**Why:** `Squiz.Functions.MultiLineFunctionDeclaration` rejects an empty body written as `{}` on one line, which
[PER Coding Style 3.1, Sections 4.4 and 7](https://www.php-fig.org/per/coding-style/#7-closures) requires for a closure and recommends for a function. This sniff skips that
case, and leaves everything else to the parent. For an empty body after a single-line signature, the parent's
`CloseBracketNewLine` check is skipped too, as it can't be run on its own. See [PERCS31.Formatting.EmptyBody](#percs31formattingemptybody).

The parent also reports every blank line in a multi-line parameter or `use` list. [Section 2.3](https://www.php-fig.org/per/coding-style/#23-lines) allows blank lines
wherever they aren't forbidden, and [section 12.4](https://www.php-fig.org/per/coding-style/#124-example)'s example has one between two parameters, both before an attribute
and before a parameter with none. So the ruleset excludes the parent's `EmptyLine` code, and this sniff reports blank
lines itself, as `BlankLine`, except a single one between parameters. A blank line before the closing parenthesis, or
in a closure's `use` list, is still reported.

The parent doesn't check an arrow function. [Section 7.1](https://www.php-fig.org/per/coding-style/#71-short-closures) says an arrow function follows the same rules as a
closure, so this sniff checks its split parameter list too:

- The first parameter on the line after `fn(`.
- One parameter per line, each indented once.
- The closing parenthesis on a line of its own.
- Blank lines, as above.

An arrow function has no braces, so the brace checks don't apply.

**Auto-fixable:** yes, as the parent.

---

### PERCS31.NamingConventions.AcronymCasing

Warns about an acronym in capitals in a name: a namespace, class, interface, trait, enum, enum case, enum constant,
function, method, property, parameter or variable. It reports two or more capitals in a row.

**Why:** [PER Coding Style 3.1, Section 2.7](https://www.php-fig.org/per/coding-style/#27-naming) RECOMMENDS treating acronyms like regular words. The warning has a severity
of 4, like other RECOMMENDED rules. That's below `phpcs`'s default of 5, so run `phpcs --warning-severity=4` to see it.

One-letter words aren't allowed before a capital, as `parseURL` could otherwise be read as `parse`, `U`, `R` and `L`. So
`getID`, `getIDs`, `AClass`, `TType`, `OAuthToken` and `whenXIs` are all reported.

Section 2.7 doesn't limit this to some names, so it applies to all of them. It doesn't report:

- A single capital that isn't followed by another, as in `getX` or `X509Certificate`.
- PHP's reserved variables, such as `$_SERVER`.
- A constant outside an enum, as UPPER_CASE is required, or one in UPPER_CASE in an enum, which has its own warning.
- A property where it's used, such as `$this->userID` or `self::$userID`, as it may belong to a class you don't control.
  It's reported where it's declared.

A name you can't change, such as a method overriding a parent's, is reported too. A variable is reported at each use.

Two properties allow exceptions, each a comma-separated list. Matching is case-sensitive:

- `excludedNames` allows whole names. Each part of a namespace counts as a name of its own, so `PERCS31` allows
  `PERCS31\Sniffs`, but not `PERCS31\HTTP`.
- `excludedAcronyms` allows acronyms in any name. An acronym must match a whole run of capitals, not counting a last
  capital that starts a word. So `HTTP` allows `HTTPClient` and `HTTPs`, but not `HTTPSClient`, and `T` allows a prefix,
  as in `TType`. Listing several lets them combine, as in `XMLHTTPRequest`.

```xml
<rule ref="PERCS31.NamingConventions.AcronymCasing">
    <properties>
        <property name="excludedNames" value="PERCS31,legacyID"/>
        <property name="excludedAcronyms" value="HTTP,URL"/>
    </properties>
</rule>
```

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

Requires constant names in UPPER_CASE, except in an enum, where PascalCase is also allowed, and recommended. This
replaces `Generic.NamingConventions.UpperCaseConstantName`.

**Why:** PSR-1, which PER Coding Style builds on, requires class constants in UPPER_CASE, and the Generic sniff applies
that to every constant. [PER Coding Style 3.1, Section 9](https://www.php-fig.org/per/coding-style/#9-enumerations) says constants in an enum MAY use either PascalCase or
UPPER_CASE, and PascalCase is RECOMMENDED. So for an enum constant:

- PascalCase passes.
- UPPER_CASE gets an `EnumConstantNotPascalCase` warning with a severity of 4. That's below `phpcs`'s default of 5, so
  it's only shown with `--warning-severity=4`.
- Anything else, such as camelCase, gets an `EnumConstantInvalidCase` error.

Every other constant is left to the parent, except that a global `const` is reported as one made with `define()` is:
"Constants must be uppercase", with the `ConstantNotUpperCase` code. The parent reports it as a class constant.

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
const maxRetries = 3;

enum Suit: string
{
    case Hearts = 'h';

    public const string defaultColour = 'red';
}
```

---

### PERCS31.NamingConventions.ValidConstantName

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

### PERCS31.NamingConventions.ValidEnumCaseName

Requires enum case names to be in PascalCase: a capital first letter, only letters and digits, and not all capitals,
apart from a single letter.

**Why:** [PER Coding Style 3.0, Section 9](https://www.php-fig.org/per/coding-style/#9-enumerations) says enum case declarations MUST use PascalCase. No inherited sniff checks it.
A run of capitals inside a name, such as `HTTPStatus`, is allowed here, as PER section 2.7 only recommends treating
acronyms as words. [`PERCS31.NamingConventions.AcronymCasing`](#percs31namingconventionsacronymcasing) warns about it.
Based on `PhpCollective.Classes.EnumCaseCasing`, from php-collective/code-sniffer, which skips a name with no capitals,
such as `hearts`.

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

The expression starts after the nearest `;`, `,`, opening bracket, or full ternary `?` or `:`. Where it is decides
the indent:

- **A statement:** exactly once past the line it starts on.
- **A list item**, such as a call argument, an array element or a `match` arm: once past the line the item starts on,
  or lined up with the item if it starts its own line. Set `requireListItemIndent` to allow only the first, as the
  Jumbo standard does.
- **An arrow function's `=>`:** exactly once past the line `fn` starts on, even in a list item.
- **A control structure's condition, or a multi-line `case` condition:** not checked.
- **Grouping parentheses**, as in `$x = (`: not checked. Set `requireGroupingLineUp` to require a line to line up with
  the group's first line, or if that's on the `(` line, to be indented once past it, as the Jumbo standard does. An
  arrow function's body is still indented once.

**Why:** [PER Coding Style 3.1, Section 6.4](https://www.php-fig.org/per/coding-style/#64-operators-placement) says that when a statement is split at an operator, the operator MUST be at
the start of the new line, and each subsequent line is indented once. Section 7.1 says the same of an arrow function's
`=>`. Section 5 says a split condition is indented at least once, and section 5.2's `case` example lines the operators
up with the first condition. PER says nothing about an operator wrapped inside a list item or grouping parentheses,
which aren't statements, so either layout passes.

Only two inherited sniffs check the placement, each for one case: `Universal.Operators.ConcatPosition` for `.`, and
`PSR12.ControlStructures.BooleanOperatorPlacement` for `&&` and `||` in a condition. This sniff covers every other
binary operator: assignment, arithmetic, comparison, bitwise and logical operators, `??`, `instanceof`, the short
ternary `?:`, the arrow function's `=>`, and the pipe operator `|>` (PHP 8.5). It checks the indent of all of them,
including `.`. A full ternary is checked by `PERCS31.ControlStructures.MultiLineTernaryOperator`, and `=>` in an array
or `match` arm isn't an operator.

**Auto-fixable:** yes, unless a comment follows an operator at the end of a line.

**Good:**

```php
$total = $price * $quantity
    + $shipping
    - $discount;

$label
    = $isActive ? 'Active' : 'Inactive';

// Either layout, unless requireListItemIndent is set, when only the first passes.
$this->expectOutputString(
    'first'
        . 'second',
);
$this->expectOutputString(
    'first'
    . 'second',
);
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

| Property                | Type | Default | Description                                                               |
| ----------------------- | ---- | ------- | ------------------------------------------------------------------------- |
| `requireListItemIndent` | bool | false   | Require a wrapped line in a list item to be indented once past the item.  |
| `requireGroupingLineUp` | bool | false   | Require a wrapped line in a group to line up with the group's first line. |

---

### PERCS31.Operators.OperatorSpacing

Requires at least one space around binary operators, including `&&`, `||`, `and`, `or`, `xor`, `.` and the pipe operator
`|>`. This replaces `PSR12.Operators.OperatorSpacing`.

**Why:** [PER Coding Style 3.1, Section 6.2](https://www.php-fig.org/per/coding-style/#62-binary-operators) says all binary operators MUST be preceded and followed by at least one
space, and section 6 allows more for readability. PSR-12's sniff checks that, but doesn't know `|>` (PHP 8.5).
PHP_CodeSniffer 4.0 doesn't define `T_PIPE` itself, but passes PHP's own token through on PHP 8.5, so this sniff
registers it when it's defined. The pipe isn't checked when PHPCS runs on PHP 8.4.

The parent skips an `=` directly followed by `&`, so this sniff checks it: `$x=&$a` and `$x =&$a` are reported, and
fixed to `$x = &$a`. `$x =& $a` passes, as it has a space either side if `=&` is read as one operator, and PER doesn't
say whether it is.

It extends `Squiz.WhiteSpace.OperatorSpacing`, which requires exactly one space, and covers PSR-12's operators too. The
ruleset excludes the codes for more than one space, `SpacingBefore` and `SpacingAfter` and their `Amp` versions, so only
a missing space is reported. A standard that wants exactly one space turns them back on, as the [Jumbo standard](Jumbo.md) does.

The `|` in a multi-catch, e.g. `catch (A|B $e)`, is a union type, not an operator, so it gets the opposite rule:
[Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says the union symbol MUST NOT have a space either side. The parent would require one.

The `&` of a by-reference hook, e.g. `&get => $this->items;`, isn't an operator either, so it's skipped, here and by
`PERCS31.Operators.OperatorPlacement`.

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

### PERCS31.PHP.DisallowMultiConstantDefinition

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

Requires a compound type split across lines to have one type per line, or for a DNF type, one union segment per line.
A type is split if any `|` or `&` outside parentheses starts a line. Then every one of them must, and a `&` inside a DNF
type's parentheses never can.

**Why:** [PER Coding Style 3.1, Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says each line of a split union or intersection MUST have a single type,
and each line of a split DNF type MUST have a single union segment, with all of its intersections on that line. No
inherited sniff checks it.

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

### PERCS31.WhiteSpace.ArrowSpacing

Requires exactly one space before and after the `=>` in a `match` arm, and optionally in an array element or a `yield`.

**Why:** [PER Coding Style 3.1, Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match) shows a `match` expression with one space each side of `=>`, and says to
"note the placement of parentheses, spaces, and braces". Although the specification doesn't say so explicitly, it
appears to imply the same rule as for the `=>` of a property hook (4.9) and an arrow function (7.1). No inherited sniff
checks a `match` arm, which has its own token, so aligned arms aren't caught.

Section 6.2 doesn't list `=>` among the binary operators, so section 6 leaves its spacing undefined elsewhere. So an
array's and a `yield`'s are only checked if `checkArray` or `checkYield` is set, as the Jumbo standard does. A
`foreach`'s, an arrow function's and a property hook's `=>` have their own sniffs.

**Auto-fixable:** yes.

A line break before or after the arrow is allowed, for wrapping a long arm, element or value.

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

| Property     | Type | Default | Description                               |
| ------------ | ---- | ------- | ----------------------------------------- |
| `checkMatch` | bool | true    | Check the `=>` in a `match` arm.          |
| `checkArray` | bool | false   | Check the `=>` in an array element.       |
| `checkYield` | bool | false   | Check the `=>` in `yield $key => $value`. |

---

### PERCS31.WhiteSpace.ConsistentIndent

Reports a line indented further than its scope, when the line before it ends a statement. This extends
`PhpCollective.WhiteSpace.ConsistentIndent`, from [php-collective/code-sniffer](https://github.com/php-collective/code-sniffer).

**Why:** [PER Coding Style 3.1, Section 2.4](https://www.php-fig.org/per/coding-style/#24-indenting) sets an indent of 4 spaces for each level, and section 5 says a structure's
body is indented once. `PERCS31.WhiteSpace.ScopeIndent`, like PSR-12's, only reports indentation that's too small. The
php-collective sniff reports a line indented too far after a complete statement, including inside a property hook. It
skips a body's first line, continuation lines, arrays, closures and `switch` cases, which it can't judge safely.

This sniff only adds to the operators that may start a continuation line, which are skipped: every binary operator
section 6.4 allows a statement to be split at. The original's list predates the pipe operator `|>`, so it reported
section 6.4's own example. It also missed `%`, `**`, `instanceof`, bitwise, comparison and assignment operators, and an
arrow function's `=>`.

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

---

### PERCS31.WhiteSpace.ScopeClosingBrace

Checks that a scope's closing brace is on a line by itself, and indented to match, except in an empty body written as
`{}` on one line, e.g. `class Foo {}` or `function () {}`. This replaces `Squiz.WhiteSpace.ScopeClosingBrace`.

**Why:** `Squiz.WhiteSpace.ScopeClosingBrace` rejects an empty body written as `{}` on one line, which
[PER Coding Style 3.1, Sections 4.4 and 7](https://www.php-fig.org/per/coding-style/#7-closures) requires for a closure and recommends for a class or function. This sniff skips
that case, and leaves everything else to the parent. See [PERCS31.Formatting.EmptyBody](#percs31formattingemptybody).

**Auto-fixable:** yes, as the parent.

---

### PERCS31.WhiteSpace.ScopeIndent

Checks that code is indented correctly, including property hooks (PHP 8.4+), and an anonymous class with attributes.
This is a fork of `Generic.WhiteSpace.ScopeIndent`.

**Why:**

- PHP_CodeSniffer's tokenizer doesn't treat property hook braces as scopes, so `Generic.WhiteSpace.ScopeIndent` doesn't
  check the indentation inside a hook.
- [PER Coding Style 3.1, Section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes) indents an anonymous class with attributes once from the line with `new`, and its body
  from `class`. The parent expects `class` at the statement's indent. See [PERCS31.Attributes.AttributePlacement](#percs31attributesattributeplacement).

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
  conditions are on several lines, and no sniff requires one in a split attribute block with a single attribute.
- **Whether a fall-through comment is clear (5.2).** Any comment just before the next `case` or `default` passes, as the
  spec doesn't set any wording.
- **Avoiding abbreviations (2.7).** Whether a name could avoid an abbreviation can't be judged by a sniff.
- **The pipe operator `|>` on PHP 8.4.** It's only checked when PHP_CodeSniffer runs on PHP 8.5, as 8.4 has no `T_PIPE`
  token.
- **Over-indentation of a body's first line (2.4, 5).** `PERCS31.WhiteSpace.ScopeIndent` only reports too little
  indentation, and [`PERCS31.WhiteSpace.ConsistentIndent`](#percs31whitespaceconsistentindent) only a line after a complete
  statement. So in `if ($a) {` followed by `        foo();`, the over-indented first line isn't reported.
