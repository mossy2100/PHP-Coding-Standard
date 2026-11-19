# PERCS31 Coding Standard

A PHP_CodeSniffer standard for [PER Coding Style 3.1](https://www.php-fig.org/per/coding-style/), and nothing more. It's built on PSR-12, which PER Coding Style
extends, and so includes [PSR-1](https://www.php-fig.org/psr/psr-1/), which PER Coding Style requires.

No complete PHP_CodeSniffer standard for PER Coding Style exists at the time of development. PSR-12's sniffs get some
PER rules wrong, such as rejecting an empty body written as `{}`, and don't yet support PHP 8.4 and 8.5 features such as
property hooks and the pipe operator. This standard fixes both, using sniffs from PHP_CodeSniffer, Slevomat, PHPCSExtra
and php-collective where they fit, and custom sniffs where nothing did.

PLEASE NOTE: The PER-CS 3.1 specification is untidy at best. There are many ambiguities, inconsistencies, apparent
omissions, and outright errors. Where the spec is unclear, contradicts itself or says nothing,
[PER-CS 3.1 Judgement Calls](PER-CS_3.1_Judgement_Calls.md) gives the reading this coding standard takes, and why.

The [Jumbo standard](Jumbo.md) adds extra rules that:

1. Resolve the ambiguities and inconsistencies in the specification.
2. Add a little extra strictness to produce neat, consistent, readable, and maintainable code.

Use PERCS31 on its own to follow PER Coding Style without them.

**Contents:**

- [Usage](#usage)
- [PSR-12](#psr-12)
- [Inherited Sniffs](#inherited-sniffs)
- [Custom Sniffs](#custom-sniffs)
- [Not Checked](#not-checked)
- [Known Issues](#known-issues)

---

## Usage

PERCS31 is installed with the `mossy2100/phpcs-jumbo` package, and registered with PHP_CodeSniffer the same way. See
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

- **Generic.Formatting.DisallowMultipleStatements**, by [`PERCS31.Formatting.DisallowMultipleStatements`](PERCS31_Custom_Sniffs.md#percs31formattingdisallowmultiplestatements), which allows
  `{ get; set; }` on an abstract or interface property.
- **Generic.Files.LineEndings**, by [`PERCS31.Files.LineEndings`](PERCS31_Custom_Sniffs.md#percs31fileslineendings), which checks every line, not only the first.
- **Generic.PHP.LowerCaseKeyword**, by [`PERCS31.PHP.LowerCaseKeyword`](PERCS31_Custom_Sniffs.md#percs31phplowercasekeyword), which also checks a property hook's name.
  **Squiz.ControlStructures.LowercaseDeclaration** and **Squiz.Functions.LowercaseFunctionKeywords** are excluded, as it
  reports every keyword they do, so an upper-case `IF` or `FUNCTION` was reported twice.
- **Generic.PHP.LowerCaseType**, by [`PERCS31.PHP.LowerCaseType`](PERCS31_Custom_Sniffs.md#percs31phplowercasetype), which also checks a `set` hook's parameter type.
- **Generic.NamingConventions.UpperCaseConstantName**, from PSR-1, by [`PERCS31.NamingConventions.UpperCaseConstantName`](PERCS31_Custom_Sniffs.md#percs31namingconventionsuppercaseconstantname),
  which allows PascalCase for an enum constant.
- **PSR12.Classes.ClosingBrace**, by [`PERCS31.Formatting.AfterClosingBrace`](PERCS31_Custom_Sniffs.md#percs31formattingafterclosingbrace), which checks every closing brace.
- **PSR12.Classes.OpeningBraceSpace**, by [`PERCS31.Classes.ClassDeclaration`](PERCS31_Custom_Sniffs.md#percs31classesclassdeclaration), which doesn't report a blank line after an
  anonymous class's opening brace.
- **PSR12.ControlStructures.BooleanOperatorPlacement** is kept, with `allowOnly` set to `first`, so a wrapped `&&` or
  `||` in a condition starts the line (5.1, 6.4). PSR-12 only requires consistency.
- **PSR12.ControlStructures.ControlStructureSpacing**, by [`PERCS31.ControlStructures.ControlStructureSpacing`](PERCS31_Custom_Sniffs.md#percs31controlstructurescontrolstructurespacing), which
  drops its rules for a multi-line `foreach` or `match`.
- **PSR12.Files.DeclareStatement**, by [`PERCS31.Files.DeclareStatement`](PERCS31_Custom_Sniffs.md#percs31filesdeclarestatement), which accepts a declare statement with several
  directives, such as `declare(ticks=1,strict_types=1);`, or a string value, such as `declare(encoding='UTF-8');`.
- **PSR12.Files.FileHeader**, by [`PERCS31.Files.FileHeader`](PERCS31_Custom_Sniffs.md#percs31filesfileheader), which allows `<?php declare(strict_types=1) ?>` on the
  first line of a file with markup.
- **PSR12.Functions.NullableTypeDeclaration**, by [`PERCS31.Functions.NullableTypeDeclaration`](PERCS31_Custom_Sniffs.md#percs31functionsnullabletypedeclaration), which also checks a `set`
  hook's parameter.
- **PSR12.Operators.OperatorSpacing**, by [`PERCS31.Operators.OperatorSpacing`](PERCS31_Custom_Sniffs.md#percs31operatorsoperatorspacing), which also covers the pipe operator `|>`.
- **PSR12.Traits.UseDeclaration** is kept. Its `NoBlankLineAfterUse` code is replaced by
  [`PERCS31.Classes.TraitUseBlock`](PERCS31_Custom_Sniffs.md#percs31classestraituseblock), as it takes a comment at the end of the last `use` statement's line for something after it.
  A blank line between two `use` statements, and more than one after the last, are allowed, as section 4.2 forbids
  neither. Its `BlankLineAfterLastUse` code is excluded, as
  [`PERCS31.Classes.ClassDeclaration`](PERCS31_Custom_Sniffs.md#percs31classesclassdeclaration) reports the same blank line before the class's closing brace. Its `MultipleImport`
  code is replaced by `SlevomatCodingStandard.Classes.TraitUseDeclaration`, which also reports several traits in a `use`
  statement with a `{ }` block. Its `SpaceAfterOpeningBrace` and `SpaceBeforeClosingBrace` codes are replaced by
  [`PERCS31.Classes.TraitUseBlock`](PERCS31_Custom_Sniffs.md#percs31classestraituseblock), as they also report a blank line in the block. Its `ConflictSameLine` code is excluded, as
  [`PERCS31.Formatting.DisallowMultipleStatements`](PERCS31_Custom_Sniffs.md#percs31formattingdisallowmultiplestatements) reports the same two statements on a line.
- **PSR12.Classes.AnonClassDeclaration** is kept, but its three comma spacing codes are replaced by
  `Universal.WhiteSpace.CommaSpacing`, which reports the same. Its six codes for the layout of the argument list are
  excluded, as [`PERCS31.Functions.FunctionCallLayout`](PERCS31_Custom_Sniffs.md#percs31functionsfunctioncalllayout) reports the same problems.
- **Squiz.Functions.FunctionDeclaration** is excluded, as [`PERCS31.Functions.MultiLineFunctionDeclaration`](PERCS31_Custom_Sniffs.md#percs31functionsmultilinefunctiondeclaration) reports the
  space after `function` and before the opening parenthesis, with a fix.
- **PSR12.Properties.ConstantVisibility** is kept, but a constant with no visibility is an error, not a warning, as
  [Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says MUST.
- **PSR2.Classes.ClassDeclaration**, **Squiz.Functions.MultiLineFunctionDeclaration** and
  **Squiz.WhiteSpace.ScopeClosingBrace**, by versions that allow an empty body as `{}`. See
  [`PERCS31.Formatting.EmptyBody`](PERCS31_Custom_Sniffs.md#percs31formattingemptybody).
- **PSR2.Classes.PropertyDeclaration** and **Generic.WhiteSpace.ScopeIndent**, by [`PERCS31.Classes.PropertyDeclaration`](PERCS31_Custom_Sniffs.md#percs31classespropertydeclaration)
  and [`PERCS31.WhiteSpace.ScopeIndent`](PERCS31_Custom_Sniffs.md#percs31whitespacescopeindent), which support property hooks (PHP 8.4).
- **Squiz.ControlStructures.ControlSignature** is kept, but its `NewlineAfterOpenBrace` code is replaced by
  [`PERCS31.ControlStructures.BodyOnNextLine`](PERCS31_Custom_Sniffs.md#percs31controlstructuresbodyonnextline), which allows an empty body as `{}`.
- **PSR2.ControlStructures.SwitchDeclaration**, by [`PERCS31.ControlStructures.SwitchDeclaration`](PERCS31_Custom_Sniffs.md#percs31controlstructuresswitchdeclaration), which adds PER's
  `switch` rules.
- **Squiz.ControlStructures.ForEachLoopDeclaration**, by [`PERCS31.ControlStructures.ForEachSpacing`](PERCS31_Custom_Sniffs.md#percs31controlstructuresforeachspacing), which allows a line
  break before or after `as` and `=>`.
- **Generic.Functions.FunctionCallArgumentSpacing**, by `Universal.WhiteSpace.CommaSpacing`'s codes for a call, which
  also report a comma on the line after a heredoc's closing marker, and one in an array or a `match` inside a call's
  parentheses.
- **PSR2.Methods.FunctionCallSignature**, by [`PERCS31.Functions.FunctionCallLayout`](PERCS31_Custom_Sniffs.md#percs31functionsfunctioncalllayout), which also checks `clone()`, and
  skips a `set` hook's parameter list, which isn't a call.
- **Squiz.Classes.ValidClassName**, from PSR-1, by [`PERCS31.NamingConventions.ValidClassName`](PERCS31_Custom_Sniffs.md#percs31namingconventionsvalidclassname), which also reports a name
  with no lower-case letter.
- **PSR12.Keywords.ShortFormTypeKeywords**, by [`PERCS31.PHP.TypeCast`](PERCS31_Custom_Sniffs.md#percs31phptypecast), which also reports `(double)` and `(binary)`.

`Squiz.Functions.FunctionDeclarationArgumentSpacing`'s `SpaceBeforeEquals` and `SpaceAfterEquals` are excluded, as a
default value's `=` is a binary operator, which section 6.2 allows more than one space around.
[`PERCS31.Operators.OperatorSpacing`](PERCS31_Custom_Sniffs.md#percs31operatorsoperatorspacing) checks it instead.

Its `SpacingAfterVisibility`, `SpacingAfterSetVisibility` and `SpacingAfterReadonly` are excluded, as
`Squiz.WhiteSpace.ScopeKeywordSpacing` reports the same space after a promoted parameter's modifier.

Its `SpacingAfterReference` and `SpacingAfterVariadic` are excluded too. [`PERCS31.Operators.UnaryOperatorSpacing`](PERCS31_Custom_Sniffs.md#percs31operatorsunaryoperatorspacing)
reports a space after every reference `&`, and `Generic.WhiteSpace.SpreadOperatorSpacingAfter` after every spread `...`,
a parameter's included.

---

## Inherited Sniffs

PERCS31 also uses sniffs from PHP_CodeSniffer's Generic and Squiz standards, Slevomat and PHPCSExtra, each for a PER
Coding Style rule. See [PERCS31 Inherited Sniffs](PERCS31_Inherited_Sniffs.md).

---

## Custom Sniffs

Custom sniffs fill the gaps in the inherited sniffs, or replace ones that conflict with PER Coding Style. See
[PERCS31 Custom Sniffs](PERCS31_Custom_Sniffs.md).

---

## Not Checked

- **Whitespace after a unary `+`, `-`, `!`, `~` or `@` (6).** Section 6 leaves these operators undefined. Each has a
  property on [`PERCS31.Operators.UnaryOperatorSpacing`](PERCS31_Custom_Sniffs.md#percs31operatorsunaryoperatorspacing) to check it, as the [Jumbo standard](Jumbo.md) does.
- **A trailing comma in a split attribute block holding one attribute (2.6).** No sniff requires one. Splitting such a
  block already breaks section 12.1, as nothing may come between `#[` and the attribute's name.
- **Whether a fall-through comment is clear (5.2).** Any comment just before the next `case` or `default` passes, as the
  spec doesn't set any wording.
- **Avoiding abbreviations (2.7).** Whether a name could avoid an abbreviation can't be judged by a sniff.
- **Short open tags, with `short_open_tag` off (PSR-1, 2.1).** PHP then reads `<?` as text, so it's only reported as a
  possible short tag, with a warning. With the setting on, it's an error.
- **Autoloading (PSR-1, 3).** Whether a class's namespace matches its file's path depends on the project's autoload
  roots, which a standard doesn't know. One class per file, in a namespace, is checked.
- **Consistent property names (PSR-1, 4.2).** Which naming convention a scope uses can't be judged by a sniff.
- **A comment after an anonymous class's, closure's, `match`'s or control structure's closing brace (4).** Section 4
  says "any closing brace", but each of these is part of a larger expression or statement, so a comment is allowed
  there. A statement is reported after any closing brace.
- **The pipe operator `|>` on PHP 8.4.** It's only checked when PHP_CodeSniffer runs on PHP 8.5. On PHP 8.4, where `|>`
  is a parse error, PHP_CodeSniffer reads it as `|` and `>`, and reports them.

---

## Known Issues

- **An arrow function with a `throw` or `exit` in a `case` body.** PHP_CodeSniffer 4.0.4 never finishes on
  `$b = fn() => throw new Exception();` directly inside a `case` or `default`, under any standard, as it hangs before
  any sniff runs. Wrap the body in a closure, or move the arrow function out of the `switch`.
