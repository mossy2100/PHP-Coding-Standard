# PER-CS 3.1 Judgement Calls

[PER Coding Style 3.1](https://www.php-fig.org/per/coding-style/) and PSR-1 leave some points unclear, contradict
themselves in places, and say nothing about others. A coding standard still has to do something in each case. This page
lists the calls the [PERCS31](PERCS31.md) standard makes, and why.

Each item has three parts:

- **Issue:** the ambiguity, contradiction or omission in the spec.
- **Ruleset:** what PERCS31 does about it.
- **Why:** the reasoning.

The general principles come first, as they settle most of the other cases. The items after them follow the spec's
section order.

---

## General Principles

### Rule Text and Examples

**Issue:** The spec's code examples sometimes show more than its text says, and a few contradict it.

**Ruleset:**

- The rule text beats an example that contradicts it.
- Where the text is silent, an example sets a rule when the spec presents it as a template, as in "Note the placement
  of parentheses, spaces, and braces", "MUST be formatted as below" or "The following example shows correct array
  usage".
- Otherwise an example shows one allowed form, and is used as evidence when reading unclear text.

**Why:** The text is the normative part of the spec. An example introduced as showing the correct form is the spec's
own statement of the rule, so it's treated as one.

---

### Unclear Text

**Issue:** Some rules can be read more than one way.

**Ruleset:** PERCS31 chooses the reading that best fits the spec's examples, its rules for similar constructs, and
PHP_CodeSniffer's reading of the same PSR-12 wording where there is one. It allows something only when nothing in the
spec or established practice points either way.

**Why:** A reading backed by the spec's own patterns is more likely to be what its authors meant than the most lenient
one. A little extra strictness, consistent with the rest of the spec, is unlikely to trouble anyone.

---

### Spec Examples That Break the Spec

Several examples are shown in the spec that break its own rules. In these cases, the text is considered to overrule
the example.

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

**Ruleset:** A PSR-12 or PSR-1 sniff that rejects something PER allows is replaced, configured or excluded.

**Why:** PERCS31 implements PER 3.1. A PSR-12 report on code PER allows is a false positive.

---

### What Goes in PERCS31

**Issue:** The spec doesn't cover every formatting question, such as which line a comma goes on.

**Ruleset:** PERCS31 contains only the rules the spec requires, read as described on this page. Anything else is left
unchecked. Extra rules go in [Jumbo](Jumbo.md).

**Why:** PERCS31 is meant to be the spec as written, so a project can adopt it without taking on anyone's preferences.

---

## Overview

### Keywords

**Issue:** The spec uses RFC 2119 keywords, but doesn't say how a tool should report each one.

**Ruleset:**

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

**Ruleset:**

- A PascalCase name starts with a capital, has only letters and digits, and has at least one lower-case letter.
- This applies to classes, interfaces, traits, enums and enum cases, as an error. An enum constant may be in PascalCase
  or UPPER_CASE (9).
- A name with no lower-case letter fails: `FOO`, `OK`, `A`, `C2`, `B2B`.
- An acronym or an initial in capitals is still PascalCase: `HTTPStatus`, `XMLParser`, `ClassA`, `IAnimal`. The acronym
  gets a warning instead (2.7).

**Why:** PascalCase mixes upper and lower case, so a name with no lower-case letter isn't in it. The MUST says nothing
about acronyms, which 2.7 covers as a RECOMMENDS. Section 4 uses "class" to cover all four kinds of class-like
structure.

---

### Global Constants (PSR-1)

**Issue:** PSR-1 says "Class constants MUST be declared in all upper case". It says nothing about global constants.

**Ruleset:** Only class constants are checked for upper case, and for a leading underscore (4.3). A global constant,
from `const` or `define()`, is only checked by `PERCS31.NamingConventions.AcronymCasing`, and only if it has a
lower-case letter. Jumbo requires UPPER_CASE for a global constant, with no leading underscore.

**Why:** PSR-1's rule names class constants only. 4.3's rule is about showing visibility, which a global constant
doesn't have.

---

## 2.3 Lines

### Line Length

**Issue:** "The soft limit on line length MUST be 120 characters" is a MUST that requires only a soft limit. "Lines
SHOULD NOT be longer than 80 characters" is a SHOULD that's rarely followed.

**Ruleset:**

- A line over 120 characters is a severity 5 warning. There's no hard limit.
- A line over 80 characters is a severity 4 warning, hidden by default.

**Why:** A soft limit is a warning by definition. The 80-character SHOULD is kept, but out of sight unless asked for.
See [Line Length](LineLength.md) to change the limit.

---

## 2.4 Indenting

### PHP Inside Markup

**Issue:** 2.4 sets the indent for code, but not for PHP blocks inside HTML or other markup.

**Ruleset:** Code in a PHP block may be level with its tags, or one level past them. The indent of `?>` isn't checked.

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

**Ruleset:** A comment indented too little is reported, as code is, and so is one indented too far on a body's first
line. A comment indented too far after a statement isn't reported. Jumbo reports it, except at the top level.

**Why:** A comment belongs to the code around it, so the indent checks for code treat it as code. Beyond that, PER has
no rule for a comment's indent, so PERCS31 has no check of its own for one.

---

### Further Indents

**Issue:** Several constructs need an indent the text doesn't spell out.

**Ruleset:** Each is measured exactly, too little as well as too much:

- A group `use`'s items: once past the `use` line, as section 3's example shows.
- A value wrapped after `=>`: once past its key's line (11).
- Dead code after a `break`: level with the statement that ends the case.
- A statement in a closure, anonymous class or `switch`: the usual indent for its body.

**Why:** Each follows the nearest example, or the general rule of one indent per level.

---

## 2.5 Keywords and Types

### `|` in a Multi-catch

**Issue:** PHP calls `catch (A|B $e)` a multi-catch, not a type declaration. It's unclear whether 2.5's rule on
compound types covers it, or whether `|` is a binary operator there.

**Ruleset:** It's read as a union type, so no space is allowed around the `|`.

**Why:** `A|B` lists the exception types the variable can hold, which is what a union type does.

---

### Magic Constants

**Issue:** 2.5 requires reserved keywords in lower case. PHP's list of reserved keywords includes the magic constants,
such as `__DIR__`, which are conventionally upper case, and whose case PHP ignores.

**Ruleset:** Their case isn't checked. Jumbo requires upper case.

**Why:** Reading the rule as requiring `__dir__` would go against near-universal practice, and it's unlikely the spec
means that.

---

### Hook Names

**Issue:** 2.5 requires reserved keywords in lower case. A property hook's `get` and `set` aren't on PHP's list of
reserved keywords, as they're only keywords inside a hook list.

**Ruleset:** They must be in lower case, so `GET => 1;` is reported.

**Why:** Section 4.10 calls one "the hook keyword", every example writes it in lower case, and 4.6 says "All keywords
MUST be all lower-case".

---

### `(double)` and `(binary)` Casts

**Issue:** 2.5 requires the short form of a type keyword, "i.e. `bool` instead of `boolean`, `int` instead of
`integer` etc." `(double)` is an alias for `(float)`, and `(binary)` for `(string)`. Neither is a longer spelling of
the same word, so it's unclear whether "etc." covers them.

**Ruleset:** It does. Both are reported, as `(boolean)` and `(integer)` are. `(double)` is fixed to `(float)`, and
`(binary)` to `(string)`.

**Why:** PHP treats the four alike. PHP 8.5 deprecates each as a "non-canonical cast", and names the form to use
instead.

---

## 2.6 Trailing Commas

### Which Lists

**Issue:** 2.6 lists the kinds of list it covers, but PHP has others the list doesn't name.

**Ruleset:** A multi-line list needs a trailing comma, and a single-line list mustn't have one, for arguments,
parameters, closure `use` lists, arrays, `match` arms, group `use`, `list()`, an anonymous class's arguments,
`clone()`, `exit()`, `die()` and attribute blocks. `clone ($a)->c` is skipped, as its parentheses may only group the
object.

**Why:** The rule's purpose, cleaner diffs, applies equally to every comma-separated list split over lines.

---

### One Argument Split Over Lines

**Issue:** 2.6 requires a trailing comma in a list "split across multiple lines", and forbids one in a list "contained
on a single line". 4.7 says a single argument split across lines, such as an array or a closure, "does not constitute
splitting the argument list itself". So `foo([` ... `],);` isn't split, and isn't on a single line either.

**Ruleset:** An argument list is split when its first argument starts on the line after the `(`, or its `)` is on a
line of its own. Any other list mustn't have a trailing comma, so `],);` is reported. This covers the arguments of a
call, an attribute, an anonymous class, `clone()`, `exit()` and `die()`.

**Why:** 2.6's two cases are read as "split" and "not split". 4.7's own examples of this shape, `], $baz);` and `});`,
have no comma.

---

### `match` Conditions

**Issue:** 2.6 doesn't say whether a `match` arm's list of conditions is one of its lists. 5.2's example only has
conditions on one line.

**Ruleset:** A single-line list of conditions mustn't end in a comma. A split list may end in one or not. Jumbo
disallows it.

**Why:** A single-line list never takes a trailing comma. With no example of a split list, both forms are allowed.

---

### A Comma That Starts a Line

**Issue:** 4.5 and 4.7 say there MUST NOT be a space before a comma in a parameter or argument list. The spec doesn't
say whether a line break counts, or cover other lists.

**Ruleset:** A comma may not start a line, in any list, including after a heredoc's closing marker.

**Why:** A line break before a comma is space before it, and every split list in the spec ends its lines with the
comma. Section 10's example puts the comma straight after a heredoc's closing marker.

---

## 2.7 Naming

### Acronyms

**Issue:** 2.7 RECOMMENDS treating acronyms as words, but doesn't say which names it covers, or what counts as an
acronym.

**Ruleset:** `PERCS31.NamingConventions.AcronymCasing` applies it to classes, interfaces, traits, enums, enum cases,
enum constants, namespaces, functions, methods, properties, parameters, variables, and import and trait aliases, as a
severity 5 warning.

- An acronym is a word of two or more capitals, so `HTTPClient`, `getID`, `parseURL` and `IHeartAI` are reported.
- A single capital is an initial, not an acronym, so `ClassA`, `EDayOfWeek` and `getX` pass.
- There's no plural rule. `getURLs` reads as `get`, `UR`, `Ls`, and is reported. `getIDs` reads as `get`, `I`, `Ds`,
  and passes.
- A name with no lower-case letter, such as `HTTP_OK`, is UPPER_CASE, and isn't checked.
- The advice to avoid abbreviations isn't checkable, so it isn't checked.

**Why:** The recommendation isn't limited to one kind of name. Splitting a name into words can only be a best effort,
so a simple rule is applied consistently.

---

## 3. Declare Statements, Namespace, and Import Statements

### Several Imports in One `use`

**Issue:** The header has "One or more class-based `use` import statements", but the spec doesn't say whether one
statement may import several names.

**Ruleset:** `use A, B;` is allowed.

**Why:** PSR-2 required one `use` keyword per declaration. PSR-12 dropped that rule when it added group `use`, and PER
doesn't have it either.

---

### Imports After Code

**Issue:** The header's blocks end with "The remainder of the code in the file", but the spec doesn't say anything
about an import after that.

**Ruleset:** An import after the rest of the code is reported. A file with more than one namespace is skipped, as each
namespace has its own imports.

**Why:** An import after the code breaks the header's order.

---

### `declare` Statements

**Issue:** "Declare statements MUST NOT contain any spaces inside the parentheses" doesn't say whether that includes
after a comma between directives.

**Ruleset:** It does, so `declare(ticks=1,strict_types=1);`. Several directives and string values are allowed. There
are no blank lines between `declare` statements.

**Why:** A space after the comma is still inside the parentheses.

---

### `strict_types` in a File With Markup

**Issue:** In a file with markup outside PHP tags, the strict types declaration "MUST be on the first line of the
file". The spec doesn't say how a shebang line fits in.

**Ruleset:** It must be `<?php declare(strict_types=1) ?>` on the first line. A file with a shebang can't have that,
so it isn't checked. `strict_types` isn't required.

**Why:** The rule can't be met in a file with a shebang. PER doesn't require strict types, so neither does PERCS31.

---

## 4. Classes, Properties, and Methods

### "Any Closing Brace"

**Issue:** "Any closing brace MUST NOT be followed by any comment or statement on the same line" doesn't say which
braces.

**Ruleset:** It covers the closing brace of a class, interface, trait, enum, method, function, property hook list and
hook body. Not an anonymous class, a closure or a control structure. Only a comment is reported.

**Why:** It sits in the section on classes. An anonymous class, a closure or a control structure is part of a larger
expression or statement, which often continues after the brace.

---

## 4.2 Using Traits

### Blank Lines Around `use`

**Issue:** 4.2 says a class with more in it "MUST have a blank line after the `use` import statement". It doesn't say
exactly one, or whether a blank line may come between two `use` statements.

**Ruleset:** No blank line between trait `use` statements, and exactly one after the last, so two or more are
reported.

**Why:** "A blank line" is read as one, as "a space" is in 4.3. 4.2's examples show no blank line between `use`
statements, and one after the last.

---

## 4.3 Properties and Constants

### "A Space" After the Type

**Issue:** 4.3 says a property's type is followed by "a space", but section 6 requires "at least one space" around
operators.

**Ruleset:** Exactly one, so `public int   $a` is reported.

**Why:** "A space" means one. Section 6's wording is for operators only.

---

### A Double Underscore Prefix

**Issue:** Names "MUST NOT be prefixed with a single underscore". It's unclear whether a double one is covered.

**Ruleset:** `$__a`, `__A` and `__a()` pass.

**Why:** "Single" excludes a double underscore, which PHP uses for magic methods. PSR-2's method sniff reads it the
same way.

---

### Visibility on a Promoted Parameter

**Issue:** 4.3 requires visibility on properties, but doesn't mention constructor promotion, where `readonly` alone
makes a property.

**Ruleset:** A promoted parameter with no visibility, such as `readonly int $a`, is reported. A set-visibility, as in
`private(set)`, counts.

**Why:** A promoted parameter declares a property, so the property rule applies.

---

## 4.4 Methods and Functions

### Empty Bodies

**Issue:** An empty method or function body SHOULD be `{}` (4.4), and an empty closure or anonymous class body MUST be
(7 and 8). It's unclear whether a body with only a comment is empty.

**Ruleset:** A class or function body is a warning, and a closure or anonymous class body an error. A body with only a
comment isn't empty. An empty closure that SHOULD be an arrow function isn't checked.

**Why:** A comment is content. Changing a closure to `fn() => null` changes its return value, so it isn't a style fix.

---

## 4.5 Method and Function Parameters

### Blank Lines in Parameter Lists

**Issue:** 2.3 allows blank lines "to indicate related blocks of code", without saying where. 12.2 allows a blank line
between one parameter and the attributes of the next. Nothing covers other blank lines in a parameter list.

**Ruleset:** A single blank line between parameters is allowed. Any other blank line in a parameter or closure `use`
list is reported: two or more in a row, one before `)`, and one between `use` items.

**Why:** 12.4's example shows one blank line between parameters. Nothing in the spec shows blank lines anywhere else in
these lists.

---

### A Split Return Type

**Issue:** A union return type can be split over lines (2.5). 4.5 puts `)` and `{` together in a split parameter list.
It's unclear how the two combine.

**Ruleset:**

- `): A` then `|B {` on the next line is reported, as it separates `)` and `{`.
- `function a(): int` then `|string` then `{` passes with a single-line parameter list. Each line has one type, as 2.5
  requires, and the return type starts on the `)` line, as 4.5 requires.

**Why:** Each rule is followed as written.

---

### A Split Array Default Value

**Issue:** 4.7 lets a single argument, such as an array, split over lines without splitting the argument list. It's
unclear whether the same applies to a parameter's default value.

**Ruleset:** It doesn't. `$f = [` then `1,` then `]): void {` splits the parameter list.

**Why:** 4.7's exemption is only for calls.

---

## 4.6 Modifier Keywords

### Modifier Order on Constants

**Issue:** 4.6 sets the order of modifier keywords for classes, properties and methods, not constants.

**Ruleset:** A constant's modifier order isn't checked. Its spacing is.

**Why:** The order rule names its targets. The single space between keywords applies everywhere.

---

## 4.7 Method and Function Calls

### Blank Lines Between Arguments

**Issue:** 4.7 doesn't forbid a blank line between a call's arguments.

**Ruleset:** A blank line between arguments, or before the call's `)`, is reported.

**Why:** 4.7 introduces its examples as "The following examples show correct argument usage", and none has a blank
line.

---

### Method Chaining

**Issue:** When a chain is split, "the first method MUST be on the next line", but the spec doesn't say what counts as
the start of the chain.

**Ruleset:** The first method call goes on the next line. Property accesses before it, as in `$this->em`, may stay.
`Foo::create()` then `->run()` passes, with the static call as the receiver.

**Why:** The rule is about methods. A property access or a static call is the object the chain starts from.

---

### Closures as Arguments

**Issue:** 4.7 doesn't say whether a closure split over lines splits the argument list.

**Ruleset:** It doesn't. A named argument's closure may keep its attribute inline, as in
`callback: #[Pure] function () {`.

**Why:** 4.7's examples pass a multi-line closure as one argument, and section 7 covers a closure in an argument list.

---

## 4.9 Property Hooks

### Hook Layout

**Issue:** 4.9 doesn't set the spacing around a by-reference `&` or a hook's own `{`.

**Ruleset:**

- One space before a hook's own `{`, as in `get {`.
- No space after a by-reference hook's `&`, as in `&get`.
- `{ get; set; }` on one line is one block, not two statements.

**Why:** The first follows 4.9's examples. The second follows 4.5's rule for a by-reference parameter.

---

### `set` Hook Parameters

**Issue:** 4.9's text doesn't set the spacing of a `set` hook's parameter list.

**Ruleset:** No space after `set`, and none just inside the parentheses. `set (string $value)` and
`set( string $value )` are reported, and fixed to `set(string $value)`.

**Why:** 4.9's example writes `set(string $value)`, which matches the rule for a method's parameters (4.4). A hook is
declared like a method, so it isn't read as a call.

---

## 4.10 Interface and Abstract Properties

### `get` Before `set`

**Issue:** 4.10 requires `get` before `set` for an abstract or interface property, whose hooks have no bodies. It says
nothing about other hook lists.

**Ruleset:** `get` comes first in every hook list.

**Why:** Every example in 4.9 has `get` first too, and one order everywhere is easier to read.

---

## 5. Control Structures

### A Loop With No Body

**Issue:** "The body of each structure MUST be enclosed by braces." The spec doesn't say what a loop with no body
needs, as in `while (next($items));`.

**Ruleset:** It's allowed. No braces are required.

**Why:** There's no body to enclose. PHP_CodeSniffer reads the same PSR-12 rule this way.

---

### An Empty Body

**Issue:** "The body MUST be on the next line after the opening brace", but the spec doesn't say how an empty body is
written. It gives `{}` to an empty class (4), function (4.4) and closure (7), and doesn't mention control structures.

**Ruleset:** An empty body may be written as `{}`, as in `catch (Exception $e) {}`, or with the braces on separate
lines. Jumbo requires `{}`.

**Why:** With no body, there's nothing to put on the next line. `{}` is how the spec writes every other empty body.

---

### "Indented at Least Once"

**Issue:** A split condition's "subsequent line is indented at least once", but the spec doesn't say how far is too
far, or how it applies to a `case`.

**Ruleset:** It applies to a split condition in a control structure's parentheses, including a `case` condition. Any
indent of at least one level passes. `case (` with the first condition on the same line is reported.

**Why:** "At least once" sets a minimum. Each control structure says "the first condition MUST be on the next line".

---

## 5.2 `switch`, `case`, `match`

### Single-line `match`

**Issue:** 2.6 names "`match()` statement branches" among the lists that take a trailing comma, which suggests a
single-line `match` is possible. Section 5 puts a control structure's body on the line after its opening brace.

**Ruleset:** A `match` on one line is reported.

**Why:** 2.6 sets the trailing comma rule for a `match`'s arms, and doesn't say a `match` may be on one line. 5.2 is
more specific about how a `match` is written, and its example has each arm on its own line.

---

### The `match` Example

**Issue:** 5.2's `match` example shows spacing and layout its text doesn't state.

**Ruleset:** One space around `=>` and after each comma, no alignment, one arm per line, a trailing comma after each
arm, and `}` on its own line. The comma spacing is also applied to lists with no example, such as `echo`, `global` and
a group `use`.

**Why:** The example is the template for a `match`. One space after a comma is the spec's pattern everywhere it shows
one.

---

### The `case` Line

**Issue:** The `case` line "MUST consist only of" the label, but it's unclear whether that includes a comment.

**Ruleset:** It doesn't. A comment is allowed there, as in `case 1: // The first.` Code is reported.

**Why:** The rule describes the code on the line. Where the spec bans a comment, it says so, as it does after a closing
brace (4). PHP_CodeSniffer reads the same PSR-12 rule this way.

---

### Blank Line After a `case` Label

**Issue:** 5.2 doesn't forbid a blank line straight after a `case` or `default` label.

**Ruleset:** It's reported.

**Why:** 5.2's example, introduced as a template for its rules, has none.

---

### `default`

**Issue:** 5.2 requires a comment when a non-empty `case` falls through, and says a `case` body MUST NOT be wrapped in
`{}`. It doesn't say whether either rule covers `default`.

**Ruleset:** Both do. A non-empty `default` that falls through needs a comment, and a braced `default` body is
reported.

**Why:** A `default` is a `case` in all but name. It can fall through, and take braces, just as a `case` can.

---

## 5.4 `for` and 5.5 `foreach`

### Spacing

**Issue:** The text doesn't set the spacing around `;`, `=>` and `as`. The examples do.

**Ruleset:** The examples' spacing is required. A `foreach` split inside its parentheses only gets section 5's general
spacing.

**Why:** The examples are the only guide. No example splits a `foreach`.

---

## 6. Operators

### Operators Not Listed

**Issue:** Section 6 lists operators by kind, but leaves out unary `-`, `!`, `@`, `~`, a reference `&`, spread, `->`,
`?->` and `::`.

**Ruleset:** They aren't checked.

**Why:** The spec leaves them undefined, and there's no consistent practice to infer a rule from.

---

## 6.2 Binary Operators

### `=>` in an Array or `yield`

**Issue:** `=>` isn't one of 6.2's operators. Section 11's array example, which "shows correct array usage", has one
space each side, but there are too few examples to rule out lining arrows up.

**Ruleset:** An array's `=>` needs at least one space each side. More is allowed. Jumbo requires exactly one. A
`yield`'s `=>` isn't checked. Jumbo requires exactly one space each side of it too.

**Why:** The spec always shows a space each side, and never none. It gives too little to forbid alignment.

---

### `=&`

**Issue:** It's unclear whether `=&` is one operator or `=` followed by a reference `&`.

**Ruleset:** It's one operator, so `$x =& $a` passes. `$x=&$a` and `$x =&$a` are fixed to `$x = &$a`.

**Why:** Both readings are common, so both spaced forms pass. The unspaced forms break the rule either way.

---

### A Default Value's `=`

**Issue:** It's unclear whether a parameter's default value `=` is an assignment operator.

**Ruleset:** It is, so it needs at least one space each side, and alignment is allowed.

**Why:** It looks and reads like an assignment. 6.2 requires "at least one space" around a binary operator, which
leaves room for alignment.

---

## 6.3 Ternary Operators

### `?:`

**Issue:** 6.3 doesn't say whether a short ternary's `?` and `:` may be apart, as in `$a ? : $b`.

**Ruleset:** They may not. The fix joins them into `?:`.

**Why:** 6.3 says the short ternary follows the rules for other binary operators, and its examples write `?:`.

---

## 6.4 Operator's Placement

### Where a Wrapped Operator Goes

**Issue:** 6.4 says a wrapped operator starts the next line, without saying whether that covers expressions inside a
list item, a `match` arm or parentheses.

**Ruleset:** It applies wherever an expression is split. The operator starts the line, indented once. This covers
assignment operators, `.`, and an arrow function's `=>`.

**Why:** The rule is about expressions, not statements.

---

### When a Ternary Is Split

**Issue:** 6.4 sets the layout of a split ternary, but not what counts as split.

**Ruleset:** A ternary is split only when a line break is right before or after its `?` or `:`, ignoring comments. Then
it takes the three-line form. A nested ternary is indented once more. A short ternary may be on two lines, with `?:`
starting the second.

**Why:** A value or condition split over lines doesn't change the ternary's structure.

---

### Heredoc After a Wrapped `=`

**Issue:** 6.4 lets `=` start a wrapped line. Section 10 puts a heredoc on its context's line.

**Ruleset:** `$x` then `= <<<` on the next line is reported.

**Why:** Section 10 is more specific.

---

## 7.1 Short Closures

### Arrow Functions

**Issue:** 7.1 gives arrow functions few rules of their own.

**Ruleset:** No space after `fn`. A split parameter list follows the closure rules. No space before the closing
semicolon. A wrapped `=>` is indented once past the line `fn` starts on.

**Why:** An arrow function is a closure, so the closure rules fill the gaps.

---

## 8. Anonymous Classes

### The Opening Brace

**Issue:** "The opening brace MAY be on the same line as the `class` keyword so long as the list of `implements`
interfaces does not wrap." The spec doesn't say whether the next line is allowed then.

**Ruleset:** Both are allowed.

**Why:** "MAY" doesn't forbid the other placement.

---

### Around the Opening Brace

**Issue:** Section 8's text doesn't say how many spaces go before the opening brace, or whether the body may start on
its line.

**Ruleset:** One space before a brace on the declaration's line, and nothing after the brace on its line, not even a
comment. An empty body, `{}`, is unaffected.

**Why:** Section 8 says anonymous classes follow the same guidelines as closures, where both are required, and its
examples show both.

---

## 9. Enumerations

### Enum Constant Names

**Issue:** PSR-1 requires upper case for class constants, but section 9 allows PascalCase or UPPER_CASE for a
constant in an enum, and RECOMMENDS PascalCase.

**Ruleset:** PascalCase passes, UPPER_CASE gets a severity 5 warning, and anything else, such as `camelCase` or
`Foo_Bar`, is an error.

**Why:** Section 9 is more specific than PSR-1.

---

## 10. Heredoc and Nowdoc

### Placement and Indent

**Issue:** Section 10 puts a heredoc on its context's line, but some contexts, such as an argument list, already start
new lines.

**Ruleset:** A `<<<` must be on its context's line, except after `(`, `,`, `[` or a binary operator. The body and
closing marker are indented once past the `<<<` line. A heredoc with no interpolation or escape sequence gets a
warning, as it SHOULD be a nowdoc.

**Why:** After those tokens, a new line is the context's own layout.

---

## 11. Arrays

### The Opening Bracket

**Issue:** The opening bracket of a split array "MUST NOT be placed on its own line". Section 11's own example puts a
nested array's bracket at the start of a line. So does 4.7, for an array that's an argument in a split argument list,
as each argument there goes on its own line.

**Ruleset:** The rule is applied after `=`, `=>`, `return` and the like, so `$a = [`. The exceptions are:

- An element of a multi-line array, as in the example.
- An argument in an argument list, so `foo(` then `[` on the next line is allowed, as is `foo([`.
- A condition of a `match` arm.
- A destructuring assignment.

**Why:** The example shows the elements exception. An argument list split one per line leaves an array argument nowhere
else to start, short of assigning it to a variable first, which PER doesn't ask for. A `match` arm starts a line, so an
array that starts its conditions does. A destructuring assignment is a list, not an array value.

---

### One Value Split Over Lines

**Issue:** Section 11's rules apply "when an array declaration is split across multiple lines". It's unclear whether an
array is split when it only spans lines because its one value does, as in `[function () {` ... `}]`. 4.7 says a single
argument split over lines doesn't split an argument list.

**Ruleset:** It's split. The value goes on the line after `[`, and `]` on the line after the value.

**Why:** 4.7's exemption is only for calls, and section 11 has nothing like it.

---

### Spacing and the Closing Bracket

**Issue:** The text doesn't set comma spacing or space inside the brackets.

**Ruleset:** One space after a comma, no space inside the brackets, and no blank line before a multi-line array's `]`.

**Why:** Section 11 introduces its example as showing "correct array usage".

---

### A Closure Attribute in an Array Element

**Issue:** 12.2 doesn't say where a closure's attribute goes when the closure is an array element.

**Ruleset:** It may be on the closure's line or the line before.

**Why:** The attribute is part of the value, and neither placement breaks a rule.

---

## 12.2 Placement

### Targets 12.2 Doesn't List

**Issue:** 12.2 sets the placement for classes, methods, functions, constants, properties and parameters. It doesn't
mention enum cases, property hooks, or closures.

**Ruleset:**

- An enum case: the attribute goes on the line before, as for a constant.
- A hook with a body in braces, as in `get {`: the attribute goes on the line before, as for a method.
- A short hook, as in `get => 1;`, or one with no body, as in `get;`: the attribute may be on its line, followed by a
  single space, or on the line before.
- A closure as a call's argument follows the parameter rule. Elsewhere, it isn't checked.

**Why:** Section 9 says enums follow the guidelines for class constants, and PHP itself treats an enum case's
attribute as a constant's. A hook with a body in braces is laid out like a method, so its attribute goes where a
method's does. A short hook or a hook with no body fits on a line, and a promoted property's hook has to (4.9), so the
attribute may share it. The single space follows 12.2's rule for a parameter in a single-line list.

---

### Two Blocks in a Single-line List

**Issue:** Two attribute blocks "in a multi-line context" go on separate lines. The spec doesn't say whether two blocks
may share a single-line parameter list, as in `function a(#[A] #[B] int $b)`.

**Ruleset:** They may. The blocks stay inline, each followed by exactly one space. The same goes for a closure in a
single-line argument list.

**Why:** A single-line list isn't a multi-line context, and 12.2 requires a parameter's attribute inline there. The
single space follows 12.2's rule for an attribute and its parameter.

---

### Comments Between an Attribute and Its Target

**Issue:** 12.2 says attributes go "immediately prior to" most structures, but only "prior to" a parameter.

**Ruleset:** A comment on its own line between an attribute and its target is reported, including before a parameter.
A comment at the end of the attribute's line, or between blocks, passes.

**Why:** The missing "immediately" is taken as incidental. Nothing suggests a parameter's attributes should be treated
differently.

---

## See Also

- [PERCS31](PERCS31.md): the sniffs that apply these calls.
- [Jumbo](Jumbo.md): PERCS31 plus extra rules.
- [PER Coding Style 3.1](https://www.php-fig.org/per/coding-style/)
