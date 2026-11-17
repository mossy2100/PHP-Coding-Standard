# PER-CS 3.1 Judgement Calls

[PER Coding Style 3.1](https://www.php-fig.org/per/coding-style/) and PSR-1 leave some points unclear, contradict themselves in places, and say nothing about others.
A coding standard still has to do something in each case. This page lists the calls the [PERCS31](PERCS31.md) standard makes, and why.

Each item has three parts:

- **Issue:** the ambiguity, contradiction or omission in the spec.
- **Decision:** what PERCS31 does about it.
- **Why:** the reasoning.

The general principles come first, as they settle most of the other cases. The items after them follow the spec's
section order.

---

## General Principles

### Rule Text and Examples

**Issue:** The spec's code examples sometimes show more than its text says, and a few contradict it.

**Decision:**

- The rule text beats an example that contradicts it.
- Where the text is silent, an example sets a rule when the spec presents it as a template, as in "Note the placement of
  parentheses, spaces, and braces", "MUST be formatted as below" or "The following example shows correct array usage".
- Otherwise an example shows one allowed form, and is used as evidence when reading unclear text.

**Why:** The text is the normative part of the spec. An example introduced as showing the correct form is the spec's own
statement of the rule, so it's treated as one.

---

### Unclear Text

**Issue:** Some rules can be read more than one way.

**Decision:** PERCS31 chooses the reading that best fits the spec's examples, its rules for similar constructs, and
PHP_CodeSniffer's reading of the same PSR-12 wording where there is one. It allows something only when nothing in the
spec or established practice points either way.

**Why:** A reading backed by the spec's own patterns is more likely to be what its authors meant than the most lenient
one. A little extra strictness, consistent with the rest of the spec, is unlikely to trouble anyone.

---

### Spec Examples That Break the Spec

Several examples are shown in the spec that break its own rules. In these cases, the text is considered to overrule the
example.

#### 2.5 Keywords and Types

This example is shown:

```php
function somethingWithReflection(
    \ReflectionObject
    |\ReflectionClass
    |\ReflectionMethod
    |\ReflectionParameter
    |\ReflectionProperty $reflect
): object|null {
    // ...
}
```

Two issues here:

a. The parameter list has no trailing comma. This contradicts section 2.6, which says:

> If the list is split across multiple lines, then the last item MUST have a trailing comma.

b. The method returns `object|null`, not `?object`. This contradicts section 2.5, which says:

> A union of a single simple type with `null` SHOULD be abbreviated using the `?` alternate syntax: `?T`.

Another example:

```php
function veryComplex(
    array
    |(ArrayAccess&Traversable)
    |(Traversable&Countable) $input): ArrayAccess&Traversable
{
    // ...
}
```

The method has its `)` and `{` on different lines. This contradicts section 4.5, which says:

> When the argument list is split across multiple lines, the closing parenthesis and opening brace MUST be placed
> together on their own line with one space between them.

Its parameter list has no trailing comma either, which contradicts section 2.6, as in the first example.

#### 4.2 Using Traits

This example is shown:

```php
class Talker
{
    use A;
    use B {
        A::smallTalk insteadof B;
    }
    use C {
        B::bigTalk insteadof C;
        C::mediumTalk as FooBar;
    }
}
```

The traits are named `A`, `B` and `C`. This contradicts PSR-1, which says:

> Class names MUST be declared in `StudlyCaps`.

Section 2.1 reads StudlyCaps as PascalCase, and section 4 says "class" covers traits. A name with no lower-case letter
is UPPER_CASE, not PascalCase. See [PascalCase (PSR-1)](#pascalcase-psr-1).

The example only uses the traits, so PERCS31 reports nothing in it. A trait declared with one of these names is
reported.

The overview's example and section 3's also give classes single-letter aliases, as in `ClassA as A` and `ClassD as D`.
An alias isn't a declared class name, so PSR-1's rule doesn't cover it, and it isn't checked.

#### 4.9 Property Hooks

This example is shown:

```php
class Example
{
    public function __construct(
        public string $name { set => ucfirst($value); }
    ) {}
}
```

This contradicts section 2.6, which says:

> If the list is split across multiple lines, then the last item MUST have a trailing comma.

The example after it, marked "not allowed" for its hook, has no trailing comma either.

#### 4.10 Interface and abstract properties

This example is shown:

```php
abstract class Example {
    abstract public string $name {
        get => ucfirst($this->name);
        set;
    }
}
```

This contradicts section 4.1, which says:

> The opening brace for the class MUST go on its own line, and MUST NOT be preceded or followed by a blank line.

#### 8. Anonymous Classes

This example is shown:

```php
<?php

// Brace on the same line
// No arguments
$instance = new class extends \Foo implements \HandleableInterface {
    // ...
};

// Brace on the next line
// Constructor arguments
$instance = new class ($a) extends \Foo implements
    \ArrayAccess,
    \Countable,
    \Serializable
{
    public function __construct(public int $a)
    {
    }
    // ...
};
```

The constructor's body is empty, but isn't written as `{}`. This contradicts section 4.4, which says:

> If a function or method contains no statements or comments (such as an empty no-op implementation or when using
> constructor property promotion), then the body SHOULD be abbreviated as `{}` and placed on the same line as the
> previous symbol, separated by a space.

#### 10. Heredoc and Nowdoc

This example is shown:

```php
function allowed()
{
    $allowedHeredoc = <<<COMPLIANT
        This
        is
        a
        compliant
        heredoc
        COMPLIANT;

    $allowedNowdoc = <<<'COMPLIANT'
        This
        is
        a
        compliant
        nowdoc
        COMPLIANT;

    var_dump(
        'foo',
        <<<'COMPLIANT'
            This
            is
            a
            compliant
            parameter
            COMPLIANT,
        'bar',
    );
}
```

The `$allowedHeredoc` heredoc has nothing to interpolate, so a nowdoc could be used. This contradicts section 10, which
says:

> A nowdoc SHOULD be used wherever possible.

---

### PER Replaces PSR-12

**Issue:** PER Coding Style replaces PSR-12, but many PHP_CodeSniffer sniffs were written for PSR-12, and some reject
code PER allows.

**Decision:** A PSR-12 sniff that rejects something PER allows is replaced, configured or excluded.

**Why:** PERCS31 implements PER 3.1. A PSR-12 report on code PER allows is a false positive.

---

### What Goes in PERCS31

**Issue:** The spec doesn't cover every formatting question, such as which line a comma goes on.

**Decision:** PERCS31 contains only the rules the spec requires, read as described on this page. Anything else is left
unchecked. Extra rules go in [Jumbo](Jumbo.md).

**Why:** PERCS31 is meant to be the spec as written, so a project can adopt it without taking on anyone's preferences.

---

## Overview

### Keywords

**Issue:** The spec uses RFC 2119 keywords, but doesn't say how a tool should report each one.

**Decision:**

| Keyword                      | Reported as                                                 |
| ---------------------------- | ----------------------------------------------------------- |
| MUST, MUST NOT, REQUIRED     | An error, unless the rule defines a soft limit              |
| SHOULD, SHOULD NOT           | A severity 5 warning, or severity 4 where noted on the page |
| RECOMMENDED, NOT RECOMMENDED | A severity 5 warning                                        |
| MAY, OPTIONAL                | Never reported; only the MUST in a conditional MAY is       |

**Why:** Errors and warnings map naturally to MUST and SHOULD. RFC 2119 defines RECOMMENDED as SHOULD.

---

## 2.1 Basic Coding Standard

### PascalCase (PSR-1)

**Issue:** PSR-1 requires class names in StudlyCaps, without defining it. 2.1 reads it as PascalCase, "where the first
letter of each word is capitalized". That doesn't settle whether `FOO`, `A` or `HTTPStatus` qualifies.

**Decision:**

- A PascalCase name starts with a capital, has only letters and digits, and has at least one lower-case letter.
- This applies to classes, interfaces, traits, enums and enum cases, as an error. An enum constant may be in PascalCase
  or UPPER_CASE (9).
- Any name with no lower-case letter fails, as it's UPPER_CASE: `FOO`, `OK`, `HTTP`, `A`, `C2`, `B2B`.
- Any name with an underscore fails: `next_node`, `REDUCED_PLANCK`, `Phone_Number`.
- Any other name passes: `Foo`, `HTTPStatus`, `XMLParser`, `ClassA`, `IAnimal`.

**Why:** PascalCase mixes upper and lower case. A name with no lower-case letter is UPPER_CASE instead. Section 4 uses
"class" to cover all four kinds of class-like structure.

---

### Global Constants (PSR-1)

**Issue:** PSR-1 says "Class constants MUST be declared in all upper case". It says nothing about global constants.

**Decision:** Only class constants are checked for upper case, and for a leading underscore (4.3). A global constant,
from `const` or `define()`, is only checked by `PERCS31.NamingConventions.AcronymCasing`, and only if it has a
lower-case letter.

**Why:** PSR-1's rule names class constants only. 4.3's rule is about showing visibility, which a global constant
doesn't have.

---

### File Encoding (PSR-1)

**Issue:** PSR-1 says "PHP code MUST use only UTF-8 without BOM". PSR-12 only checks the byte-order mark, and no
inherited sniff checks the encoding.

**Decision:** `PERCS31.Files.Encoding` reports a file that isn't valid UTF-8, once, at its first invalid line. There's
no fix.

**Why:** It's a MUST, and it can be checked. The file's real encoding can't be known, so converting it is left to a
person.

---

## 2.3 Lines

### Line Length

**Issue:** "The soft limit on line length MUST be 120 characters" is a MUST that requires only a soft limit. "Lines
SHOULD NOT be longer than 80 characters" is a SHOULD that's rarely followed.

**Decision:**

- A line over 120 characters is a severity 5 warning. There's no hard limit.
- A line over 80 characters is a severity 4 warning, hidden by default.

**Why:** A soft limit is a warning by definition. The 80-character SHOULD is kept, but out of sight unless asked for.
See [Line Length](LineLength.md) to change the limit.

---

## 2.4 Indenting

### PHP Inside Markup

**Issue:** 2.4 sets the indent for code, but not for PHP blocks inside HTML or other markup.

**Decision:** Code in a PHP block may be level with its tags, or one level past them. The indent of `?>` isn't checked.

**Why:** Both styles are common, and the spec shows both:

- Every example that starts with `<?php` has its code level with the tag.
- Section 3's example of a file with markup has `// ...`, standing in for code, one level past its `<?php`:

```php
<?php declare(strict_types=1) ?>
<html>
<body>
    <?php
        // ...
    ?>
</body>
</html>
```

---

### Comments

**Issue:** The spec doesn't say how a comment is indented.

**Decision:** A comment's indent isn't checked, whatever kind of comment it is.

**Why:** PER has no rule for a comment's indent, or for its layout, so PERCS31 has no check for either.

---

### Further Indents

**Issue:** Several constructs need an indent the text doesn't spell out.

**Decision:** Each is measured exactly, too little as well as too much:

- A group `use`'s items: once past the `use` line, as section 3's example shows.
- A value wrapped after `=>`: once past its key's line (11).
- Dead code after a `break`: level with the statement that ends the case.
- A statement in a closure, anonymous class or `switch`: the usual indent for its body.

**Why:** Each follows the nearest example, or the general rule of one indent per level.

---

## 2.5 Keywords and Types

### The Indent of a Split Type

**Issue:** 2.5 says how a compound type is split over lines, but not how its lines are indented. Its examples are all in
parameter lists, where the type starts its line, and each `|` line is level with it.

**Decision:** Each line that starts with a `|` or `&` is indented as the type's first line requires:

- Level with it, when the type starts that line, as in a parameter list or a multi-catch.
- Once past it, when other code comes before the type on that line, as in a property, a constant, a return type or a
  promoted parameter.

So these are right:

```php
function reflect(
    \ReflectionObject
    |\ReflectionClass $reflect,
): void {
    // ...
}

class Reflector
{
    public \ReflectionObject
        |\ReflectionClass $reflect;
}
```

**Why:** The first follows 2.5's examples, and 4.5, which indents each line of a split parameter list once. In the
second, the `|` line continues a line that starts with something else, so it's indented once more, as 6.4 indents any
line that continues another.

---

### `|` in a Multi-catch

**Issue:** PHP calls `catch (A|B $e)` a multi-catch, not a type declaration. It's unclear whether 2.5's rule on compound
types covers it, or whether `|` is a binary operator there.

**Decision:** It's read as a union type, so no space is allowed around the `|`. The types may be split across lines
before a `|`, one type per line, as any union type may.

**Why:** `A|B` lists the exception types the variable can hold, which is what a union type does.

---

### Magic Constants

**Issue:** 2.5 requires reserved keywords in lower case. PHP's list of reserved keywords includes the magic constants,
such as `__DIR__`, which are conventionally upper case, and whose case PHP ignores.

**Decision:** Their case isn't checked.

**Why:** Reading the rule as requiring `__dir__` would go against near-universal practice, and it's unlikely the spec
means that.

---

### Hook Names

**Issue:** 2.5 requires reserved keywords in lower case. A property hook's `get` and `set` aren't on PHP's list of
reserved keywords, as they're only keywords inside a hook list.

**Decision:** They must be in lower case, so `GET => 1;` is reported.

**Why:** Section 4.10 calls one "the hook keyword", every example writes it in lower case, and 4.6 says "All keywords
MUST be all lower-case".

---

### `(double)` and `(binary)` Casts

**Issue:** 2.5 requires the short form of a type keyword, "i.e. `bool` instead of `boolean`, `int` instead of `integer`
etc." `(double)` is an alias for `(float)`, and `(binary)` for `(string)`. Neither is a longer spelling of the same
word, so it's unclear whether "etc." covers them.

**Decision:** It does. Both are reported, as `(boolean)` and `(integer)` are. `(double)` is fixed to `(float)`, and
`(binary)` to `(string)`.

**Why:** PHP treats the four alike. PHP 8.5 deprecates each as a "non-canonical cast", and names the form to use
instead.

---

## 2.6 Trailing Commas

### Which Lists

**Issue:** 2.6 lists the kinds of list it covers, but PHP has others the list doesn't name.

**Decision:** A multi-line list needs a trailing comma, and a single-line list mustn't have one, for arguments,
parameters, closure `use` lists, arrays, `match` arms, a `match` arm's conditions, group `use`, `list()`, an anonymous
class's arguments, `clone()`, `exit()`, `die()` and attribute blocks. `clone ($a)->c` is skipped, as its parentheses may
only group the object.

A `match` has two lists: its arms, and the conditions at the start of each arm. See
[`match` Conditions](#match-conditions).

**Why:** The rule's purpose, cleaner diffs, applies equally to every comma-separated list split over lines.

---

### One Argument Split Over Lines

**Issue:** 2.6 requires a trailing comma in a list "split across multiple lines", and forbids one in a list "contained
on a single line". 4.7 says a single argument split across lines, such as an array or a closure, "does not constitute
splitting the argument list itself". So this call's argument list isn't split, and isn't on a single line either:

```php
foo([
    1,
    2,
],);
```

**Decision:** An argument list is split when its first argument starts on the line after the `(`, or its `)` is on a
line of its own. Any other list mustn't have a trailing comma, so the comma after the `]` above is reported. This covers
the arguments of a call, an attribute, an anonymous class, `clone()`, `exit()` and `die()`.

This is how it should be written:

```php
foo([
    1,
    2,
]);
```

**Why:** 2.6's two cases are read as "split" and "not split". 4.7's own examples of this shape, `], $baz);` and `});`,
have no comma.

---

### `match` Conditions

**Issue:** 2.6's rule covers any comma-separated list that may end in a comma. A `match` arm's conditions are one, but
5.2's example only has conditions on one line, and the spec never shows them split.

**Decision:** 2.6 applies as written.

- Conditions on one line don't end in a comma, as in `1, 2 => 'a',`.
- Conditions split over lines go one per line, and the last ends in a comma, before the `=>`:

```php
$label = match ($code) {
    301,
    302,
    307, => 'Redirect',
    default => 'Other',
};
```

The list is split when it doesn't start and end on the same line. That includes a list with a condition that spans
lines, such as an array:

```php
$label = match ($code) {
    [
        301,
        302,
    ],
    $other,
    [
        307,
    ], => 'Redirect',
    default => 'Other',
};
```

A line break before the `=>` isn't in the list, so it doesn't split it. The `=>` may follow the last comma, as above, or
start the next line.

**Why:** 2.6 says "If the list is split across multiple lines, then the last item MUST have a trailing comma", and makes
no exception for this list. One condition per line follows every other list the spec splits: arguments (4.7),
parameters (4.5), an array's values (11) and interfaces (4.1).

---

### A Comma That Starts a Line

**Issue:** 4.5 and 4.7 say there MUST NOT be a space before a comma in a parameter or argument list. The spec doesn't
say whether a line break counts, or cover other lists.

**Decision:** A comma may not start a line, in any list, including after a heredoc's closing marker.

**Why:** A line break before a comma is space before it, and every split list in the spec ends its lines with the comma.
Section 10's example puts the comma straight after a heredoc's closing marker.

---

## 2.7 Naming

### Acronyms

**Issue:** 2.7 RECOMMENDS two rules from the php-src coding standards. Abbreviations, acronyms and initialisms "SHOULD
be avoided wherever possible, unless they are much more widely used than the long form (e.g. HTTP or URL)". And they
"SHOULD be treated like regular words", so written with an upper-case first character, then lower case. It doesn't say
which names the rules cover, or what counts as an acronym.

**Decision:** Only the second rule is checked. `PERCS31.NamingConventions.AcronymCasing` applies it to everything except
upper-case constants (global or otherwise) as a severity 5 warning.

- An acronym is a word of two or more capitals, so `HTTPClient`, `getID`, `parseURL` and `IHeartAI` are reported.
- A single capital is an initial, not an acronym, so `ClassA`, `EDayOfWeek` and `getX` pass.
- There's no plural rule because code is not always written in English, and different languages form plurals in
  different ways. `getURLs` reads as `get`, `UR`, `Ls`, and is reported. `getIDs` reads as `get`, `I`, `Ds`, and passes.
- A constant with no lower-case letter, such as `HTTP_OK`, is UPPER_CASE, and isn't checked. That covers a global
  constant, from `const` or `define()`, and a class, interface, trait or enum constant. Every other name is checked, so
  `$ID`, `ID()` and `class HTTP` are reported.

**Why:** The first rule would need a dictionary of acronyms, and of which are more widely used than their long form,
which isn't practical. The second isn't limited to one kind of name. Splitting a name into words can only be a best
effort, so a simple rule is applied consistently.

---

## 3. Declare Statements, Namespace, and Import Statements

### Several Imports in One `use`

**Issue:** The header has "One or more class-based `use` import statements", but the spec doesn't say whether one
statement may import several names.

**Decision:** `use A, B;` is allowed.

**Why:** PSR-2 required one `use` keyword per declaration. PSR-12 dropped that rule when it added group `use`, and PER
doesn't have it either.

---

### A Group `use` Split Over Lines

**Issue:** Section 3 shows a group `use` on one line, and one split over lines with a name on each line. Its text
doesn't say how a split one is laid out.

**Decision:** A group `use` is written in one of two ways. All on one line:

```php
use A\{B, C};
```

Or split, with the first name on the line after the `{`, one name per line, indented once, and the `}` on a line of its
own:

```php
use A\{
    B,
    C,
};
```

The comma after the last name is 2.6's rule for a list split over lines. See [Which Lists](#which-lists).

Anything in between is reported. Each of these is wrong:

```php
// The first name is on the `{` line.
use A\{B,
    C,
};

// Two names are on one line.
use A\{
    B, C,
};

// The `}` isn't on a line of its own.
use A\{
    B,
    C};
```

A blank line after the `{` or before the `}` is also reported, as for an array.

**Why:** The two examples are the only forms the spec shows, and they match its rules for a single-line and a multi-line
array (11).

---

### Spacing in a Group `use`

**Issue:** No rule in section 3 sets the spacing in a group `use`. Its examples, and the overview's, which "encompasses
some rules below", all write one the same way, as in `use Vendor\Package\{ClassA as A, ClassB, ClassC as C};`.

**Decision:** The examples' spacing is required.

- On one line: no space after the `{` or before the `}`, one space after each comma and none before it, no trailing
  comma, and one space each side of `as`.
- Split over lines: one name per line, indented once, a trailing comma after the last, and one space each side of `as`.

The space around `as`, and after `use`, `function` and `const`, is checked in every import, not only a group.

**Why:** The examples agree with each other, and with how the spec writes every other list in brackets: an array, a
call's arguments and an attribute block.

---

### Imports After Code

**Issue:** The header's blocks end with "The remainder of the code in the file", but the spec doesn't say anything about
an import after that.

**Decision:** An import after the rest of the code is reported. A file with more than one namespace is skipped, as each
namespace has its own imports.

**Why:** An import after the code breaks the header's order.

---

### A `declare` After the Header

**Issue:** The header has "One or more declare statements", and each block "MUST be in the order listed". The spec
doesn't say whether that covers a `declare` statement after the rest of the code, or after markup.

**Decision:** It doesn't. Only the `declare` statements in the header are checked for their order. One anywhere else
isn't checked, so this passes:

```php
<?php

namespace Vendor\Package;

run();

declare(ticks=1);
```

**Why:** A `declare` after the header is part of the code, not of the header. `declare(ticks=1)` applies from where
it's written, so it has a reason to be anywhere. PHP itself rejects a `strict_types` declaration that isn't the first
statement, and rejects or ignores a misplaced `encoding` one. Catching code that doesn't work is PHP's job, not a
coding standard's.

---

### `declare` Statements

**Issue:** "Declare statements MUST NOT contain any spaces inside the parentheses" doesn't say whether that includes
after a comma between directives.

**Decision:** It does, so `declare(ticks=1,strict_types=1);` is the correct form. Several directives and string values
are allowed. There are no blank lines between `declare` statements.

**Why:** A space after the comma is still inside the parentheses. A `declare` statement is a special case: its `=` has
no space either side, where 6.2 requires one around every other `=`. So its comma has none after it either, where every
other comma has one.

---

### Blank Lines in a Block `declare`

**Issue:** "Block declare statements are allowed and MUST be formatted as below. Note position of braces and spacing".
The example has no blank line after its `{` or before its `}`, but the text doesn't forbid one, and 2.3 allows blank
lines "except where explicitly forbidden".

**Decision:** A blank line straight after a block `declare`'s opening brace, or straight before its closing one, is
reported. One between two statements in the block is allowed.

**Why:** A block `declare` is a control structure, as PHP's manual lists it, so section 5 applies: "The body MUST be on
the next line after the opening brace", and "The closing brace MUST be on the next line after the body". The example is
also a template for the format, and has neither blank line.

---

### `strict_types` After a Shebang Line

**Issue:** In a file with markup outside PHP tags, the strict types declaration "MUST be on the first line of the file".
A shebang line must be on the first line too, so a script with both can't follow the rule.

**Decision:** In a file that starts with a shebang line, the declaration goes on the second line:

```php
#!/usr/bin/env php
<?php declare(strict_types=1) ?>
<html>
```

**Why:** PHP skips a shebang line, so the second line is the first one it reads.

---

### `strict_types=0`

**Issue:** The first-line rule applies "when wishing to declare strict types" in a file with markup. The spec doesn't
say whether `declare(strict_types=0)`, which turns strict types off, counts.

**Decision:** It does. In a file with markup, the declaration goes on the first line whatever its value:

```php
<?php declare(strict_types=0) ?>
<html>
```

`strict_types=0` is otherwise allowed, as PER doesn't require strict types.

**Why:** The rule is about where the statement goes, and it's the same statement with either value.

---

## 4. Classes, Properties, and Methods

### "Any Closing Brace"

**Issue:** "Any closing brace MUST NOT be followed by any comment or statement on the same line" says "any", but it sits
in section 4, which is about classes, properties and methods. Read literally, it would also forbid `} else {`, `});` and
a comment after an `if`.

**Decision:** The rule covers the braces section 4 is about: those of a class, interface, trait, enum, method, function,
property hook list and hook body. Nothing follows one of those on the same line.

```php
class Foo
{
    public function bar(): void
    {
    } // Reported.
} // Reported.
```

Other closing braces follow 2.3 instead, "There MUST NOT be more than one statement per line":

| Closing brace of                      | A comment after it | A new statement after it |
| ------------------------------------- | ------------------ | ------------------------ |
| A closure, anonymous class or `match` | Allowed            | Reported                 |
| A control structure                   | Allowed            | Reported                 |

- A closure, an anonymous class and a `match` are values, so one can be an argument, a list item or an operand. The rest
  of the statement carries on after the brace, as in `});`, `}, $b);` and `} + 1;`.
- `else`, `elseif`, `catch`, `finally` and a `do` loop's `while` belong to the same statement as the brace before them,
  so `} else {` is allowed.
- A comment isn't a statement, so 2.3 doesn't forbid one.
- A new statement, as in `} run();` or `} break;`, breaks 2.3.

**Why:** The section, and how PHP is written, both point to the narrower reading. The spec's own examples have
`} elseif ($expr2) {` (5.1) and `});` (4.7), which the literal reading would forbid.

---

### Parentheses Around `new`

**Issue:** "When accessing a class member immediately after instantiating a new class, the instantiation SHOULD NOT be
wrapped in parentheses." The spec doesn't say which parentheses wrap. In `make(new Foo())->bar`, a `new` is in
parentheses, and a member is accessed straight after them.

**Decision:** Only parentheses that group an expression are reported, as in `(new Foo())->bar()`. Parentheses that hold
a call's arguments aren't, whatever comes after them.

- A function's name, a variable or another callable before the `(` makes them a call's, as in `make(new Foo())->bar`.
- A keyword before the `(` doesn't. So they're reported after `echo`, `print`, `return`, `yield`, `throw`, `include` and
  `require`, and inside `isset()`, `empty()`, `eval()`, `exit()` and `array()`.
- `clone(new Foo())->bar` is reported, and written `clone(new Foo()->bar)`, by the rule for `clone`'s parentheses in
  4.7.

**Why:** The two kinds do different jobs. Grouping parentheses set the order of operations, and PHP 8.4 made them
unnecessary around `new`. A call's parentheses are part of the call.

PHP reads `clone(new Foo())->bar` as a clone of `bar`, not of the new object, so those parentheses group `new Foo()`
too. 4.7 says `clone` SHOULD always have parentheses, so they move to hold what is really cloned. The same goes for any
`clone($a)->b`, which is written `clone($a->b)`, and `clone($a + $b)->c`, which is written `clone(($a + $b)->c)`.

---

## 4.2 Using Traits

### Blank Lines Around `use`

**Issue:** 4.2 says a class with more in it "MUST have a blank line after the `use` import statement". It doesn't say
exactly one, or whether a blank line may come between two `use` statements.

**Decision:** No blank line between trait `use` statements, and exactly one after the last, so two or more are reported.

**Why:** "A blank line" is read as one, as "a space" is in 4.3. 4.2's examples show no blank line between `use`
statements, and one after the last.

---

### A Comment Before the First `use`

**Issue:** 4.2 says the `use` keyword for traits "MUST be declared on the next line after the opening brace". It doesn't
say whether a comment may come between.

**Decision:** A comment or docblock may come between the opening brace and the first trait `use`. A blank line, or any
other code, is reported.

```php
class Foo
{
    /** @use HasItems<Item> */
    use HasItems;
}
```

**Why:** A comment belongs with the code after it, so the `use` is still the first thing in the class. A docblock on a
trait `use` is the usual way to give a generic trait its types. PHP_CodeSniffer reads the same PSR-12 wording this way.

---

## 4.3 Properties and Constants

### A Double Underscore Prefix

**Issue:** Names "MUST NOT be prefixed with a single underscore". It's unclear whether a double one is covered.

**Decision:** `$__a`, `__A` and `__a()` pass.

**Why:** "Single" excludes a double underscore, which PHP uses for magic methods. PSR-2's method sniff reads it the same
way.

---

### Visibility on a Promoted Parameter

**Issue:** 4.3 requires visibility on properties, but doesn't mention constructor promotion, where `readonly` alone
makes a property.

**Decision:** A promoted parameter with no visibility, such as `readonly int $a`, is reported. A set-visibility, as in
`private(set)`, counts.

**Why:** A promoted parameter declares a property, so the property rule applies.

---

## 4.4 Methods and Functions

### A Body With Only a Comment

**Issue:** An empty body is abbreviated as `{}`. 4.4 defines empty for a function or method as "no statements or
comments". The class rule (4) says "no additional declarations", and the closure rule (7) says "no statements", so
neither says whether a comment counts.

**Decision:** A body with only a comment isn't empty, for a class, a function, a closure and an anonymous class. So this
passes:

```php
$noOp = function (): void {
    // Nothing to do.
};
```

**Why:** 4.4's definition is the only one that mentions comments, so it's used for all four. Abbreviating the body would
also delete the comment.

---

## 4.5 Method and Function Parameters

### Blank Lines in a List

**Issue:** 2.3 says "Blank lines MAY be added to improve readability and to indicate related blocks of code except where
explicitly forbidden". The spec's examples rarely show a blank line between two items of a list, so it's unclear whether
one is allowed there.

**Decision:** It is, in every list: parameters, a closure's `use` variables, a call's or an attribute's arguments, an
array's values, a `match`'s arms, a group `use`'s names and interfaces after `implements`. A comment between two items
is allowed too. So this passes:

```php
$mailer->send(
    $recipient,
    $subject,

    // The body, in both formats.
    $html,
    $text,
);
```

A blank line is only reported where the text rules it out:

- Straight after a list's opening bracket, as the first item goes "on the next line" (4.5, 4.7, 11).
- Straight before its closing bracket, as that goes on the line after the last item. 11 says so for an array, and 4.5
  puts a parameter list's `)` and `{` "together on their own line". 4.7 doesn't say it for a call, which follows the
  same pattern.
- After an attribute, as "There MUST NOT be any blank lines between... the attributes and the structure" (12.2).

**Why:** 2.3 only excludes a blank line that's explicitly forbidden, and an example that doesn't happen to have one
isn't a ban. 12.4's example has a blank line between two parameters, which shows the spec's authors use them this way.

---

### A Split Return Type

**Issue:** A union return type can be split over lines (2.5). 4.5 puts `)` and `{` together in a split parameter list.
It's unclear how the two combine.

**Decision:**

- After a split parameter list, a return type split over lines is reported, as it separates `)` and `{`:

  ```php
  function a(
      int $b,
  ): A
  |B {
      // ...
  }
  ```

- After a single-line parameter list, it passes. Each line has one type, as 2.5 requires, and the return type starts on
  the `)` line, as 4.5 requires:

  ```php
  function a(): int
      |string
  {
      // ...
  }
  ```

**Why:** Each rule is followed as written.

---

### A Wrapped Default Value

**Issue:** 4.5 says each line of a split parameter list "is indented once". 6.4 says the same of a statement split at an
operator. A default value wrapped at an operator is covered by both.

**Decision:** The wrapped line is indented once past its parameter. Level with the parameter is reported. The same goes
for a ternary default, and in a closure's or arrow function's parameters.

```php
function greet(
    string $name = 'a'
        . 'b',
): void {
    // ...
}
```

**Why:** The wrapped line is a continuation of the line before it, not a parameter. 4.5's rule places each parameter,
and 6.4's places the rest of its expression, as for an operator wrapped in a call's argument.

---

### A Split Array Default Value

**Issue:** 4.7 lets a single argument, such as an array, split over lines without splitting the argument list. It's
unclear whether the same applies to a parameter's default value.

**Decision:** It doesn't. An array default value over several lines splits the parameter list, so this is reported:

```php
function foo(int $a, array $f = [
    1,
]): void {
    // ...
}
```

It's written as any other split parameter list is, with one parameter per line:

```php
function foo(
    int $a,
    array $f = [
        1,
    ],
): void {
    // ...
}
```

**Why:** 4.7's exemption is only for calls.

---

### A Reference `&` Outside a Parameter List

**Issue:** "When using the reference operator `&` before an argument, there MUST NOT be a space after it." The spec
doesn't say whether that covers a reference `&` anywhere else, as in `$x = & $a`.

**Decision:** It does. No white space follows a reference `&`, wherever it's used:

```php
$x = &$a;

foreach ($items as &$item) {
    // ...
}

$pair = [&$a, 'b' => &$b];

$add = function () use (&$total): void {
    // ...
};

function &find(array &$items): mixed
{
    // ...
}

class Basket
{
    public array $items = [] {
        &get => $this->items;
    }
}
```

A space or a line break after the `&` in any of these is an error, so `& get` is reported as `$x = & $a` is.

**Why:** It's the same operator in each place, so the same rule is used. 4.5 states it where the spec happens to show
the operator, which is in a parameter list.

---

### The Spread Operator Outside a Parameter List

**Issue:** "There MUST NOT be a space between the variadic three dot operator and the argument name." The spec doesn't
say whether that covers a `...` anywhere else, as in `foo(... $args)`.

**Decision:** It does. No white space follows a spread `...`, wherever it's used:

```php
function sum(int ...$numbers): int
{
    // ...
}

$total = sum(...$numbers);

$all = [...$first, ...$second];
```

A space or a line break after the `...` in any of these is an error.

The `...` of a first-class callable, as in `strlen(...)`, isn't a spread, as it has nothing after it to unpack. 4.8
sets its spacing.

**Why:** It's the same operator in each place, so the same rule is used, as for
[A Reference `&` Outside a Parameter List](#a-reference--outside-a-parameter-list).

---

## 4.6 Modifier Keywords

### Modifier Order on Constants

**Issue:** 4.6 sets the order of modifier keywords for "classes, properties, and methods". It doesn't name constants.

**Decision:** The order is checked everywhere a modifier keyword can be used, so on a constant too. `final` comes before
the visibility, as in `final public const A`. The single space between keywords is checked there as well.

**Why:** The order is a property of the keywords, not of what they're on. A constant only takes two of them, an
inheritance modifier and a visibility, and 4.6 puts those in that order.

---

### Spacing in a Constant Declaration

**Issue:** 4.6 requires a single space between keywords, and 4.3 requires a space between a property's type and its
name. Neither says how a constant's `const`, type and name are spaced.

**Decision:** A class constant has exactly one space after `const`, and between its type and its name, so
`public const  int  A = 1;` is reported. A line break counts as more than one space. A global constant isn't checked.

**Why:** A constant is declared like a property, so the same spacing applies. Section 4 is about classes, so the rule
stops at class constants.

---

## 4.7 Method and Function Calls

### A Space Before `clone`'s or `exit`'s Parentheses

**Issue:** 4.7 says there MUST NOT be a space between a function's name and its opening parenthesis. It also says the
`clone()`, `exit()` and `die()` functions SHOULD be called with parentheses. It's unclear which keyword applies to a
space before those parentheses, as in `clone ($a)`.

**Decision:** The MUST does. `clone ($a)`, `exit (1)` and `die ('x')` are errors, as `foo ($a)` is. Leaving the
parentheses out, as in `clone $a` or `exit;`, is a warning.

**Why:** The SHOULD is about whether to use parentheses. With them, each is a function call, which is what the spec
calls all three, and the rule for a call's parentheses applies.

---

### `require` and `include` With Parentheses

**Issue:** 4.7 says there MUST NOT be a space between a function's name and its opening parenthesis. The spec doesn't
say whether `require ($file)` is a call. Its only example of either is in PSR-1's section on side effects, which writes
`include "file.php";`.

**Decision:** It isn't checked. `require`, `include` and their `_once` forms may be written with or without parentheses,
and with a space before them.

**Why:** They're language constructs, not functions, and parentheses after one only group its expression, as in
`include ($a ? 'x' : 'y') . '.php';`. PSR-1's example is there to show a side effect, not a layout, so it shows one
allowed form, and no rule covers them.

---

### Blank Lines Between Arguments

**Issue:** 4.7 doesn't forbid a blank line between a call's arguments, and none of its examples has one.

**Decision:** One is allowed, as in any list. See [Blank Lines in a List](#blank-lines-in-a-list) (4.5). A blank line
straight after the call's `(`, or straight before its `)`, is reported.

**Why:** 2.3 allows blank lines "except where explicitly forbidden". PHP_CodeSniffer's PSR-12 standard reports one here,
but nothing in the text does.

---

### Method Chaining

**Issue:** When a chain is split, "the first method MUST be on the next line", but the spec doesn't say what counts as
the start of the chain.

**Decision:** The first method call goes on the next line. Property accesses before it, as in `$this->em`, may stay. A
static call may stay too, as the receiver, so this passes:

```php
Foo::create()
    ->run();
```

**Why:** The rule is about methods. A property access or a static call is the object the chain starts from.

---

### A Chain on a Continuation Line

**Issue:** A split chain's lines are "indented once", but the spec doesn't say from where. It only matters when the
chain starts on a continuation line, as a ternary's branch does, or an operand after a wrapped operator.

**Decision:** They're indented once past the line the chain starts on, the one with its object. So after `? $a` at 4
spaces, `->b()` is at 8. Level with `? $a` is reported.

**Why:** A chain's wrapped line continues the line before it, so it's indented once more, as 6.4 indents any line that
continues another. Lined up with its object, it can't be told from the ternary's own lines.

---

### `->` at the End of a Line

**Issue:** "Method chaining MAY be put on separate lines, where each subsequent line is indented once. When doing so,
the first method MUST be on the next line." Only the example shows where the `->` goes. This meets the text, as the
method is on the next line:

```php
$a->
    b();
```

**Decision:** A `->` or `?->` starts its line, so one at the end of a line, as above, is reported, whether the statement
has one or several.

**Why:** The example shows it, and 6.4 puts every wrapped operator at the start of a line. One rule for every `->` is
simpler than a rule that depends on how many a statement has.

---

### Closures as Arguments

**Issue:** 4.7 doesn't say whether a closure split over lines splits the argument list.

**Decision:** It doesn't. A named argument's closure may keep its attribute inline, as in
`callback: #[Pure] function () {`.

**Why:** 4.7's examples pass a multi-line closure as one argument, and section 7 covers a closure in an argument list.

---

## 4.9 Property Hooks

### Hook Layout

**Issue:** 4.9's text doesn't set the spacing before a hook's own `{`, or say whether two hooks on one line are two
statements.

**Decision:**

- One space before a hook's own `{`, as in `get {`.
- `{ get; set; }` on one line is one block, not two statements.

A by-reference hook's `&` is covered by
[A Reference `&` Outside a Parameter List](#a-reference--outside-a-parameter-list) (4.5).

**Why:** The first follows 4.9's examples. The second follows 4.10's, which write `{ get; set; }` on one line.

---

### `set` Hook Parameters

**Issue:** 4.9's text doesn't set the spacing of a `set` hook's parameter list.

**Decision:** No space after `set`, and none just inside the parentheses. `set (string $value)` and
`set( string $value )` are reported, and fixed to `set(string $value)`.

**Why:** 4.9's example writes `set(string $value)`, which matches the rule for a method's parameters (4.4). A hook is
declared like a method, so it isn't read as a call.

---

### Blank Lines Between Hooks

**Issue:** 4.9 says hooks "MUST be separated by at least a single line break", and "MAY be separated by an additional
blank line to aid readability". 2.3 allows blank lines "except where explicitly forbidden". A line break isn't a blank
line, so it's easy to misread this as requiring one or two blank lines.

**Decision:** No blank line or one between hooks. Two or more are reported.

- A single line break puts the next hook on a new line, with no blank line between them. That's the minimum.
- "An additional blank line" is one more line break, so one blank line. That's the maximum.

4.9's example shows both:

```php
public string $department {
    get {
        return $this->values[__PROPERTY__];
    }
    set {
        $this->values[__PROPERTY__] = $value;
    }
}
// or
public string $department {
    get {
        return $this->values[__PROPERTY__];
    }

    set {
        $this->values[__PROPERTY__] = $value;
    }
}
```

**Why:** The MAY names the one thing allowed, "an additional blank line", and "a blank line" is read as one, as in
"Blank Lines Around `use`" (4.2). Unlike a list, where 2.3 is all there is to go on, the spec says here how many.

---

## 4.10 Interface and Abstract Properties

### `get` Before `set`

**Issue:** 4.10 requires `get` before `set` for an abstract or interface property, whose hooks have no bodies. It says
nothing about other hook lists.

**Decision:** `get` comes first in every hook list.

**Why:** It makes no sense to require `get` before `set` when the hooks have no bodies, and not care about the order
when they do. The order of two hooks doesn't depend on what's in them, and every example in 4.9 has `get` first too.

---

## 5. Control Structures

### An Empty Body, or No Body

**Issue:** "The body of each structure MUST be enclosed by braces", and "the body MUST be on the next line after the
opening brace". The spec doesn't say what either means for a structure with nothing in it. That comes in two forms: an
empty body, with braces and nothing between them, and no body at all, as in `while (next($items));`.

**Decision:**

- **No body:** the structure MUST have braces, so `while (next($items));` and `else;` are errors.
- **An empty body:** it SHOULD be written as `{}` on the same line, after a single space. The braces on separate lines
  get a warning.

So these are right:

```php
while (next($items)) {}

try {
    run();
} catch (Exception $e) {}

if ($ready) {
    run();
} else {}
```

These are errors:

```php
while (next($items));

if ($ready) {
    run();
} else;
```

This gets a warning:

```php
try {
    run();
} catch (Exception $e) {
}
```

**Why:** The rule says each structure's body MUST be enclosed by braces, and makes no exception for one with no body. A
`;` straight after a condition is also easy to add by accident, and to miss when reading.

For an empty body, the spec has no rule in section 5. `{}` is how it writes every other empty body, and it says SHOULD
for a class's (4) and a function's (4.4), so the same severity is used here.

---

### "Indented at Least Once"

**Issue:** A split condition's "subsequent line is indented at least once", but the spec doesn't say how far is too far,
or how it applies to a `case`.

**Decision:** It applies to a split condition in a control structure's parentheses, including a `case` condition. Any
indent of at least one level passes. `case (` with the first condition on the same line is reported.

**Why:** "At least once" sets a minimum. Each control structure says "the first condition MUST be on the next line".

---

### A Condition Split Only Inside a Call

**Issue:** A control structure's condition can span lines only because a call or array in it does, as in `if (foo(`. 4.7
says a single argument split across lines "does not constitute splitting the argument list itself". It's unclear whether
the same goes for a condition.

**Decision:** It doesn't. A condition that spans lines is split, wherever the line breaks are, so its first condition
goes on the line after `(`, and `)` with `{` on a line of their own. The same goes for a `case` condition, which is then
wrapped in parentheses:

```php
if (
    $a && foo(
        $b,
    )
) {
    run();
}

switch (true) {
    case (
        foo(
            $a,
        )
    ):
        run();
        break;
}
```

**Why:** 4.7's exemption is only for a call's argument list, as "A Split Array Default Value" (4.5) and "One Value Split
Over Lines" (11) read it too. Each control structure's rule is about its expression spanning lines, not about where the
breaks fall. PHP_CodeSniffer reads the same PSR-12 wording this way.

---

## 5.2 `switch`, `case`, `match`

### Single-line `match`

**Issue:** 2.6 names "`match()` statement branches" among the lists that take a trailing comma, which suggests a
single-line `match` is possible, even if extremely rare in practice. Section 5 puts a control structure's body on the
line after its opening brace.

**Decision:** A `match` on one line is reported.

**Why:** 2.6 sets the trailing comma rule for a `match`'s arms, and doesn't say a `match` may be on one line. 5.2 is
more specific about how a `match` is written, and its example has each arm on its own line.

---

### The `match` Example

**Issue:** 5.2's `match` example shows spacing and layout its text doesn't state.

**Decision:** At least one space each side of `=>`, one space after each comma, one arm per line, a trailing comma after
each arm, and `}` on its own line. The comma spacing is also applied to lists with no example, such as `echo`, `global`
and a group `use`.

The arrows may be lined up, as an array's may. See [`=>` in a `match` Arm, an Array or `yield`](#-in-a-match-arm-an-array-or-yield).

**Why:** The example is the template for a `match`. One space after a comma is the spec's pattern everywhere it shows
one.

---

### A `match`'s Subject Split Over Lines

**Issue:** 5.2 gives `switch` the rule for an expression split inside the parentheses, with the first condition on the
next line. Of a `match`, it only says it "looks like" its example.

**Decision:** The split rule isn't applied to a `match`. Only the spacing just inside the parentheses is checked. So
this passes:

```php
$result = match ($this->lookup([
    'a',
    'b',
])) {
    1 => 'one',
    default => 'other',
};
```

**Why:** The rule applies where the spec states it, which is how "5.4 `for` and 5.5 `foreach`" reads it for `foreach`.
Nothing else in 5.2 rules this layout out. A structure that has the rule, such as `switch`, would put the call on the
line after `(`, as "A Condition Split Only Inside a Call" (5.1) says.

---

### The `case` Line

**Issue:** The `case` line "MUST consist only of" the label, but it's unclear whether that includes a comment.

**Decision:** It doesn't. A comment is allowed there, as in `case 1: // The first.` Anything else is reported.

**Why:** The rule describes the code on the line. Where the spec bans a comment, it says so, as it does after a closing
brace (4). PHP_CodeSniffer reads the same PSR-12 rule this way.

---

### Blank Line After a `case` Label

**Issue:** 5.2 doesn't forbid a blank line straight after a `case` or `default` label.

**Decision:** It's reported.

**Why:** 5.2's example, introduced as a template for its rules, has none. It follows the pattern of section 5, where a
structure's body "MUST be on the next line after the opening brace". A `case` has a colon in place of a brace.

---

### `default`

**Issue:** 5.2 requires a comment when a non-empty `case` falls through, and says a `case` body MUST NOT be wrapped in
`{}`. It doesn't say whether either rule covers `default`.

**Decision:** Both do. A non-empty `default` that falls through needs a comment, and a braced `default` body is
reported.

**Why:** A `default` is a `case` in all but name. It can fall through, and take braces, just as a `case` can.

---

## 5.4 `for` and 5.5 `foreach`

### Spacing

**Issue:** The text doesn't set the spacing around `;`, `=>` and `as`. The examples do.

**Decision:** The examples' spacing is required. In a `foreach` split inside its parentheses, a line break may take the
place of the space before or after `as` or `=>`. Otherwise it only gets section 5's general spacing.

**Why:** The examples are the only guide. No example splits a `foreach`, and no rule says where it may be split.

---

### An Empty Expression in a `for`

**Issue:** 5.4's example has three expressions, so it doesn't show how an empty one is spaced.

**Decision:** No space comes before a semicolon, whether or not an expression does. So an empty middle expression
leaves the two semicolons together:

| Code                    | Result                                   |
| ----------------------- | ---------------------------------------- |
| `for (;;)`              | Passes                                   |
| `for ($i = 0;; $i++)`   | Passes                                   |
| `for (; $i < 10; $i++)` | Passes                                   |
| `for ($i = 0; ; $i++)`  | Reported, fixed to `for ($i = 0;; $i++)` |
| `for ( ; ; )`           | Reported, fixed to `for (;;)`            |

**Why:** The example has no space before a semicolon and one after. Keeping that as one rule, with no special case for
an empty expression, matches `for (;;)`, which is always written without spaces.

---

## 6. Operators

### Operators Not Listed

**Issue:** Section 6 lists operators by kind, and says "All operators not described here are left undefined". It leaves
out unary `+`, `-`, `!`, `~` and `@`, a reference `&` and a spread `...`. It also leaves out `->`, `?->` and `::`,
though it's debatable whether those are operators at all: PHP's operator precedence table doesn't list them.

**Decision:** Section 6 sets no rule for any of these. One is only checked where another section gives it a rule:

- `->`, `?->` and `::` have no white space around them, as an error, by 4.7's and 4.2's examples.
  - `::` has none on either side, not even a line break, as in `Foo::bar()` and `self::Spades`.
  - `->` and `?->` have none after them. They have none before them either, unless one starts a line in a split chain,
    where only the line break and the indent come before it.
- A reference `&` and a spread `...` have no white space after them, by 4.5's rules. See
  [A Reference `&` Outside a Parameter List](#a-reference--outside-a-parameter-list) and
  [The Spread Operator Outside a Parameter List](#the-spread-operator-outside-a-parameter-list).
- Unary `+`, `-`, `!`, `~` and `@` aren't checked, as no section has a rule for them. Jumbo checks them.

**Why:** "Left undefined" means section 6 has no rule for an operator, not that no other section may have one. The spec
presents these examples as templates, which makes each one a rule (see
[Rule Text and Examples](#rule-text-and-examples)):

- `$foo->bar($arg1);` and `Foo::bar($arg2, $arg3);`, in 4.7's "lines [that] show correct calls".
- `A::smallTalk insteadof B;` and `C::mediumTalk as FooBar;`, in 4.2, which says `insteadof` and `as` "MUST be used as
  follows taking note of indentation, spacing, and new lines".

Every other example agrees, such as `BazClass::bar($arg2, $arg3);` in the overview and
`public const Wild = self::Spades;` in section 9. 4.7's chain example starts each line with `->`. 4.2's MUST sets the
severity.

It's the same reading as for `=>`, which section 6 doesn't list either: see [`=>` in a `match` Arm, an Array or `yield`](#-in-a-match-arm-an-array-or-yield).

---

## 6.2 Binary Operators

### `=>` in a `match` Arm, an Array or `yield`

**Issue:** `=>` isn't one of 6.2's operators. 5.2's `match` example and section 11's array example each have one space
each side, but there are too few arrows in them to rule out additional spaces being used to align arrows, which is not
uncommon.

**Decision:** A `match` arm's `=>` and an array's each need at least one space each side. More are allowed, so both of
these pass:

```php
$label = match ($size) {
    1 => 'one',
    default => 'many',
};

$label = match ($size) {
    1       => 'one',
    default => 'many',
};
```

The same goes for a `yield`'s `=>`.

**Why:** The spec always shows a space each side, and never none. It gives too little to forbid alignment, and section 6
says "multiple spaces MAY be used for readability purposes" where space is permitted around an operator. One rule for
both keeps a `match` and an array consistent. The spec says nothing about a `yield`, so that follows the same rule.

---

### `=&`

**Issue:** It's unclear whether `=&` is one operator or `=` followed by a reference `&`. The spec (both PER-CS 3.1 and
PSR-1) show no examples, and the PHP documentation shows both joined and separated forms.

**Decision:** It's an `=` followed by a reference `&`. The `=` is a binary operator, so it needs at least one space each
side (6.2). The `&` is a unary operator, so it has no space after it (4.5). Only `$x = &$a` passes:

| Code        | Result                        |
| ----------- | ----------------------------- |
| `$x = &$a`  | Passes                        |
| `$x = & $a` | Reported, fixed to `$x = &$a` |
| `$x =& $a`  | Reported, fixed to `$x = &$a` |
| `$x =&$a`   | Reported, fixed to `$x = &$a` |
| `$x=&$a`    | Reported, fixed to `$x = &$a` |
| `$x=& $a`   | Reported, fixed to `$x = &$a` |

**Why:** PHP has no `=&` operator. It reads the `=` and the `&` as separate tokens, even though the `&` changes what the
assignment does. So each follows its own rule: 6.2's for an assignment operator, and 4.5's for a reference `&`, as
[A Reference `&` Outside a Parameter List](#a-reference--outside-a-parameter-list) reads it.

---

### A Default Value's `=`

**Issue:** It's unclear whether a parameter's default value `=` is an assignment operator.

**Decision:** It is, so it needs at least one space each side, and alignment is allowed.

**Why:** It looks and reads like an assignment. 6.2 requires "at least one space" around a binary operator, which leaves
room for alignment.

---

## 6.3 Ternary Operators

### `?:`

**Issue:** 6.3 doesn't say whether a short ternary's `?` and `:` may be apart, as in `$a ? : $b`.

**Decision:** They may not. The fix joins them into `?:`.

**Why:** 6.3 says the short ternary follows the rules for other binary operators, and its examples write `?:`.

---

## 6.4 Operator's Placement

### Where a Wrapped Operator Goes

**Issue:** 6.4 says a wrapped operator starts the next line, without saying whether that covers expressions inside a
list item, a `match` arm or parentheses.

**Decision:** It applies wherever an expression is split. The operator starts the line, indented once. This covers
assignment operators, `.`, and an arrow function's `=>`.

**Why:** The rule is about expressions, not statements.

---

### Operators in Grouping Parentheses

**Issue:** 6.4 says "each subsequent line is indented once", and 5.1 says a split condition's lines are "indented at
least once". Neither says how the lines inside grouping parentheses are indented, as in `$a && (` with `$b` and `|| $c`
on the lines after it.

**Decision:** Every line in grouping parentheses is indented once past the line with the `(`. So `|| $c` lines up with
`$b`, and isn't indented once more. That applies in a condition and in any other expression, such as `$x = $a` then
`&& (` on the next line. A call's argument parentheses aren't a group, so an operator wrapped inside an argument is
still indented once past the argument's first line. An arrow function's `=>` is always indented once past the line with
its own `fn`, in a group or not, as is an operator wrapped in its body. So in a nested arrow function, each wrapped `=>`
is indented once more than the one before it:

```php
$sum = fn(int $x)
    => fn(int $y)
        => fn(int $z)
            => $x + $y + $z;
```

**Why:** It's the layout 5.1's example gives a condition's own parentheses, one level down. The lines in a group read as
one level, and the line with the `(` shows that the group continues the statement.

---

### When a Ternary Is Split

**Issue:** 6.4 sets the layout of a split ternary, but not what counts as split.

**Decision:** A ternary is split only when a line break is right before or after its `?` or `:`, ignoring comments. Then
it takes the three-line form. A nested ternary is indented once more. A short ternary may be on two lines, with `?:`
starting the second.

**Why:** A value or condition split over lines doesn't change the ternary's structure.

---

### Heredoc After a Wrapped `=`

**Issue:** 6.4 lets `=` start a wrapped line. Section 10 puts a heredoc on its context's line.

**Decision:** A heredoc after a wrapped `=` is reported:

```php
$x
    = <<<EOT
    Hello $name.
    EOT;
```

**Why:** Section 10 is more specific.

---

## 7. Closures

### Empty Closures and Arrow Functions

**Issue:** "If possible, empty-body closures SHOULD be replaced with arrow functions", as in `fn() => null`.

**Decision:** It's possible unless the closure has a `void` or `never` return type, or returns by reference. So
`function () {}` gets a warning, and `function (): void {}` passes.

**Why:** An arrow function always returns a value, so `fn(): void => null` is a fatal error. `function () {}` and
`fn() => null` both return `null`, so replacing one with the other changes nothing.

---

## 7.1 Short Closures

### Arrow Functions

**Issue:** 7.1 gives arrow functions few rules of their own.

**Decision:** No space after `fn`. A split parameter list follows the closure rules. No space before the closing
semicolon. A wrapped `=>` is indented once past the line its own `fn` starts on, so each nested arrow function's wrapped
`=>` is indented once more than the one before it.

**Why:** An arrow function is a closure, so the closure rules fill the gaps.

---

## 8. Anonymous Classes

### The Opening Brace

**Issue:** "The opening brace MAY be on the same line as the `class` keyword so long as the list of `implements`
interfaces does not wrap." The spec doesn't say whether the brace may also go on the next line in that case.

**Decision:** It may. When the list doesn't wrap, the brace can go on either line:

```php
$a = new class implements Countable {
    // ...
};

$b = new class implements Countable
{
    // ...
};
```

When the list wraps, the spec leaves no choice: the brace MUST be on the line after the last interface.

```php
$c = new class implements
    ArrayAccess,
    Countable
{
    // ...
};
```

**Why:** "MAY" allows the same line, and doesn't forbid the next one.

---

### Around the Opening Brace

**Issue:** Section 8's text doesn't say how many spaces go before the opening brace, or whether the body may start on
its line.

**Decision:** One space before a brace on the declaration's line, and nothing after the brace on its line, not even a
comment. An empty body, `{}`, is OK.

**Why:** Section 8 says anonymous classes follow the same guidelines as closures, where both are required, and its
examples show both.

---

### After the Arguments

**Issue:** Section 8's text doesn't say whether a space goes between an anonymous class's arguments and `extends` or
`implements`. Its example writes `new class ($a) extends \Foo implements`.

**Decision:** Exactly one space is required, so `new class ($a)extends Foo` is reported, as an error, and fixed.

**Why:** The example shows it, and it's the spacing before `extends` and `implements` everywhere else. More than one
space there was already an error, so none is treated the same way.

---

### A Comment Before an Anonymous Class's Attributes

**Issue:** An anonymous class's attributes "MUST be placed starting on the next line after the `new` keyword". The spec
doesn't say whether a comment may come between.

**Decision:** A comment on the lines between `new` and the first attribute is allowed. A blank line is reported.

```php
$example = new
    // Handles the event.
    #[Attribute]
    class {
        // ...
    };
```

**Why:** A comment belongs with the code after it, so the attributes still start the class's own lines. A blank line
puts the attributes further from `new` for no purpose.

---

## 9. Enumerations

### Enum Constant Names

**Issue:** PSR-1 requires upper case for class constants, but section 9 allows PascalCase or UPPER_CASE for a constant
in an enum, and RECOMMENDS PascalCase.

**Decision:** PascalCase passes, UPPER_CASE gets a severity 5 warning, and anything else, such as `camelCase` or
`Foo_Bar`, is an error.

**Why:** Section 9 is more specific than PSR-1.

---

## 10. Heredoc and Nowdoc

### Placement and Indent

**Issue:** Section 10 puts a heredoc on its context's line, but some contexts, such as an argument list, already start
new lines.

**Decision:** A `<<<` must be on its context's line, except after `(`, `,`, `[` or a binary operator. The body and
closing marker are indented once past the `<<<` line. A heredoc with no interpolation or escape sequence gets a warning,
as it SHOULD be a nowdoc.

**Why:** After those tokens, a new line is the context's own layout.

---

## 11. Arrays

### The Opening Bracket

**Issue:** The opening bracket of a split array "MUST NOT be placed on its own line". Section 11's own example puts a
nested array's bracket at the start of a line. So does 4.7, for an array that's an argument in a split argument list, as
each argument there goes on its own line. So do 5.1 to 5.4, for an array that starts a split condition, as "the first
condition MUST be on the next line".

**Decision:** The rule is applied after `=`, `=>`, `return` and the like, so `$a = [`. The exceptions are:

- An element of a multi-line array, as in the example.
- An argument in an argument list. Both `foo([` and the first form below are allowed.
- A condition of a `match` arm.
- The first condition of a split `if`, `elseif`, `while`, `switch` or `for`, as in the second form below. `foreach ([`
  and `match ([` are required, as neither has a split rule. Jumbo gives both the split rule, as an extra, so there an
  array that starts either header goes on the line after the `(` too.
- A destructuring assignment.

```php
foo(
    [
        1,
        2,
    ],
);

if (
    [
        1,
        2,
    ] === $a
) {
    // ...
}
```

**Why:** The example shows the elements exception. An argument list split one per line leaves an array argument nowhere
else to start, short of assigning it to a variable first, which PER doesn't ask for. A `match` arm starts a line, so an
array that starts its conditions does. The same goes for a split condition: section 5's rule and section 11's can't both
be met. Section 11's own example breaks its rule, by starting a nested array on a line of its own, so that rule plainly
has exceptions. Section 5's has none, so it's the stronger of the two. A destructuring assignment is a list, not an
array value.

---

### One Value Split Over Lines

**Issue:** Section 11's rules apply "when an array declaration is split across multiple lines". It's unclear whether an
array is split when it only spans lines because its one value does, as in `[function () {` ... `}]`. 4.7 says a single
argument split over lines doesn't split an argument list.

**Decision:** It's split. The value goes on the line after `[`, and `]` on the line after the value.

**Why:** 4.7's exemption is only for calls, and section 11 has nothing like it.

---

### Spacing and the Closing Bracket

**Issue:** The text doesn't set comma spacing or space inside the brackets. It puts the first value "on the next line"
after the `[`, and the `]` "on the next line after the last value", without saying whether a blank line may come
between.

**Decision:** One space after a comma, no space inside the brackets, and no blank line after a multi-line array's `[` or
before its `]`. A blank line between two values is allowed. An empty array has no space or line break inside either, so
`[]`, not `[ ]`.

**Why:** Section 11 introduces its example as showing "correct array usage". A value two lines after the `[` isn't on
the next line, and the same goes for the `]`. 2.3 allows blank lines "except where explicitly forbidden", which leaves
the ones between values.

---

### A Closure Attribute in an Array Element

**Issue:** 12.2 doesn't say where a closure's attribute goes when the closure is an array element.

**Decision:** It may be on the closure's line or the line before.

**Why:** The attribute is part of the value, and neither placement breaks a rule.

---

## 12.2 Placement

### Targets 12.2 Doesn't List

**Issue:** 12.2 sets the placement for classes, methods, functions, constants, properties and parameters. It doesn't
mention enum cases, property hooks, or closures.

**Decision:**

- An enum case: the attribute goes on the line before, as for a constant.
- A hook with a body in braces, as in `get {`: the attribute goes on the line before, as for a method.
- A short hook, as in `get => 1;`, or one with no body, as in `get;`: the attribute may be on its line, followed by a
  single space, or on the line before.
- A closure as a call's argument follows the parameter rule. Elsewhere, such as in an assignment or an array, its
  attribute may be on the closure's line or the line before. One on the closure's line is followed by a single space,
  and two or more blocks go on lines of their own, before the closure.

An attribute that must be on the line before its target must also have that line to itself, so one that follows other
code on its line is reported, as in `case Hearts; #[A]`.

**Why:** Section 9 says enums follow the guidelines for class constants, and PHP itself treats an enum case's attribute
as a constant's. A hook with a body in braces is laid out like a method, so its attribute goes where a method's does. A
short hook or a hook with no body fits on a line, and a promoted property's hook has to (4.9), so the attribute may
share it. The single space follows 12.2's rule for a parameter in a single-line list. 12.2 puts two blocks "in a
multi-line context" on separate lines, and a closure outside a single-line list is one.

---

### Two Blocks in a Single-line List

**Issue:** Two attribute blocks "in a multi-line context" go on separate lines. The spec doesn't say whether two blocks
may share a single-line parameter list, as in `function a(#[A] #[B] int $b)`.

**Decision:** They may. The blocks stay inline, each followed by exactly one space. The same goes for a closure in a
single-line argument list.

**Why:** A single-line list isn't a multi-line context, and 12.2 requires a parameter's attribute inline there. The
single space follows 12.2's rule for an attribute and its parameter.

---

### Comments Between an Attribute and Its Target

**Issue:** 12.2 puts an attribute "immediately prior to" its target, or in a single-line parameter list, "inline with
the parameter it describes, separated by a single space". Both rules are written for one attribute. The spec doesn't say
how they apply to two or more blocks, or whether a comment may come between them.

**Decision:** The rules apply to every block, not only the last. So nothing but another attribute block comes between an
attribute and its target, on separate lines or inline. A comment there is reported.

On separate lines, these are right. A comment may come before the first block, or at the end of a block's line:

```php
#[Foo]
#[Bar]
public int $a = 1;

// Before the blocks.
#[Foo] // At the end of a line.
#[Bar] // At the end of the last line.
public int $b = 1;
```

Each of these comments is reported:

```php
#[Foo]
// Between blocks.
#[Bar]
// Before the target.
public int $a = 1;
```

Inline, these are right. A comment may come before the first block:

```php
function a(#[Foo] #[Bar] int $a): void {}

function b(/* Before the blocks. */ #[Foo] #[Bar] int $a): void {}
```

Both of these are reported:

```php
function a(#[Foo] /* Between blocks. */ #[Bar] int $a): void {}

function b(#[Foo] #[Bar] /* Before the parameter. */ int $a): void {}
```

This applies to every target, including a parameter, where 12.2 only says "prior to".

**Why:** It's the same reading in both layouts. A block that describes a target is as close to it as the other blocks
allow, as in [Two Blocks in a Single-line List](#two-blocks-in-a-single-line-list), where each block is followed by a single space. A comment at the end of a
line doesn't move an attribute any further from its target.

---

## See Also

- [PERCS31](PERCS31.md): the sniffs that apply these calls.
- [Jumbo](Jumbo.md): PERCS31 plus extra rules.
- [PER Coding Style 3.1](https://www.php-fig.org/per/coding-style/)
