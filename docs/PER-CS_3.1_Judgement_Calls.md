# PER-CS 3.1 Judgement Calls

[PER Coding Style 3.1](https://www.php-fig.org/per/coding-style/) and [PSR-1](https://www.php-fig.org/psr/psr-1/) leave some points unclear, contradict themselves in places, and say nothing about others.
A coding standard still has to do something in each case. This page lists the calls the [PERCS31](PERCS31.md) standard makes, and why.

Each item has three parts:

- **Issue:** the ambiguity, contradiction or omission in the spec, quoting the text in question.
- **Decision:** what PERCS31 does about it, with examples.
- **Why:** the reasoning.

Quotes are from the spec, and each links to its section. The general principles come first, as they settle most of the
other cases. The items after them follow the spec's section order.

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

Several examples in the spec break its own rules. In these cases, the text overrules the example.

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

a. The parameter list has no trailing comma. This contradicts [Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas), which says:

> If the list is split across multiple lines, then the last item MUST have a trailing comma.

b. The function returns `object|null`, not `?object`. This contradicts [Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types), which says:

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

The function has its `)` and `{` on different lines. This contradicts [Section 4.5](https://www.php-fig.org/per/coding-style/#45-method-and-function-parameters), which says:

> When the argument list is split across multiple lines, the closing parenthesis and opening brace MUST be placed
> together on their own line with one space between them.

Its parameter list has no trailing comma either, which contradicts the trailing comma rule quoted above.

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

[Section 2.1](https://www.php-fig.org/per/coding-style/#21-basic-coding-standard) reads StudlyCaps as PascalCase, and [section 4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says the term "class" "refers to all classes, interfaces,
traits, and enums". A name with no lower-case letter is UPPER_CASE, not PascalCase. See [PascalCase (PSR-1)](#pascalcase-psr-1).

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

The parameter list is split over lines, but has no trailing comma. This contradicts [Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas), which says:

> If the list is split across multiple lines, then the last item MUST have a trailing comma.

The example after it, marked "not allowed" for its hook, has no trailing comma either.

#### 4.10 Interface and Abstract Properties

This example is shown:

```php
abstract class Example {
    abstract public string $name {
        get => ucfirst($this->name);
        set;
    }
}
```

The class's opening brace is on the same line as its name. This contradicts [Section 4.1](https://www.php-fig.org/per/coding-style/#41-extends-and-implements), which says:

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

The constructor's body is empty, but isn't written as `{}`. This contradicts [Section 4.4](https://www.php-fig.org/per/coding-style/#44-methods-and-functions), which says:

> If a function or method contains no statements or comments
> (such as an empty no-op implementation or when using constructor property promotion), then the body SHOULD be
> abbreviated as `{}` and placed on the same line as the previous symbol, separated by a space.

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

The `$allowedHeredoc` heredoc has nothing to interpolate, so a nowdoc could be used. This contradicts [Section 10](https://www.php-fig.org/per/coding-style/#10-heredoc-and-nowdoc), which
says:

> A nowdoc SHOULD be used wherever possible.

---

### PER Replaces PSR-12

**Issue:** PER Coding Style replaces PSR-12, but many PHP_CodeSniffer sniffs were written for PSR-12, and some reject
code PER allows.

**Decision:** A PSR-12 sniff that rejects something PER allows is replaced, configured or excluded.

**Why:** PERCS31 implements PER 3.1. A PSR-12 report on code PER allows is a false positive.

---

### Blank Lines

**Issue:** Many items on this page come down to whether a blank line is allowed somewhere. The spec has one general
rule, in [Section 2.3](https://www.php-fig.org/per/coding-style/#23-lines):

> Blank lines MAY be added to improve readability and to indicate related blocks of code except where explicitly
> forbidden.

**Decision:** A blank line is only reported where the text rules it out, such as "MUST NOT be preceded by a blank line",
or where it puts one thing "on the next line" after another. An example that happens to have no blank line never forbids
one.

**Why:** "Except where explicitly forbidden" leaves no room for an implied ban. Where PERCS31 allows a blank line that's
rarely wanted, [Jumbo](Jumbo.md) reports it, as an extra rule.

---

### Spacing Shown by Example

**Issue:** The spec states some spacing rules, such as "There MUST be a space between type declaration and property
name". Many others it only shows: one space between `function` and a function's name, or between a visibility and a
property's name. It has no general rule for spacing, as it has for blank lines.

**Decision:** Where every example writes a construct with the same spacing, that spacing is required, as with one space
between a keyword and the name after it. This is different from a blank line, which is allowed wherever the text doesn't
explicitly forbid one (see [Blank Lines](#blank-lines)). So PERCS31 infers a spacing rule from the examples, but never a ban on a blank
line.

**Why:** Nothing in the spec permits irregular spacing, as it permits blank lines, and nobody means to write
`case Hearts`. These patterns are obvious, and a standard that left them unchecked would miss plain slips.

---

### Continuation Lines

**Issue:** The spec says how far several kinds of split line are indented, but not every kind. Its rule for a statement
split at an operator, in [Section 6.4](https://www.php-fig.org/per/coding-style/#64-operators-placement), is the most general:

> A statement that includes an operator MAY be split across multiple lines, where each subsequent line is indented once.

**Decision:** A line that continues the line before it, rather than starting a new item in a list, is indented once past
the line it continues. Several items below rely on this, such as [The Indent of a Split Type](#the-indent-of-a-split-type), [A Wrapped Default Value](#a-wrapped-default-value) and
[A Chain on a Continuation Line](#a-chain-on-a-continuation-line).

**Why:** The spec indents every split it describes once per level: a split argument list, parameter list, array, chain
and operator. A continuation line that isn't indented can't be told apart from the line it continues.

---

### The Split-Argument Exemption

**Issue:** [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says how to split an argument list, then makes an exception:

> A single argument being split across multiple lines (as might be the case with a closure or array) does not constitute
> splitting the argument list itself.

It's unclear whether the same goes for other lists and parentheses, such as a parameter's default value, a control
structure's condition or an array's one value.

**Decision:** The exemption covers argument lists only: a call's, an attribute's, an anonymous class's, and those of
`clone()`, `exit()` and `die()`. Anywhere else, a list or condition that spans lines is split, wherever the line breaks
fall.

```php
// An argument list: not split, so the closure may start on the call's line.
$app->get('/hello', function ($name) {
    return 'Hello ' . $name;
});
```

**Why:** The exemption is written for argument lists, in the section on calls, and nothing else in the spec has one like
it. See [A Split Array Default Value](#a-split-array-default-value), [A Condition Split Only Inside a Call](#a-condition-split-only-inside-a-call) and [One Value Split Over Lines](#one-value-split-over-lines).

---

### What Goes in PERCS31

**Issue:** The spec doesn't cover every formatting question, such as which line a comma goes on.

**Decision:** PERCS31 contains only the rules the spec requires, read as described on this page. Anything else is left
unchecked. Extra rules go in [Jumbo](Jumbo.md).

**Why:** PERCS31 is meant to be the spec as written, so a project can adopt it without taking on anyone's preferences.

---

## Overview

### Keywords

**Issue:** The spec uses RFC 2119 keywords, such as MUST and SHOULD, but doesn't say how a tool should report each one.

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

**Issue:** PSR-1 says "Class names MUST be declared in `StudlyCaps`", without defining StudlyCaps. [Section 2.1](https://www.php-fig.org/per/coding-style/#21-basic-coding-standard) says:

> The term "StudlyCaps" in PSR-1 MUST be interpreted as PascalCase where the first letter of each word is capitalized
> including the very first letter.

That doesn't settle whether `FOO`, `A` or `HTTPStatus` qualifies.

**Decision:**

- A PascalCase name starts with a capital, has only letters and digits, and has at least one lower-case letter.
- This applies to classes, interfaces, traits, enums and enum cases, as an error. An enum constant may be in PascalCase
  or UPPER_CASE (see [Enum Constant Names](#enum-constant-names)).
- Any name with no lower-case letter fails, as it's UPPER_CASE: `FOO`, `OK`, `HTTP`, `A`, `C2`, `B2B`.
- Any name with an underscore fails: `next_node`, `REDUCED_PLANCK`, `Phone_Number`.
- Any other name passes: `Foo`, `PhoneNumber`, `HTTPStatus`, `ClassA`, `IAnimal`, `Sha256Hash`.

**Why:** PascalCase mixes upper and lower case. A name with no lower-case letter is UPPER_CASE instead. [Section 4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says
the term "class" "refers to all classes, interfaces, traits, and enums", so the rule covers all four.

---

### Global Constants (PSR-1)

**Issue:** PSR-1 says "Class constants MUST be declared in all upper case". It says nothing about global constants.

**Decision:** Only class constants are checked for upper case, and for a leading underscore. A global constant, from
`const` or `define()`, is only checked by `PERCS31.NamingConventions.AcronymCasing`, and only if it has a lower-case
letter.

```php
const maxSize = 10; // Passes.

class Limits
{
    public const maxSize = 10; // Reported.
}
```

**Why:** PSR-1's rule names class constants only. The underscore rule, "Property or constant names MUST NOT be prefixed
with a single underscore to indicate protected or private visibility", is about showing visibility, which a global
constant doesn't have.

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

**Issue:** [Section 2.3](https://www.php-fig.org/per/coding-style/#23-lines) says:

> There MUST NOT be a hard limit on line length.
>
> The soft limit on line length MUST be 120 characters.
>
> Lines SHOULD NOT be longer than 80 characters; lines longer than that SHOULD be split into multiple subsequent lines
> of no more than 80 characters each.

The 120-character rule is a MUST that only requires a soft limit. The 80-character SHOULD is rarely followed.

**Decision:**

- A line over 120 characters is a severity 5 warning. There's no hard limit.
- A line over 80 characters is a severity 4 warning, hidden by default.

**Why:** A soft limit is a warning by definition. The 80-character SHOULD is kept, but out of sight unless asked for.
See [Line Length](LineLength.md) to change the limit.

---

## 2.4 Indenting

### A Tab Outside the Indent

**Issue:** [Section 2.4](https://www.php-fig.org/per/coding-style/#24-indenting) says:

> Code MUST use an indent of 4 spaces for each indent level, and MUST NOT use tabs for indenting.

The text only bans a tab that indents. It doesn't say whether one may be used elsewhere, as in `$a =` then a tab then
`1;`.

**Decision:** A tab is reported anywhere outside a string, including between two tokens, before a trailing comment and
inside a comment's text. The fix replaces it with spaces up to the next tab stop, which is every 4 columns, so the code
keeps the layout it had.

**Why:** A tab is never a space. The spec's spacing rules all say "space", as in "All binary operators MUST be preceded
and followed by at least one space", and every example separates tokens with spaces. See [Spacing Shown by Example](#spacing-shown-by-example).
PHP_CodeSniffer reads the same PSR-12 wording this way.

---

### PHP Inside Markup

**Issue:** The indent rule is written for code, and doesn't say how a PHP block inside HTML or other markup is indented.

**Decision:** Code in a PHP block may be level with its tags, or one level past them. The indent of `?>` isn't checked.

**Why:** Both styles are common, and the spec shows both:

- Every example that starts with `<?php` has its code level with the tag.
- [Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements)'s example of a file with markup has `// ...`, standing in for code, one level past its `<?php`:

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

- A compound namespace's items: once past the `use` line, as section 3's example shows.
- A value wrapped after `=>`: once past its key's line.
- Dead code after a `break`: level with the statement that ends the case.
- A statement in a closure, anonymous class or `switch`: the usual indent for its body.
- The `):` line that ends a multi-line `case` condition: level with the `case`, as section 5.2's example shows.

```php
switch ($a) {
    case (
        $b
        && $c
    ):
        run();
        break;
        cleanUp(); // Dead code, level with the `break`.
}
```

**Why:** Each follows the nearest example, or the general rule of one indent per level. The `):` line also matches the
closing line of every other split bracket, such as a split `if` condition's `) {`, a call's `)` and an array's `]`.

---

## 2.5 Keywords and Types

### The Indent of a Split Type

**Issue:** [Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says how a compound type is split over lines:

> If it is necessary to split a compound type into multiple lines:
>
> - If the type contains only intersections or only unions, then each line MUST have a single type.
> - If the type contains both intersections and unions, then each line MUST have a single union segment. All
>   intersections in a segment MUST be on the same line.
> - The symbol on which the compound type is split MUST be at the start of each line.

It doesn't say how the lines are indented. Its examples are all in parameter lists, where the type starts its line, and
each `|` line is level with it.

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

**Why:** The first follows the spec's examples, and the rule for a split parameter list, where "each subsequent line is
indented once". In the second, the `|` line continues a line that starts with something else, so it's indented once
more. See [Continuation Lines](#continuation-lines).

---

### `|` in a Multi-catch

**Issue:** [Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says:

> The union symbol `|` and intersection symbol `&` MUST NOT have a leading or trailing space.

PHP calls `catch (A|B $e)` a multi-catch, not a type declaration. It's unclear whether the rule covers it, or whether
`|` is a binary operator there, which needs a space each side.

**Decision:** It's read as a union type, so no space is allowed around the `|`. The types may be split across lines
before a `|`, one type per line, as any union type may.

```php
try {
    run();
} catch (RuntimeException|LogicException $e) { // Right.
    report($e);
} catch (TypeError | ValueError $e) { // Reported.
    report($e);
}
```

**Why:** `A|B` lists the exception types the variable can hold, which is what a union type does.

---

### Magic Constants

**Issue:** [Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says "All PHP reserved keywords and types MUST be in lower case". PHP's list of reserved keywords
includes the magic constants, such as `__DIR__`, which are conventionally upper case, and whose case PHP ignores.

**Decision:** Their case isn't checked.

**Why:** Reading the rule as requiring `__dir__` would go against near-universal practice, and it's unlikely the spec
means that.

---

### Hook Names

**Issue:** [Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says "All PHP reserved keywords and types MUST be in lower case". A property hook's `get` and
`set` aren't on PHP's list of reserved keywords, as they're only keywords inside a hook list.

**Decision:** They must be in lower case, so `GET => 1;` is reported.

**Why:** Section 4.10 calls one "the hook keyword", every example writes it in lower case, and [Section 4.6](https://www.php-fig.org/per/coding-style/#46-modifier-keywords) says "All
keywords MUST be all lower-case".

---

### `(double)` and `(binary)` Casts

**Issue:** [Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says:

> Short form of type keywords MUST be used i.e. `bool` instead of `boolean`, `int` instead of `integer` etc.

`(double)` is an alias for `(float)`, and `(binary)` for `(string)`. Neither is a longer spelling of the same word, so
it's unclear whether "etc." covers them.

**Decision:** It does. Both are reported, as `(boolean)` and `(integer)` are. `(double)` is fixed to `(float)`, and
`(binary)` to `(string)`.

**Why:** PHP treats the four alike. PHP 8.5 deprecates each as a "non-canonical cast", and names the form to use
instead.

---

## 2.6 Trailing Commas

### Which Lists

**Issue:** [Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas) says:

> Numerous PHP constructs allow a sequence of values to be separated by a comma, and the final item may have an optional
> comma. Examples include array key/value pairs, function arguments, closure `use` statements, `match()` statement
> branches, etc.
>
> If that list is contained on a single line, then the last item MUST NOT have a trailing comma.
>
> If the list is split across multiple lines, then the last item MUST have a trailing comma.

The "etc." leaves open which other lists it covers.

**Decision:** A multi-line list needs a trailing comma, and a single-line list mustn't have one, for arguments,
parameters, closure `use` lists, arrays, `match` arms, a `match` arm's conditions, compound namespaces, `list()`, an
anonymous class's arguments, `clone()`, `exit()`, `die()` and an attribute's arguments. `clone ($a)->c` is skipped, as
its parentheses may only group the object.

A `match` has two lists: its arms, and the conditions at the start of each arm. See [`match` Conditions](#match-conditions).

An attribute block is never split, so it never has a trailing comma. [Section 12.1](https://www.php-fig.org/per/coding-style/#121-basics) puts the first attribute straight
after `#[`, and the `]` straight after the last. [Section 12.3](https://www.php-fig.org/per/coding-style/#123-compound-attributes) says "If the attribute list is split into multiple lines
for any reason, then the attributes MUST be placed in separate attribute blocks". So the only block over several lines
holds one attribute whose arguments span lines, and the comma after its `)` is reported:

```php
// Reported.
#[Foo(
    1,
),]

// Right.
#[Foo(
    1,
)]
```

**Why:** The rule's purpose, cleaner diffs, applies equally to every comma-separated list split over lines. An attribute
block's own rules leave no way to split one.

---

### One Argument Split Over Lines

**Issue:** The trailing comma rule has two cases: a list "split across multiple lines" needs one, and a list "contained
on a single line" mustn't have one. [The Split-Argument Exemption](#the-split-argument-exemption) says a single argument split across lines "does not
constitute splitting the argument list itself". So this call's argument list isn't split, and isn't on a single line
either:

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

**Why:** The two cases are read as "split" and "not split". The spec's own examples of this shape, `], $baz);` and `});`
in [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls), have no comma.

---

### `match` Conditions

**Issue:** The trailing comma rule covers any comma-separated list that may end in a comma. A `match` arm's conditions
are one, but the spec's only example has them on one line, as in `1, 2, 3 => multipleCases(),`, and it never shows them
split.

**Decision:** The rule applies as written.

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

**Why:** The rule says "If the list is split across multiple lines, then the last item MUST have a trailing comma", and
makes no exception for this list. One condition per line follows every other list the spec splits: arguments,
parameters, an array's values and interfaces each have "only one" per line.

---

### A Comma That Starts a Line

**Issue:** [Section 4.5](https://www.php-fig.org/per/coding-style/#45-method-and-function-parameters) and [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) say "there MUST NOT be a space before each comma" in a parameter or argument
list. The spec doesn't say whether a line break counts, or cover other lists.

**Decision:** A comma may not start a line, in any list, including after a heredoc's closing marker.

```php
$values = [
    'a'
    , 'b', // Reported.
];
```

**Why:** A line break before a comma is space before it, and every split list in the spec ends its lines with the comma.
Section 10's example puts the comma straight after a heredoc's closing marker.

---

## 2.7 Naming

### Acronyms

**Issue:** [Section 2.7](https://www.php-fig.org/per/coding-style/#27-naming) RECOMMENDS following the php-src coding standards on abbreviations and acronyms, and quotes them:

> Abbreviations and acronyms as well as initialisms SHOULD be avoided wherever possible, unless they are much more
> widely used than the long form (e.g. HTTP or URL). Abbreviations, acronyms, and initialisms SHOULD be treated like
> regular words, thus they SHOULD be written with an uppercase first character, followed by lowercase characters.

It doesn't say which names the rules cover, or what counts as an acronym.

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

### Blank Lines in the Header

**Issue:** [Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements) says:

> The header of a PHP file may consist of a number of different blocks. If present, each of the blocks below MUST be
> separated by a single blank line, and MUST NOT contain a blank line.

The last block it lists is "The remainder of the code in the file". Read literally, that bans every blank line after the
header.

**Decision:** The ban covers the header's blocks only, from the opening tag to the imports. The rest of the code may
have blank lines wherever the general rule allows them (see [Blank Lines](#blank-lines)).

The file docblock is one of those blocks, so a blank line inside it is reported: a line that's empty, or only spaces,
with no `*`. A line with only a `*` isn't blank, so a docblock's paragraphs are separated with one:

```php
/**
 * Handles user authentication and session management.
 *
 * @package App\Auth
 */
```

A class's or a function's docblock isn't part of the header, so it isn't checked. See [Comments](#comments).

**Why:** The literal reading contradicts the general rule for blank lines, and every example in the spec with more than
one statement after the header, such as the trait and attribute examples. The file docblock is listed as a block, so the
ban on a blank line covers it too.

---

### Several Imports in One `use`

**Issue:** The header has "One or more class-based `use` import statements", but the spec doesn't say whether one
statement may import several names.

**Decision:** `use A, B;` is allowed.

**Why:** PSR-2 required one `use` keyword per declaration. PSR-12 dropped that rule when it added compound namespaces,
and PER doesn't have it either.

---

### A Compound Namespace Split Over Lines

**Issue:** [Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements) shows a compound namespace on one line, and one split over lines:

```php
use Vendor\Package\{ClassA as A, ClassB, ClassC as C};

use Vendor\Package\SomeNamespace\{
    SubnamespaceOne\ClassA,
    SubnamespaceOne\ClassB,
    SubnamespaceTwo\ClassY,
    ClassZ,
};
```

Its text doesn't say how a split one is laid out.

**Decision:** A compound namespace is written in one of those two ways. All on one line:

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

The comma after the last name is the trailing comma rule for a list split over lines. See [Which Lists](#which-lists).

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

A blank line anywhere in a compound namespace, after the `{`, between two names or before the `}`, is reported. A block
of the file header "MUST NOT contain a blank line", and a compound namespace is part of the block of imports.

**Why:** The two examples are the only forms the spec shows, and they match its rules for a single-line and a multi-line
array.

---

### Spacing in a Compound Namespace

**Issue:** No rule in section 3 sets the spacing in a compound namespace. Its examples, and the overview's, which
"encompasses some rules below", all write one the same way, as in
`use Vendor\Package\{ClassA as A, ClassB, ClassC as C};`.

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

```php
<?php

namespace App;

run();

use Vendor\Package\Bar; // Reported.
```

A file with markup that starts with `<?php declare(strict_types=1) ?>` has the rest of its header in the next PHP block,
as the strict types declaration must be on the first line (see [`strict_types` After a Shebang Line](#strict_types-after-a-shebang-line)). So in such a file,
an import after that block's header is reported, as is one after markup or a `<?=` block that comes before it.

**Why:** An import after the code breaks the header's order, which "MUST be in the order listed".

---

### The Case of a Directive

**Issue:** [Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types) says "All PHP reserved keywords and types MUST be in lower case". A `declare` directive's name,
such as `strict_types`, `ticks` or `encoding`, isn't a reserved keyword, and PHP accepts it in any case.

**Decision:** It must be in lower case, so `declare(STRICT_TYPES=1);` is reported, and fixed to
`declare(strict_types=1);`.

**Why:** The spec writes what PHP itself defines in lower case: its keywords and types, including `true`, `false` and
`null`, a hook's `get` and `set` (see [Hook Names](#hook-names)), and the `php` in `<?php`, which "MUST always be lower case". Every
example writes a directive in lower case too. The one exception is the magic constants, such as `__DIR__`, which are
conventionally upper case, and whose case isn't checked. See [Magic Constants](#magic-constants).

---

### A `declare` After the Header

**Issue:** The header has "One or more declare statements", and "Each block MUST be in the order listed below". The spec
doesn't say whether that covers a `declare` statement after the rest of the code, or after markup.

**Decision:** It doesn't. Only the `declare` statements in the header are checked for their order. One anywhere else
isn't checked, so this passes:

```php
<?php

namespace Vendor\Package;

run();

declare(ticks=1);
```

**Why:** A `declare` after the header is part of the code, not of the header. `declare(ticks=1)` applies from where it's
written, so it has a reason to be anywhere. PHP itself rejects a `strict_types` declaration that isn't the first
statement, and rejects or ignores a misplaced `encoding` one. Catching code that doesn't work is PHP's job, not a coding
standard's.

---

### `declare` Statements

**Issue:** [Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements) says "Declare statements MUST NOT contain any spaces inside the parentheses", but doesn't say
whether that includes after a comma between directives.

**Decision:** It does, so `declare(ticks=1,strict_types=1);` is the correct form. Several directives and string values
are allowed. There are no blank lines between `declare` statements.

**Why:** A space after the comma is still inside the parentheses. A `declare` statement is a special case: its `=` has
no space either side, where every other `=` needs "at least one space". So its comma has none after it either, where
every other comma has one.

The `declare` statements are one block of the file header, and a block "MUST NOT contain a blank line".

---

### Blank Lines in a Block `declare`

**Issue:** [Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements) says "Block declare statements are allowed and MUST be formatted as below. Note position of braces
and spacing". The example has no blank line after its `{` or before its `}`, but the text doesn't forbid one.

**Decision:** Both are allowed. [Jumbo](Jumbo.md) reports them, as an extra rule.

**Why:** Nothing explicitly forbids them (see [Blank Lines](#blank-lines)). [Section 5](https://www.php-fig.org/per/coding-style/#5-control-structures) forbids them at the start and end of a control
structure's body, but it names its control structures, and `declare` isn't one of them. Section 3 only asks for the
position of the braces and the spacing.

---

### `strict_types` After a Shebang Line

**Issue:** [Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements) says:

> When wishing to declare strict types in files containing markup outside PHP opening and closing tags, the declaration
> MUST be on the first line of the file and include an opening PHP tag, the strict types declaration and closing tag.

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

**Issue:** The first-line rule applies "When wishing to declare strict types" in a file with markup. The spec doesn't
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

**Issue:** [Section 4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says:

> Any closing brace MUST NOT be followed by any comment or statement on the same line.

It says "any", but it sits in the section on classes, properties and methods. Read literally, it would also forbid
`} else {`, `});` and a comment after an `if`.

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

Other closing braces follow the general rule in [Section 2.3](https://www.php-fig.org/per/coding-style/#23-lines) instead, "There MUST NOT be more than one statement per
line":

| Closing brace of                      | A comment after it | A new statement after it |
| ------------------------------------- | ------------------ | ------------------------ |
| A closure, anonymous class or `match` | Allowed            | Reported                 |
| A control structure                   | Allowed            | Reported                 |

- A closure, an anonymous class and a `match` are values, so one can be an argument, a list item or an operand. The rest
  of the statement carries on after the brace, as in `});`, `}, $b);` and `} + 1;`.
- `else`, `elseif`, `catch`, `finally` and a `do` loop's `while` belong to the same statement as the brace before them,
  so `} else {` is allowed.
- A comment isn't a statement, so the one-statement rule doesn't forbid one.
- A new statement, as in `} run();` or `} break;`, breaks the one-statement rule.

**Why:** The section, and how PHP is written, both point to the narrower reading. The spec's own examples have
`} elseif ($expr2) {` and `});`, which the literal reading would forbid.

---

### Parentheses Around `new`

**Issue:** [Section 4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says:

> When accessing a class member immediately after instantiating a new class, the instantiation SHOULD NOT be wrapped in
> parentheses.

It doesn't say which parentheses wrap. In `make(new Foo())->bar`, a `new` is in parentheses, and a member is accessed
straight after them.

**Decision:** Only parentheses that group an expression are reported, as in `(new Foo())->bar()`. Parentheses that hold
a call's arguments aren't, whatever comes after them.

- A function's name, a variable or another callable before the `(` makes them a call's, as in `make(new Foo())->bar`.
- A keyword before the `(` doesn't. So they're reported after `echo`, `print`, `return`, `yield`, `throw`, `include` and
  `require`, and inside `isset()`, `empty()`, `eval()`, `exit()` and `array()`.
- `clone(new Foo())->bar` is reported, and written `clone(new Foo()->bar)`, by the rule for `clone`'s parentheses below.

```php
new Foo()->bar();      // Right.
make(new Foo())->bar;  // Right: the parentheses hold an argument.
(new Foo())->bar();    // Reported.
return (new Foo())->a; // Reported.
```

**Why:** The two kinds do different jobs. Grouping parentheses set the order of operations, and PHP 8.4 made them
unnecessary around `new`. A call's parentheses are part of the call.

PHP reads `clone(new Foo())->bar` as a clone of `bar`, not of the new object, so those parentheses group `new Foo()`
too. [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says "The `clone()` function SHOULD always be called with parenthesis", so they move to hold what is
really cloned. The same goes for any `clone($a)->b`, which is written `clone($a->b)`, and `clone($a + $b)->c`, which is
written `clone(($a + $b)->c)`.

---

## 4.2 Using Traits

### Blank Lines Around `use`

**Issue:** [Section 4.2](https://www.php-fig.org/per/coding-style/#42-using-traits) says:

> The `use` keyword used inside the classes to implement traits MUST be declared on the next line after the opening
> brace.

And when the class has more in it than trait `use` statements:

> Otherwise, it MUST have a blank line after the `use` import statement

It doesn't say exactly one, or whether a blank line may come between two `use` statements.

**Decision:**

- A blank line before the first trait `use` is reported, as it goes "on the next line after the opening brace".
- Blank lines between two trait `use` statements are allowed.
- After the last one, at least one blank line is required when more follows in the class. More than one is allowed.
- When nothing follows, the class's closing brace goes on the next line.

```php
class Foo
{
    use First;

    use Second;


    private int $a;
}
```

[Jumbo](Jumbo.md) allows none between two `use` statements, and exactly one after the last, as extra rules.

**Why:** "MUST have a blank line" sets a minimum, not a count. Nothing forbids a blank line between two `use`
statements, so it's allowed (see [Blank Lines](#blank-lines)).

---

### Blank Lines in a Conflict Block

**Issue:** [Section 4.2](https://www.php-fig.org/per/coding-style/#42-using-traits) says "When using the `insteadof` and `as` operators they MUST be used as follows taking note of
indentation, spacing, and new lines". Its example has no blank line in the `{ ... }` block, but the text doesn't forbid
one.

**Decision:** Each statement goes on a line of its own between the braces, as the example shows. Blank lines in the
block are allowed. [Jumbo](Jumbo.md) allows none, as an extra rule.

**Why:** Nothing explicitly forbids them, and an example never sets a ban on a blank line. See [Blank Lines](#blank-lines).

---

### A Comment Before the First `use`

**Issue:** The trait `use` keyword "MUST be declared on the next line after the opening brace". The spec doesn't say
whether a comment may come between.

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

### A Comment After the Last `use`

**Issue:** [Section 4.2](https://www.php-fig.org/per/coding-style/#42-using-traits) says "When the class has nothing after the `use` import statement, the class closing brace MUST
be on the next line after the `use` import statement", and otherwise a blank line MUST follow it. It doesn't say whether
a comment counts as something after it.

**Decision:**

- A comment at the end of the `use` statement's line belongs to the statement, so the closing brace may follow on the
  next line.
- A comment on a line of its own after the last `use` counts as something after it, so a blank line goes before it.

```php
class Foo
{
    use First; // The first.
}

class Bar
{
    use First;

    // Nothing else yet.
}
```

**Why:** A comment on a line of its own belongs with the code after it, as in [A Comment Before the First `use`](#a-comment-before-the-first-use), and here
there's none, so it stands on its own. PHP_CodeSniffer reads the same PSR-12 wording this way.

---

## 4.3 Properties and Constants

### A Double Underscore Prefix

**Issue:** [Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says:

> Property or constant names MUST NOT be prefixed with a single underscore to indicate protected or private visibility.

It's unclear whether a double one is covered.

**Decision:** `$__a`, `__A` and `__a()` pass.

**Why:** "Single" excludes a double underscore, which PHP uses for magic methods. PSR-2's method sniff reads it the same
way.

---

### One Constant Per Statement

**Issue:** [Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says "There MUST NOT be more than one property or constant declared per statement". It's in
section 4, which is about classes, and doesn't say whether it covers a global `const`.

**Decision:** It covers a constant in a class, an interface, a trait, an enum or an anonymous class. A global `const`
with several constants passes. [Jumbo](Jumbo.md) reports it too, as an extra rule, and requires one space after a global `const`. A
`define()` call can only declare one constant.

```php
const A = 1, B = 2; // Passes.

class Limits
{
    public const MIN = 1, MAX = 9; // Reported.
}
```

**Why:** Section 4 is about classes, as [Global Constants (PSR-1)](#global-constants-psr-1) and [Spacing in a Constant Declaration](#spacing-in-a-constant-declaration) read it too.

---

### Visibility on a Promoted Parameter

**Issue:** [Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says:

> Visibility MUST be declared on all properties. If set-visibility is specified, then the general visibility MAY be
> omitted.

It doesn't mention constructor promotion, where `readonly` alone makes a property.

**Decision:** A promoted parameter with no visibility is reported. A set-visibility, as in `private(set)`, counts.

```php
class Point
{
    public function __construct(
        readonly int $x,          // Reported.
        private readonly int $y,  // Right.
        private(set) int $z,      // Right.
    ) {}
}
```

**Why:** A promoted parameter declares a property, so the property rule applies.

---

## 4.4 Methods and Functions

### A Body With Only a Comment

**Issue:** An empty body is abbreviated as `{}`, but the spec defines "empty" differently in different places:

- A function or method ([Section 4.4](https://www.php-fig.org/per/coding-style/#44-methods-and-functions)): "contains no statements or comments".
- A class ([Section 4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods)): "contains no additional declarations".
- A closure ([Section 7](https://www.php-fig.org/per/coding-style/#7-closures)): "empty-body closures", with no definition.

Only the first says whether a comment counts.

**Decision:** A body with only a comment isn't empty, for a class, a function, a closure and an anonymous class. So this
passes:

```php
$noOp = function (): void {
    // Nothing to do.
};
```

**Why:** The function rule's definition is the only one that mentions comments, so it's used for all four. Abbreviating
the body would also delete the comment.

---

### A Blank Line at the Start of a Body

**Issue:** [Section 4.4](https://www.php-fig.org/per/coding-style/#44-methods-and-functions) says a function's or method's "closing brace MUST go on the next line following the body", and
[Section 7](https://www.php-fig.org/per/coding-style/#7-closures) says the same of a closure. Neither says anything about the line after the opening brace.

**Decision:** A blank line straight before the closing brace is reported. Blank lines straight after the opening brace
are allowed. [Jumbo](Jumbo.md) reports those too, as an extra rule.

```php
function foo(): void
{

    run();

}
```

The blank line after the `{` is allowed. The one before the `}` is reported.

**Why:** The first is the rule as written. Nothing forbids the second (see [Blank Lines](#blank-lines)). A class and a control structure
each have a rule for both ends, so the difference is the spec's own: a class's opening brace "MUST NOT be preceded or
followed by a blank line".

---

### A Blank Line Before a Function's Opening Brace

**Issue:** [Section 4.4](https://www.php-fig.org/per/coding-style/#44-methods-and-functions) says a function's or method's "opening brace MUST go on its own line". Unlike the rule for a
class, it doesn't say the brace "MUST NOT be preceded" by a blank line.

**Decision:** The brace goes on the line straight after the declaration, so a blank line before it is reported:

```php
function fooBarBaz($arg1)

{
    return $arg1;
}
```

**Why:** Nobody writes this, and it's very unlikely the spec's authors meant to allow it. Every example puts the brace
on the next line, and PHP_CodeSniffer reads the same PSR-12 wording this way.

---

## 4.5 Method and Function Parameters

### Blank Lines in a List

**Issue:** The spec's examples rarely show a blank line in a list, so it's unclear where one is allowed. The general
rule is that blank lines are allowed "except where explicitly forbidden" (see [Blank Lines](#blank-lines)).

**Decision:** Blank lines are allowed between two items of every list: parameters, a closure's `use` variables, a call's
or an attribute's arguments, an array's values, a `match`'s arms and interfaces after `implements`. A comment between
two items is allowed too. So this passes:

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

- Straight after the opening parenthesis of a parameter list, a closure's `use` list or an argument list, and after an
  array's opening bracket, as "the first item in the list MUST be on the next line".
- Straight before an array's closing bracket, which "MUST be placed on the next line after the last value".
- After an attribute, as "There MUST NOT be any blank lines between the docblock and attributes, or the attributes and
  the structure".
- Straight after a `match`'s `{`, or before its `}`, as for any control structure.
- Between the last interface after `implements` and the class's `{`, as the brace "MUST NOT be preceded or followed by a
  blank line".

So a blank line straight before the closing parenthesis of a parameter list, a `use` list or an argument list is
allowed. A split parameter list's rule only puts "the closing parenthesis and opening brace" "together on their own
line", and the rule for a call says nothing about its `)`. The same goes for a split control structure header, which
uses the same words.

[Jumbo](Jumbo.md) is stricter, as extra rules: no multi-line list starts or ends with a blank line, and no more than one comes
between two items.

**Why:** Only a blank line that's explicitly forbidden is ruled out, and an example that doesn't happen to have one
isn't a ban. The spec's attribute example in [Section 12.4](https://www.php-fig.org/per/coding-style/#124-example) has a blank line between two parameters, which shows its
authors use them this way.

---

### A Split Return Type

**Issue:** A union return type can be split over lines, with "The symbol on which the compound type is split" "at the
start of each line". A split parameter list puts "the closing parenthesis and opening brace" "together on their own
line". It's unclear how the two combine.

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

- After a single-line parameter list, it passes. Each line has one type, as the type rule requires, and the return type
  starts on the `)` line, where "The colon and declaration MUST be on the same line as the argument list closing
  parenthesis":

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

**Issue:** A split parameter list has each parameter on its own line, where "each subsequent line is indented once". A
default value wrapped at an operator is also a split statement, where "each subsequent line is indented once" past the
line it continues. It's covered by both.

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

**Why:** The wrapped line is a continuation of the line before it, not a parameter. The list's rule places each
parameter, and the operator rule places the rest of its expression, as for an operator wrapped in a call's argument. See
[Continuation Lines](#continuation-lines).

---

### A Split Array Default Value

**Issue:** [The Split-Argument Exemption](#the-split-argument-exemption) lets a single argument, such as an array, split over lines without splitting the
argument list. It's unclear whether the same applies to a parameter's default value.

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

**Why:** The exemption is only for argument lists.

---

### A Reference `&` Outside a Parameter List

**Issue:** [Section 4.5](https://www.php-fig.org/per/coding-style/#45-method-and-function-parameters) says:

> When using the reference operator `&` before an argument, there MUST NOT be a space after it

The spec doesn't say whether that covers a reference `&` anywhere else, as in `$x = & $a`.

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

**Why:** It's the same operator in each place, so the same rule is used. The spec states it where it happens to show the
operator, which is in a parameter list.

---

### The Spread Operator Outside a Parameter List

**Issue:** [Section 4.5](https://www.php-fig.org/per/coding-style/#45-method-and-function-parameters) says:

> There MUST NOT be a space between the variadic three dot operator and the argument name

The spec doesn't say whether that covers a `...` anywhere else, as in `foo(... $args)`.

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

The `...` of a first-class callable, as in `strlen(...)`, isn't a spread, as it has nothing after it to unpack.
[Section 4.8](https://www.php-fig.org/per/coding-style/#48-function-callable-references) sets its spacing.

**Why:** It's the same operator in each place, so the same rule is used, as for
[A Reference `&` Outside a Parameter List](#a-reference--outside-a-parameter-list).

---

## 4.6 Modifier Keywords

### Modifier Order on Constants

**Issue:** [Section 4.6](https://www.php-fig.org/per/coding-style/#46-modifier-keywords) says:

> Classes, properties, and methods have numerous keyword modifiers that alter how the engine and language handles them.
> When present, they MUST be in the following order:

The list starts with the inheritance modifier, `abstract` or `final`, then the visibility. It doesn't name constants,
which can be `final` too.

**Decision:** The order is checked everywhere a modifier keyword can be used, so on a constant too. `final` comes before
the visibility. The single space between keywords is checked there as well.

```php
final public const MAX = 10; // Right.
public final const MAX = 10; // Reported.
```

**Why:** The order is a property of the keywords, not of what they're on. A constant only takes two of them, an
inheritance modifier and a visibility, and the list puts those in that order.

---

### Spacing in a Constant Declaration

**Issue:** [Section 4.6](https://www.php-fig.org/per/coding-style/#46-modifier-keywords) says keywords "MUST be separated by a single space", and [Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says "There MUST be a space
between type declaration and property name". Neither says how a constant's `const`, type and name are spaced.

**Decision:** A class constant has exactly one space after `const`, and between its type and its name, so
`public const int A = 1;` is reported. A line break counts as more than one space. A global constant isn't checked.

**Why:** A constant is declared like a property, so the same spacing applies. Section 4 is about classes, so the rule
stops at class constants.

---

## 4.7 Method and Function Calls

### A Space Before `clone`'s or `exit`'s Parentheses

**Issue:** [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says "there MUST NOT be a space between the method or function name and the opening parenthesis".
It also says:

> The `exit()` and `die()` functions SHOULD always be called with parentheses even if no argument is given

And "The `clone()` function SHOULD always be called with parenthesis". It's unclear which keyword applies to a space
before those parentheses, as in `clone ($a)`.

**Decision:** The MUST does. `clone ($a)`, `exit (1)` and `die ('x')` are errors, as `foo ($a)` is. Leaving the
parentheses out, as in `clone $a` or `exit;`, is a warning.

**Why:** The SHOULD is about whether to use parentheses. With them, each is a function call, which is what the spec
calls all three, and the rule for a call's parentheses applies.

---

### `require` and `include` With Parentheses

**Issue:** [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says "there MUST NOT be a space between the method or function name and the opening parenthesis".
The spec doesn't say whether `require ($file)` is a call. Its only example of either is in PSR-1's section on side
effects, which writes `include "file.php";`.

**Decision:** It isn't checked. `require`, `include` and their `_once` forms may be written with or without parentheses,
and with a space before them.

**Why:** They're language constructs, not functions, and parentheses after one only group its expression, as in
`include ($a ? 'x' : 'y') . '.php';`. PSR-1's example is there to show a side effect, not a layout, so it shows one
allowed form, and no rule covers them.

---

### Blank Lines Between Arguments

**Issue:** [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) doesn't forbid a blank line between a call's arguments, and none of its examples has one.

**Decision:** Blank lines are allowed between two arguments, and straight before the call's `)`. One straight after the
call's `(` is reported. See [Blank Lines in a List](#blank-lines-in-a-list).

**Why:** Nothing explicitly forbids them (see [Blank Lines](#blank-lines)). The rule for a split argument list says "the first item in
the list MUST be on the next line", and nothing more. PHP_CodeSniffer's PSR-12 standard reports every blank line in a
call, but nothing in the text does.

---

### Method Chaining

**Issue:** [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) says:

> Method chaining MAY be put on separate lines, where each subsequent line is indented once. When doing so, the first
> method MUST be on the next line.

It doesn't say what counts as the start of the chain.

**Decision:** The first method call goes on the next line. Property accesses before it, as in `$this->em`, may stay. A
static call may stay too, as the receiver.

```php
// Right.
$this->em
    ->persist($user);

Foo::create()
    ->run();

// Reported: the first method is on the first line.
$this->em->persist($user)
    ->flush();
```

**Why:** The rule is about methods. A property access or a static call is the object the chain starts from.

---

### A Chain on a Continuation Line

**Issue:** A split chain's lines are "indented once", but the spec doesn't say from where. It only matters when the
chain starts on a continuation line, as a ternary's branch does, or an operand after a wrapped operator.

**Decision:** They're indented once past the line the chain starts on, the one with its object.

```php
// Right.
$user = $useCache
    ? $cache
        ->get($id)
    : null;

// Reported: `->get` is level with `? $cache`.
$user = $useCache
    ? $cache
    ->get($id)
    : null;
```

**Why:** A chain's wrapped line continues the line before it, so it's indented once more (see [Continuation Lines](#continuation-lines)). Lined
up with its object, it can't be told from the ternary's own lines.

---

### `->` at the End of a Line

**Issue:** The chaining rule says "the first method MUST be on the next line", and only the example shows where the `->`
goes:

```php
$someInstance
    ->create()
    ->prepare()
    ->run();
```

This meets the text too, as the method is on the next line:

```php
$a->
    b();
```

**Decision:** A `->` or `?->` starts its line, so one at the end of a line, as in the second example, is reported,
whether the statement has one or several.

**Why:** The example shows it, and the operator rule says "the operator MUST be placed at the beginning of the new
line". One rule for every `->` is simpler than a rule that depends on how many a statement has.

---

### Closures as Arguments

**Issue:** [The Split-Argument Exemption](#the-split-argument-exemption) names a closure as an argument that may span lines without splitting the
argument list. It doesn't say where the closure's attribute goes, which matters for a named argument.

**Decision:** A closure that spans lines doesn't split the argument list, and its attribute may stay inline, before
`function`, even after a named argument's `name:`.

```php
$items->each(callback: #[Pure] function ($item) {
    return $item;
});
```

**Why:** The exemption names closures, and its example passes a multi-line closure as one argument. As the list isn't
split, the closure's attribute follows the rule for a single-line list, inline. See [Targets 12.2 Doesn't List](#targets-122-doesnt-list).

---

## 4.9 Property Hooks

### Hook Layout

**Issue:** [Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) sets the spacing before a hook list's `{`, "separated from the property name or its default value
by a single space". It doesn't set the spacing before a hook's own `{`, or say whether two hooks on one line are two
statements.

**Decision:**

- One space before a hook's own `{`, as in `get {`.
- `{ get; set; }` on one line is one block, not two statements.

```php
interface Named
{
    public string $name { get; set; } // Right.
}

class Person
{
    public string $name {
        get{ // Reported.
            return $this->name;
        }
    }
}
```

A by-reference hook's `&` is covered by [A Reference `&` Outside a Parameter List](#a-reference--outside-a-parameter-list).

**Why:** The first follows the examples in section 4.9. The second follows section 4.10's, which write `{ get; set; }`
on one line.

---

### `set` Hook Parameters

**Issue:** [Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks)'s text doesn't set the spacing of a `set` hook's parameter list.

**Decision:** No space after `set`, and none just inside the parentheses. `set (string $value)` and
`set( string $value )` are reported, and fixed to `set(string $value)`.

**Why:** The section's example writes `set(string $value)`, which matches the rule for a method: "Method and function
names MUST NOT be declared with space after the method name". A hook is declared like a method, so it isn't read as a
call.

---

### Blank Lines Between Hooks

**Issue:** [Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) says:

> If multiple hooks are declared, they MUST be separated by at least a single line break. They MAY be separated by an
> additional blank line to aid readability.

A line break isn't a blank line, so it's easy to misread this as requiring one or two blank lines.

**Decision:** No blank line or one between hooks. Two or more are reported.

- A single line break puts the next hook on a new line, with no blank line between them. That's the minimum.
- "An additional blank line" is one more line break, so one blank line. That's the maximum.

The section's example shows both:

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

**Why:** The MAY names the one thing allowed, "an additional blank line", so one is the most. Unlike a list, where the
general rule is all there is to go on, the spec says here how many.

This is the one rule that says how many blank lines are allowed, so it overrides the general permission. It's a
deliberate exception to [Keywords](#keywords): this MAY sets a limit, so going past it is reported.

---

### Blank Lines Inside a Hook's Braces

**Issue:** [Section 4.9](https://www.php-fig.org/per/coding-style/#49-property-hooks) says a hook list's "closing brace MUST be on its own line". It doesn't say whether a blank line
may follow the opening brace or come before the closing one, of the hook list or of a hook's body.

**Decision:** All four are allowed. [Jumbo](Jumbo.md) reports them, as an extra rule.

**Why:** A brace after a blank line is still on its own line, and nothing else in the section rules one out. See
[Blank Lines](#blank-lines).

---

## 4.10 Interface and Abstract Properties

### `get` Before `set`

**Issue:** [Section 4.10](https://www.php-fig.org/per/coding-style/#410-interface-and-abstract-properties) has rules for an abstract or interface property whose hooks have no bodies, including:

> The `get` operation MUST be listed before the `set` operation.

It says nothing about the order in other hook lists.

**Decision:** `get` comes first in every hook list.

```php
public string $name {
    set => trim($value); // Reported: `set` before `get`.
    get => $this->name;
}
```

**Why:** It makes no sense to require `get` before `set` when the hooks have no bodies, and not care about the order
when they do. The order of two hooks doesn't depend on what's in them, and every example in section 4.9 has `get` first
too.

---

## 5. Control Structures

### An Empty Body, or No Body

**Issue:** [Section 5](https://www.php-fig.org/per/coding-style/#5-control-structures) says "The body of each structure MUST be enclosed by braces", and "The body MUST be on the next
line after the opening brace". It doesn't say what either means for a structure with nothing in it. That comes in two
forms: an empty body, with braces and nothing between them, and no body at all, as in `while (next($items));`.

**Decision:**

- **No body:** the structure MUST have braces, so `while (next($items));` and `else;` are errors.
- **An empty body:** it isn't checked. `{}` on the same line and the braces on separate lines both pass. [Jumbo](Jumbo.md) requires
  `{}`, as an extra rule.

So these are right:

```php
while (next($items)) {}

try {
    run();
} catch (Exception $e) {}

if ($ready) {
    run();
} else {}

try {
    run();
} catch (Exception $e) {
}
```

These are errors:

```php
while (next($items));

if ($ready) {
    run();
} else;
```

**Why:** The rule says each structure's body MUST be enclosed by braces, and makes no exception for one with no body. A
`;` straight after a condition is also easy to add by accident, and to miss when reading.

For an empty body, section 5 has no rule, and braces on separate lines meet both of its rules: the body on the next line
after the `{`, and the `}` on the next line after the body. The SHOULD for `{}` covers a class and a function only
(see [A Body With Only a Comment](#a-body-with-only-a-comment)).

---

### "Indented at Least Once"

**Issue:** [Section 5.1](https://www.php-fig.org/per/coding-style/#51-if-elseif-else) says:

> Expressions in parentheses MAY be split across multiple lines, where each subsequent line is indented at least once.
> When doing so, the first condition MUST be on the next line.

It doesn't say how far is too far, or how it applies to a `case`.

**Decision:** It applies to a split condition in a control structure's parentheses, including a `case` condition. Any
indent of at least one level passes. `case (` with the first condition on the same line is reported.

```php
// Right: indented twice.
if (
        $a
        && $b
) {
    run();
}

// Reported: the first condition is on the `case (` line.
switch (true) {
    case ($a
        && $b
    ):
        run();
        break;
}
```

**Why:** "At least once" sets a minimum. Each control structure says "the first condition MUST be on the next line".

---

### A Condition Split Only Inside a Call

**Issue:** A control structure's condition can span lines only because a call or array in it does, as in `if (foo(`.
[The Split-Argument Exemption](#the-split-argument-exemption) says a single argument split across lines "does not constitute splitting the argument list
itself". It's unclear whether the same goes for a condition.

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

**Why:** The exemption is only for argument lists. Each control structure's rule is about its expression spanning lines,
not about where the breaks fall. PHP_CodeSniffer reads the same PSR-12 wording this way.

---

## 5.2 `switch`, `case`, `match`

### Single-line `match`

**Issue:** [Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas) names "`match()` statement branches" among the lists whose trailing comma depends on whether the
list is "contained on a single line". That suggests a single-line `match` is possible, even if extremely rare in
practice. [Section 5](https://www.php-fig.org/per/coding-style/#5-control-structures) says "The body MUST be on the next line after the opening brace".

**Decision:** A `match` on one line is reported.

```php
$label = match ($size) { 1 => 'one', default => 'many' }; // Reported.
```

**Why:** The trailing comma rule names a `match`'s arms, but doesn't say a `match` may be on one line. [Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match) is
more specific about how a `match` is written, and its example has each arm on its own line.

---

### The `match` Example

**Issue:** [Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match) says "a `match` expression looks like the following. Note the placement of parentheses, spaces,
and braces":

```php
$returnValue = match ($expr) {
    0 => 'First case',
    1, 2, 3 => multipleCases(),
    default => 'Default case',
};
```

Its text states none of that layout.

**Decision:** At least one space each side of `=>`, one space after each comma, one arm per line, a trailing comma after
each arm, and `}` on its own line. The comma spacing is also applied to lists with no example, such as `echo`, `global`
and a compound namespace.

The arrows may be lined up, as an array's may. See [`=>` in a `match` Arm, an Array or `yield`](#-in-a-match-arm-an-array-or-yield).

**Why:** The example is the template for a `match` (see [Rule Text and Examples](#rule-text-and-examples)). One space after a comma is the spec's
pattern everywhere it shows one.

---

### A `match`'s Subject Split Over Lines

**Issue:** [Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match) gives `switch` the rule for an expression split inside the parentheses: "the first condition MUST
be on the next line". Of a `match`, it only says it "looks like" its example.

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

**Why:** The rule applies where the spec states it, which is how [Spacing](#spacing) reads it for `foreach`. Nothing else in section
5.2 rules this layout out. A structure that has the rule, such as `switch`, would put the call on the line after `(`, as
in [A Condition Split Only Inside a Call](#a-condition-split-only-inside-a-call).

---

### The `case` Line

**Issue:** [Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match) says:

> The `case` statements line MUST consist only of the `case` keyword, a single space, the case condition
> (an expression), and a colon.

It's unclear whether "only" rules out a comment.

**Decision:** It doesn't. A comment is allowed there, as in `case 1: // The first.` Anything else is reported.

**Why:** The rule describes the code on the line. Where the spec bans a comment, it says so, as it does after a closing
brace: "Any closing brace MUST NOT be followed by any comment or statement on the same line". PHP_CodeSniffer reads the
same PSR-12 rule this way.

---

### A Block in a `case`

**Issue:** [Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match) says "The `case` body MUST NOT be wrapped in `{}`". A block can also be part of a body, with more
statements after it. PHP_CodeSniffer reads both the same way.

**Decision:** A body is wrapped when a block starts straight after the label, and the block's `}` is followed by the
next `case` or `default`, or the switch's `}`, ignoring comments. That's reported. A block followed by anything else,
even just `break`, is part of the body, and passes:

```php
switch ($size) {
    // Reported: the block is the whole body.
    case 1: {
        run();
    }
    case 2:
        {
            run();
        }
        // Passes: the break is part of the body too.
        break;
}
```

**Why:** The rule is there to stop a case being written as if it took a block, as an `if` does. A block followed by more
of the body doesn't wrap it, so the text doesn't forbid it.

---

### Blank Line After a `case` Label

**Issue:** [Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match) doesn't forbid a blank line straight after a `case` or `default` label.

**Decision:** It's allowed. [Jumbo](Jumbo.md) reports it, as an extra rule.

**Why:** Section 5.2 only sets what's on the label's own line, and nothing else explicitly forbids it (see [Blank Lines](#blank-lines)).
Section 5's rule, "The body MUST be on the next line after the opening brace", needs a brace, and a label has none.

---

### `default`

**Issue:** [Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match) has two rules that name only `case`:

> If a non-empty case intends to continue into the following case, then a clear comment MUST be included to highlight
> the deliberate lack of a `break`, `return`, or similar termination statement.

And "The `case` body MUST NOT be wrapped in `{}`". It doesn't say whether either rule covers `default`.

**Decision:** Both do. A non-empty `default` that falls through needs a comment, and a braced `default` body is
reported.

**Why:** A `default` is a `case` in all but name. It can fall through, and take braces, just as a `case` can.

---

## 5.4 `for` and 5.5 `foreach`

### Spacing

**Issue:** The text doesn't set the spacing around `;`, `=>` and `as`. The examples do: `for ($i = 0; $i < 10; $i++) {`
and `foreach ($iterable as $key => $value) {`. [Section 5.4](https://www.php-fig.org/per/coding-style/#54-for) has a rule for splitting a `for` header, but [Section 5.5](https://www.php-fig.org/per/coding-style/#55-foreach) has
none for a `foreach`.

**Decision:** The examples' spacing is required. In a `foreach` split inside its parentheses, a line break may take the
place of the space before or after `as` or `=>`. Otherwise a split `foreach` only gets the checks every control
structure gets, with no rule for where it's split.

```php
foreach (
    $this->repository->findAllActive()
    as $id => $user
) {
    run($user);
}
```

**Why:** The examples are the only guide. No example splits a `foreach`, and no rule says where it may be split.

---

### An Empty Expression in a `for`

**Issue:** [Section 5.4](https://www.php-fig.org/per/coding-style/#54-for)'s example has three expressions, so it doesn't show how an empty one is spaced.

**Decision:** No space comes before a semicolon, whether or not an expression does. So an empty middle expression leaves
the two semicolons together:

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

**Issue:** [Section 6](https://www.php-fig.org/per/coding-style/#6-operators) lists operators by kind, and says:

> All operators not described here are left undefined.

It leaves out unary `+`, `-`, `!`, `~` and `@`, a reference `&` and a spread `...`. It also leaves out `->`, `?->` and
`::`, though it's debatable whether those are operators at all: PHP's operator precedence table doesn't list them.

**Decision:** Section 6 sets no rule for any of these. One is only checked where another section gives it a rule:

- `->`, `?->` and `::` have no white space around them, as an error, by the call and trait examples quoted below.
  - `::` has none on either side, not even a line break, as in `Foo::bar()` and `self::Spades`.
  - `->` and `?->` have none after them. They have none before them either, unless one starts a line in a split chain,
    where only the line break and the indent come before it.
- A reference `&` and a spread `...` have no white space after them, by the parameter rules. See
  [A Reference `&` Outside a Parameter List](#a-reference--outside-a-parameter-list) and [The Spread Operator Outside a Parameter List](#the-spread-operator-outside-a-parameter-list).
- Unary `+`, `-`, `!`, `~` and `@` aren't checked, as no section has a rule for them. Jumbo checks them.

**Why:** "Left undefined" means section 6 has no rule for an operator, not that no other section may have one. The spec
presents these examples as templates, which makes each one a rule (see [Rule Text and Examples](#rule-text-and-examples)):

- `$foo->bar($arg1);` and `Foo::bar($arg2, $arg3);`, which [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls) introduces with "The following lines show correct
  calls".
- `A::smallTalk insteadof B;` and `C::mediumTalk as FooBar;`, in [Section 4.2](https://www.php-fig.org/per/coding-style/#42-using-traits), which says `insteadof` and `as` "MUST be
  used as follows taking note of indentation, spacing, and new lines".

Every other example agrees, such as `BazClass::bar($arg2, $arg3);` in the overview and
`public const Wild = self::Spades;` in section 9. The chain example starts each line with `->`. The trait rule's MUST
sets the severity.

It's the same reading as for `=>`, which section 6 doesn't list either: see [`=>` in a `match` Arm, an Array or `yield`](#-in-a-match-arm-an-array-or-yield).

---

## 6.2 Binary Operators

### `=>` in a `match` Arm, an Array or `yield`

**Issue:** `=>` isn't one of the binary operators [Section 6.2](https://www.php-fig.org/per/coding-style/#62-binary-operators) lists. The `match` example and the array example each have
one space each side, but there are too few arrows in them to rule out additional spaces being used to align arrows,
which is not uncommon.

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

**Why:** The spec always shows a space each side, and never none. It gives too little to forbid alignment, and [Section 6](https://www.php-fig.org/per/coding-style/#6-operators)
says "When space is permitted around an operator, multiple spaces MAY be used for readability purposes". One rule for
both keeps a `match` and an array consistent. The spec says nothing about a `yield`, so that follows the same rule.

---

### `=&`

**Issue:** It's unclear whether `=&` is one operator or `=` followed by a reference `&`. Neither PER-CS 3.1 nor PSR-1
shows an example, and the PHP documentation shows both joined and separated forms.

**Decision:** It's an `=` followed by a reference `&`. The `=` is a binary operator, so it needs "at least one space"
each side. The `&` is a reference, so "there MUST NOT be a space after it". Only `$x = &$a` passes:

| Code        | Result                        |
| ----------- | ----------------------------- |
| `$x = &$a`  | Passes                        |
| `$x = & $a` | Reported, fixed to `$x = &$a` |
| `$x =& $a`  | Reported, fixed to `$x = &$a` |
| `$x =&$a`   | Reported, fixed to `$x = &$a` |
| `$x=&$a`    | Reported, fixed to `$x = &$a` |
| `$x=& $a`   | Reported, fixed to `$x = &$a` |

**Why:** PHP has no `=&` operator. It reads the `=` and the `&` as separate tokens, even though the `&` changes what the
assignment does. So each follows its own rule: the binary operator rule for the `=`, and the reference rule for the `&`,
as [A Reference `&` Outside a Parameter List](#a-reference--outside-a-parameter-list) reads it.

---

### A Default Value's `=`

**Issue:** It's unclear whether a parameter's default value `=` is an assignment operator, which [Section 6.2](https://www.php-fig.org/per/coding-style/#62-binary-operators) says "MUST
be preceded and followed by at least one space".

**Decision:** It is, so it needs at least one space each side, and alignment is allowed:

```php
function connect(
    string $host = 'localhost',
    int $port    = 3306,
): void {}
```

**Why:** It looks and reads like an assignment. "At least one space" leaves room for alignment.

---

## 6.3 Ternary Operators

### `?:`

**Issue:** [Section 6.3](https://www.php-fig.org/per/coding-style/#63-ternary-operators) says:

> When the middle operand of the conditional operator is omitted, the operator MUST follow the same style rules as other
> binary comparison operators

It doesn't say whether the `?` and `:` may be apart, as in `$a ? : $b`.

**Decision:** They may not. The fix joins them into `?:`.

**Why:** A binary operator is one token with a space each side, and the section's example writes
`$variable = $foo ?: 'bar';`.

---

## 6.4 Operator's Placement

### Where a Wrapped Operator Goes

**Issue:** [Section 6.4](https://www.php-fig.org/per/coding-style/#64-operators-placement) says:

> A statement that includes an operator MAY be split across multiple lines, where each subsequent line is indented once.
> When doing so, the operator MUST be placed at the beginning of the new line; ternaries MUST occupy 3 lines, never 2.

It says "a statement", without saying whether that covers expressions inside a list item, a `match` arm or parentheses.

**Decision:** It applies wherever an expression is split. The operator starts the line, indented once. This covers
assignment operators, `.`, and an arrow function's `=>`.

```php
// Right.
$message = sprintf(
    '%s %s',
    $firstName
        . ' '
        . $lastName,
    $title,
);

// Reported: the `.` ends the line.
$message = sprintf(
    '%s',
    $firstName .
        $lastName,
);
```

**Why:** The rule is about expressions, not statements.

---

### Operators in Grouping Parentheses

**Issue:** Split operators get "each subsequent line is indented once", and a split condition's lines are "indented at
least once". Neither rule says how the lines inside grouping parentheses are indented, as in `$a && (` with `$b` and
`|| $c` on the lines after it.

**Decision:** Every line in grouping parentheses is indented once past the line with the `(`. So `|| $c` lines up with
`$b`, and isn't indented once more:

```php
// Right.
if (
    $a
    && (
        $b
        || $c
    )
) {
    run();
}

// Reported: `|| $c` is indented past `$b`.
if (
    $a
    && (
        $b
            || $c
    )
) {
    run();
}
```

That applies in a condition and in any other expression, such as `$x = $a` then `&& (` on the next line. A call's
argument parentheses aren't a group, so an operator wrapped inside an argument is still indented once past the
argument's first line.

An arrow function's `=>` is always indented once past the line with its own `fn`, in a group or not, as is an operator
wrapped in its body. So in a nested arrow function, each wrapped `=>` is indented once more than the one before it:

```php
$sum = fn(int $x)
    => fn(int $y)
        => fn(int $z)
            => $x + $y + $z;
```

**Why:** It's the layout the spec gives a condition's own parentheses, one level down. The lines in a group read as one
level, and the line with the `(` shows that the group continues the statement.

---

### When a Ternary Is Split

**Issue:** The operator rule says "ternaries MUST occupy 3 lines, never 2", but not what counts as split.

**Decision:** A ternary is split only when a line break is right before or after its `?` or `:`, ignoring comments. Then
it takes exactly three lines, unless a value or condition wraps over lines of its own, so a blank line before the `?` or
`:` is reported. A nested ternary is indented once more. A short ternary may be on two lines, with `?:` starting the
second.

```php
// Right.
$label = $isAdmin
    ? 'Admin'
    : 'User';

$name = $nickname
    ?: 'Guest';

// Right: not split, as no line break is next to the `?` or `:`.
$total = $isMember ? discount(
    $price,
) : $price;

// Reported: split, but on two lines.
$label = $isAdmin ? 'Admin'
    : 'User';
```

**Why:** A value or condition split over lines doesn't change the ternary's structure. A blank line does, as a split
ternary MUST "occupy 3 lines". It's one of the places the text rules a blank line out (see [Blank Lines](#blank-lines)). The two-line
short ternary follows the section's own example, `$variable3 = $elvisExpr` then `?: 'qix';`.

---

### Heredoc After a Wrapped `=`

**Issue:** The operator rule lets `=` start a wrapped line. [Section 10](https://www.php-fig.org/per/coding-style/#10-heredoc-and-nowdoc) says "Declared heredocs or nowdocs MUST begin on
the same line as the context the declaration is being used in".

**Decision:** A heredoc after a wrapped `=` is reported:

```php
$x
    = <<<EOT
    Hello $name.
    EOT;
```

**Why:** The heredoc rule is more specific.

---

## 7. Closures

### Empty Closures and Arrow Functions

**Issue:** [Section 7](https://www.php-fig.org/per/coding-style/#7-closures) says:

> If possible, empty-body closures SHOULD be replaced with arrow functions:

```php
$noOpFunction = fn() => null;
```

It doesn't say when it's possible.

**Decision:** It's possible unless the closure has a `void` or `never` return type, or returns by reference. So
`function () {}` gets a warning, and `function (): void {}` passes.

**Why:** An arrow function always returns a value, so `fn(): void => null` is a fatal error. `function () {}` and
`fn() => null` both return `null`, so replacing one with the other changes nothing.

---

## 7.1 Short Closures

### Arrow Functions

**Issue:** [Section 7.1](https://www.php-fig.org/per/coding-style/#71-short-closures) says:

> Short closures, also known as arrow functions, MUST follow the same guidelines and principles as long closures above,
> with the following additions.

Its additions cover the space after `fn`, around `=>` and before the `;`, and a wrapped `=>` "on the second line". They
don't cover a nested arrow function's wrapped `=>`.

**Decision:** No space after `fn`. A split parameter list follows the closure rules. No space before the closing
semicolon. A wrapped `=>` is indented once past the line its own `fn` starts on, so each nested arrow function's wrapped
`=>` is indented once more than the one before it, as shown in [Operators in Grouping Parentheses](#operators-in-grouping-parentheses).

**Why:** An arrow function is a closure, so the closure rules fill the gaps. The wrapped `=>` "MUST be indented once",
and for a nested one, the line it's indented from is its own `fn`'s.

---

## 8. Anonymous Classes

### The Opening Brace

**Issue:** [Section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes) says:

> The opening brace MAY be on the same line as the `class` keyword so long as the list of `implements` interfaces does
> not wrap. If the list of interfaces wraps, the brace MUST be placed on the line immediately following the last
> interface.

It doesn't say whether the brace may also go on the next line when the list doesn't wrap.

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

**Issue:** [Section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes)'s text doesn't say how many spaces go before the opening brace, or whether the body may start on
its line.

**Decision:** One space before a brace on the declaration's line, and nothing after the brace on its line, not even a
comment. An empty body, `{}`, is OK.

```php
// Right.
$a = new class {};

$b = new class implements Countable {
    public function count(): int
    {
        return 0;
    }
};

// Reported: code after the brace.
$c = new class { public int $d = 1;
};
```

**Why:** Section 8 says "Anonymous Classes MUST follow the same guidelines and principles as closures", and a closure's
"opening brace MUST go on the same line". Its examples show one space before the brace, and nothing after it.

---

### Blank Lines in an Anonymous Class's Body

**Issue:** [Section 4.1](https://www.php-fig.org/per/coding-style/#41-extends-and-implements) says a class's opening brace "MUST NOT be preceded or followed by a blank line", and its closing
brace "MUST NOT be preceded by a blank line". [Section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes) says anonymous classes follow the closure rules instead, and
[Section 4](https://www.php-fig.org/per/coding-style/#4-classes-properties-and-methods) says the term "class" "refers to all classes, interfaces, traits, and enums", with no mention of anonymous
classes.

**Decision:** An anonymous class follows the closure rules. A blank line after its opening brace is allowed, and one
before its closing brace is reported, as for a closure. See [A Blank Line at the Start of a Body](#a-blank-line-at-the-start-of-a-body). [Jumbo](Jumbo.md) reports both, as
an extra rule.

**Why:** Section 8 names closures explicitly, and section 4's definition of a class leaves anonymous classes out.

---

### After the Arguments

**Issue:** [Section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes)'s text doesn't say whether a space goes between an anonymous class's arguments and `extends` or
`implements`. Its example writes `new class ($a) extends \Foo implements`.

**Decision:** Exactly one space is required, so `new class ($a)extends Foo` is reported, as an error, and fixed.

**Why:** The example shows it, and it's the spacing before `extends` and `implements` everywhere else. More than one
space there was already an error, so none is treated the same way.

---

### A Comment Before an Anonymous Class's Attributes

**Issue:** [Section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes) says:

> If an anonymous class has attributes, they MUST be placed starting on the next line after the `new` keyword, indented
> once

It doesn't say whether a comment may come between.

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
doesn't, as the attributes start "on the next line after the `new` keyword".

---

## 9. Enumerations

### Enum Constant Names

**Issue:** PSR-1 says "Class constants MUST be declared in all upper case", but [Section 9](https://www.php-fig.org/per/coding-style/#9-enumerations) says:

> Constants in Enumerations MAY use either PascalCase or UPPER_CASE capitalization. PascalCase is RECOMMENDED, so that
> it is consistent with case declarations.

**Decision:** PascalCase passes, UPPER_CASE gets a severity 5 warning, and anything else, such as `camelCase` or
`Foo_Bar`, is an error.

```php
enum Suit
{
    case Hearts;

    public const Wild = self::Hearts;  // Right.
    public const WILD = self::Hearts;  // A warning.
    public const wild = self::Hearts;  // An error.
}
```

**Why:** Section 9 is more specific than PSR-1. RECOMMENDED is a SHOULD (see [Keywords](#keywords)), so not following it is a
warning.

---

### The Space After `case`

**Issue:** [Section 9](https://www.php-fig.org/per/coding-style/#9-enumerations) doesn't say how many spaces go between an enum case's `case` keyword and its name. Its example
writes `case Hearts = 'H';`.

**Decision:** Exactly one, so `case Hearts` is reported. A line break counts as more than one space.

**Why:** The example shows it, and it's the pattern for every keyword before a name, such as `const`, `function` and
`class`. See [Spacing Shown by Example](#spacing-shown-by-example).

---

## 10. Heredoc and Nowdoc

### Placement and Indent

**Issue:** [Section 10](https://www.php-fig.org/per/coding-style/#10-heredoc-and-nowdoc) says:

> Declared heredocs or nowdocs MUST begin on the same line as the context the declaration is being used in. Subsequent
> lines in the heredoc or nowdoc MUST be indented once past the scope indentation they are declared in.

Some contexts, such as an argument list, already start new lines, so the "same line" isn't always clear.

**Decision:** A `<<<` must be on its context's line, except after `(`, `,`, `[` or a binary operator. The body and
closing marker are indented once past the `<<<` line. A heredoc with no interpolation or escape sequence gets a warning,
as "A nowdoc SHOULD be used wherever possible".

```php
// Right.
$sql = <<<'SQL'
    SELECT *
    FROM users
    SQL;

run(
    'users',
    <<<'SQL'
        SELECT *
        FROM users
        SQL,
);

// Reported: the nowdoc doesn't start on the `$sql =` line.
$sql =
<<<'SQL'
    SELECT *
    SQL;
```

**Why:** After those tokens, a new line is the context's own layout, as in the spec's example of a nowdoc passed as an
argument.

---

## 11. Arrays

### The Opening Bracket

**Issue:** [Section 11](https://www.php-fig.org/per/coding-style/#11-arrays) says:

> When an array declaration is split across multiple lines, the opening bracket MUST NOT be placed on its own line.

But the section's own example starts a nested array's bracket on a line of its own. So does any array that's an argument
in a split argument list, as there "there MUST be only one argument per line". So does an array that starts a split
condition, as "the first condition MUST be on the next line".

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
array that starts its conditions does. The same goes for a split condition: the condition rule and the bracket rule
can't both be met. Section 11's own example breaks its rule, by starting a nested array on a line of its own, so that
rule plainly has exceptions. The condition rule has none, so it's the stronger of the two. A destructuring assignment is
a list, not an array value.

---

### One Value Split Over Lines

**Issue:** [Section 11](https://www.php-fig.org/per/coding-style/#11-arrays)'s rules apply "When an array declaration is split across multiple lines". It's unclear whether an
array is split when it only spans lines because its one value does. [The Split-Argument Exemption](#the-split-argument-exemption) says a single argument
split over lines doesn't split an argument list.

**Decision:** It's split. The value goes on the line after `[`, and `]` on the line after the value.

```php
// Right.
$handlers = [
    function (): void {
        run();
    },
];

// Reported.
$handlers = [function (): void {
    run();
}];
```

**Why:** The exemption is only for argument lists, and section 11 has nothing like it.

---

### Spacing and the Closing Bracket

**Issue:** [Section 11](https://www.php-fig.org/per/coding-style/#11-arrays)'s text doesn't set comma spacing or space inside the brackets. It says "the first value in the
array MUST be on the next line", and "The closing bracket MUST be placed on the next line after the last value", without
saying whether a blank line may come between.

**Decision:** One space after a comma, no space inside the brackets, and no blank line after a multi-line array's `[` or
before its `]`. Blank lines between two values are allowed. An empty array has no space or line break inside either, so
`[]`, not `[ ]`.

**Why:** Section 11 introduces its example with "The following example shows correct array usage". A value two lines
after the `[` isn't on the next line, and the same goes for the `]`. Nothing forbids a blank line between two values
(see [Blank Lines](#blank-lines)).

---

### A Closure Attribute in an Array Element

**Issue:** [Section 12.2](https://www.php-fig.org/per/coding-style/#122-placement) doesn't say where a closure's attribute goes when the closure is an array element.

**Decision:** It may be on the closure's line or the line before.

```php
$handlers = [
    #[Pure] fn(): int => 1,
    #[Pure]
    fn(): int => 2,
];
```

**Why:** The attribute is part of the value, and neither placement breaks a rule.

---

## 12.2 Placement

### Targets 12.2 Doesn't List

**Issue:** [Section 12.2](https://www.php-fig.org/per/coding-style/#122-placement) says:

> Attributes on classes, methods, functions, constants and properties MUST be placed on their own line, immediately
> prior to the structure being described.
>
> For attributes on parameters, if the parameter list is presented on a single line, the attribute MUST be placed inline
> with the parameter it describes, separated by a single space.

It doesn't mention enum cases, property hooks, or closures.

**Decision:**

- An enum case: the attribute goes on the line before, as for a constant.
- A hook with a body in braces, as in `get {`: the attribute goes on the line before, as for a method.
- A short hook, as in `get => 1;`, or one with no body, as in `get;`: the attribute may be on its line, followed by a
  single space, or on the line before.
- A closure as a call's argument follows the parameter rule. Elsewhere, such as in an assignment or an array, its
  attribute may be on the closure's line or the line before. One on the closure's line is followed by a single space,
  and two or more blocks go on lines of their own, before the closure.

```php
enum Suit
{
    #[Deprecated]
    case Hearts;
}

class Box
{
    public int $size {
        #[Deprecated]
        get {
            return 1;
        }
        #[Pure] set => $value;
    }
}
```

An attribute that must be on the line before its target must also have that line to itself, so one that follows other
code on its line is reported, as in `case Hearts; #[A]`, and so is `#[Deprecated] case Hearts;`.

**Why:** [Section 9](https://www.php-fig.org/per/coding-style/#9-enumerations) says "Enumerations (enums) MUST follow the same guidelines as classes
(including the guidelines for class methods and constants)", and PHP itself treats an enum case's attribute as a
constant's. A hook with a body in braces is laid out like a method, so its attribute goes where a method's does. A short
hook or a hook with no body fits on a line, and a promoted property's hook has to, "defined on a single line", so the
attribute may share it. The single space follows the rule for a parameter in a single-line list. Two blocks "in a
multi-line context" go on separate lines, and a closure outside a single-line list is one.

---

### Two Blocks in a Single-line List

**Issue:** [Section 12.2](https://www.php-fig.org/per/coding-style/#122-placement) says:

> If two separate attribute blocks are used in a multi-line context, they MUST be on separate lines with no blank lines
> between them.

It doesn't say whether two blocks may share a single-line parameter list, as in `function a(#[A] #[B] int $b)`.

**Decision:** They may. The blocks stay inline, each followed by exactly one space. The same goes for a closure in a
single-line argument list.

**Why:** A single-line list isn't a multi-line context, and in one, "the attribute MUST be placed inline with the
parameter it describes, separated by a single space".

---

### Comments Between an Attribute and Its Target

**Issue:** [Section 12.2](https://www.php-fig.org/per/coding-style/#122-placement) puts an attribute "immediately prior to the structure being described", or in a single-line
parameter list, "inline with the parameter it describes, separated by a single space". Both rules are written for one
attribute. The spec doesn't say how they apply to two or more blocks, or whether a comment may come between them.

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

This applies to every target, including a parameter, where the rule only says "prior to".

**Why:** It's the same reading in both layouts. A block that describes a target is as close to it as the other blocks
allow, as in [Two Blocks in a Single-line List](#two-blocks-in-a-single-line-list), where each block is followed by a single space. A comment at the end of a
line doesn't move an attribute any further from its target.

---

## See Also

- [PERCS31](PERCS31.md): the standard that applies these calls.
- [PERCS31 Custom Sniffs](PERCS31_Custom_Sniffs.md) and [PERCS31 Inherited Sniffs](PERCS31_Inherited_Sniffs.md): the sniffs that apply them.
- [Jumbo](Jumbo.md): PERCS31 plus extra rules.
- [PER Coding Style 3.1](https://www.php-fig.org/per/coding-style/)
