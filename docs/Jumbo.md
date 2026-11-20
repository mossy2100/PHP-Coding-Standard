# Jumbo Coding Standard

A PHP_CodeSniffer standard for PHP 8.4 and later: [PERCS31](PERCS31.md), for PER Coding Style 3.1, plus extra rules reflecting my
preferences and habits developed coding PHP for over 20 years. These add consistent naming, modern PHP, and stricter
choices where PER allows more than one layout.

Everything PERCS31 checks, Jumbo checks too, apart from the changes listed under [PERCS31](#percs31). This page documents what Jumbo
adds. For the PER rules, see [PERCS31](PERCS31.md).

**Contents:**

- [Usage](#usage)
- [PERCS31](#percs31)
- [Inherited Sniffs](#inherited-sniffs)
- [Custom Sniffs](#custom-sniffs)
- [Not Checked](#not-checked)

---

## Usage

Jumbo is installed with the `mossy2100/phpcs-jumbo` package, and registered with PHP_CodeSniffer the same way. See
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

- `Generic.Files.LineLength` has `absoluteLineLimit`, which holds the soft limit, set from the ruleset's `lineLength`
  entity. See [Line Length](LineLength.md).
- `PERCS31.WhiteSpace.ArrowSpacing` requires exactly one space around a `match` arm's, an array's or a `yield`'s `=>`,
  where PERCS31 allows more (`allowSeveralSpaces` set to `false`). A `=>` that starts a wrapped line is still allowed.
  It replaces Squiz `ArrayDeclaration`'s arrow spacing checks.
- `PERCS31.WhiteSpace.ScopeIndent` checks the indent of a comment on its own line, as it does a statement's
  (`ignoreIndentationTokens` set to docblocks only). PERCS31 doesn't check a comment's indent, as PER has no rule for
  it. A docblock's indent isn't checked.
- `PERCS31.WhiteSpace.ConsistentIndent` also reports a comment indented further than its scope, when the line before it
  ends a statement (`checkComments` set to `true`). A comment at the top level isn't checked.
- No multi-line list starts or ends with a blank line. PERCS31 only reports one at the start, and at the end of an
  array, as PER forbids no other. Jumbo turns on the codes it excludes:
  `PERCS31.Functions.MultiLineFunctionDeclaration.BlankLineBeforeCloseBracket`, for a parameter list and a closure's
  `use` list, and `PERCS31.Functions.FunctionCallLayout.BlankLineBeforeCloseBracket`, for an argument list. See
  [Blank Lines in a List](PER-CS_3.1_Judgement_Calls.md#blank-lines-in-a-list). The same goes for a split control
  structure header, with `PERCS31.ControlStructures.ControlStructureSpacing.BlankLineBeforeCloseParenthesis`.
- No blank line in a trait's conflict block, with `PERCS31.Classes.TraitUseBlock.BlankLine`. PERCS31 excludes it,
  as only PER's example shows the block with none.
- No blank line between two trait `use` statements, with `PERCS31.Classes.TraitUseBlock.BlankLineBetweenUses`, and
  so exactly one after the last, with `PhpCollective.WhiteSpace.EmptyLines`. PERCS31 excludes the first, and allows
  any number after the last, as section 4.2 only says there MUST be a blank line there. See
  [Blank Lines Around `use`](PER-CS_3.1_Judgement_Calls.md#blank-lines-around-use).
- One constant per `const` statement at file scope too, and one space after its `const` keyword. PERCS31 only checks a
  class's constants, as section 4 is about classes (`checkGlobalConstants` set to `true` on
  `PERCS31.Classes.ConstantDeclaration`). A `define()` call can only declare one constant.
- No blank line after a `case` or `default` label, which PERCS31 allows. Jumbo turns on
  `PERCS31.ControlStructures.SwitchDeclaration`'s `BodyOnNextLineCASE` and `BodyOnNextLineDEFAULT` codes, which report
  that and code on the label's line, and turns off `StatementOnLabelLine`, which reports the second alone.
- `PERCS31.Formatting.EmptyBody` requires every empty body as `{}`, as an error (`strict` set to `true`). PERCS31 only
  warns for a class or a function, where PER says SHOULD, and doesn't check a control structure, where PER says
  nothing.
- `PERCS31.ControlStructures.ControlStructureSpacing` gives a `foreach` or `match` header that's split over lines the
  layout the other control structures have: the first expression on the line after `(`, and `) {` on a line of their
  own. A `foreach`'s `as` starts a line too, with its variables after it. PER has no rule for either, so PERCS31 leaves
  them alone (`splitForeachAndMatch` set to `true`). A header that fits on one line is still joined, by
  [`HeaderLayout`](Jumbo_Custom_Sniffs.md#jumbocontrolstructuresheaderlayout).
- `PERCS31.Arrays.MultiLineArrayLayout` lets an array that starts such a header start a line, where PERCS31 requires
  `foreach ([` and `match ([` (`splitForeachAndMatch` set to `true`).
- `PERCS31.Operators.OperatorPlacement` requires a line in a control structure's condition to be indented exactly once
  past the `(` line if it starts with `&&` or `||`, and exactly once past its operand's line if it starts with any other
  operator. PERCS31 allows at least once (`requireExactConditionIndent` set to `true`).
- `PERCS31.TypeHints.CompoundType` reports `T|null`, which should be `?T`, as an error, not a warning.
- [`Squiz.Arrays.ArrayDeclaration`](Jumbo_Inherited_Sniffs.md#squizarraysarraydeclaration) also runs the checks PERCS31 leaves out.
- `PERCS31.Operators.OperatorSpacing` also reports more than one space around an operator, with `ignoreNewlines` on and
  `ignoreSpacingBeforeAssignments` off. See [`Squiz.WhiteSpace.OperatorSpacing`](Jumbo_Inherited_Sniffs.md#squizwhitespaceoperatorspacing).
- `Squiz.Functions.FunctionDeclarationArgumentSpacing` requires exactly one space around a default value's `=`, using
  the `SpaceBeforeEquals` and `SpaceAfterEquals` codes PERCS31 excludes (`PERCS31.Operators.OperatorSpacing` has
  `checkDefaultValues` set to `false`).
- `PSR2.Files.ClosingTag` is excluded, as [`Zend.Files.ClosingTag`](Jumbo_Inherited_Sniffs.md#zendfilesclosingtag) reports the same closing tags, and more.
- `PERCS31.Operators.UnaryOperatorSpacing` also disallows whitespace after a prefix unary `+`, `-`, `!`, `~` and `@`,
  as in `- $a` and `! $a`. PERCS31 only checks the reference `&` with it, as PER leaves the others
  undefined. Each has its own property, such as `checkNotOperator`, and Jumbo sets them all to `true`.
- `PERCS31.NamingConventions.UpperCaseConstantName` has `checkGlobalConstants` set to `true`, so a global constant, from
  `const` or `define()`, and an alias of a constant, must be in UPPER_CASE. PERCS31 doesn't check this, as PER doesn't
  require it.
- `PERCS31.NamingConventions.UpperCaseConstantName` also has `requireUpperSnakeCase` set to `true`, so a constant in
  upper case must be in strict UPPER_SNAKE_CASE: words of capitals and digits, separated by single underscores. That
  covers class constants, global constants from `const` or `define()`, and an enum constant that isn't in PascalCase.
  So `__HIDDEN`, `TWO__WORDS` and `TRAILING_` are reported, as `ConstantNotUpperSnakeCase`. PERCS31 allows them.
- `PERCS31.NamingConventions.ValidConstantName` has `checkGlobalConstants` set to `true`, so a global constant from a
  `const` statement can't start with a single underscore either. PERCS31 only checks a class constant.
- `PERCS31.NamingConventions.UpperCaseConstantName`'s `EnumConstantNotPascalCase` warning has a severity of 4, not 5, so
  it's hidden by default, as Jumbo uses UPPER_CASE for every constant. Run `phpcs --warning-severity=4` to see
  it.

---

## Inherited Sniffs

Jumbo adds sniffs from PHP_CodeSniffer's Generic, Squiz and Zend standards, Slevomat, PHPCSExtra and php-collective, as
extra rules. See [Jumbo Inherited Sniffs](Jumbo_Inherited_Sniffs.md).

---

## Custom Sniffs

Custom sniffs fill the gaps in the inherited sniffs, or replace ones that misread modern PHP. See [Jumbo Custom Sniffs](Jumbo_Custom_Sniffs.md).

---

## Not Checked

Jumbo adds no gaps of its own. Everything listed under [Not Checked in PERCS31](PERCS31.md#not-checked) applies here too.
