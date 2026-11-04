# Jumbo Coding Standard - Inherited Sniffs

This document lists the PHP_CodeSniffer sniffs the [Jumbo Coding Standard](../README.md) inherits from other coding standards, on top of
PERCS31.

PHP_CodeSniffer provides the PSR-12, Generic, and Squiz sniffs out of the box. Documentation is found in the [repository](https://github.com/PHPCSStandards/PHP_CodeSniffer)
and [wiki](https://github.com/PHPCSStandards/PHP_CodeSniffer/wiki).

---

## PERCS31

The Jumbo standard is built on [PERCS31](PERCS31.md), the package's standard for PER Coding Style 3.1, which is built on PSR-12.
Everything PERCS31 includes is included here, with six changes:

- `Jumbo.Classes.PropertyDeclaration` checks the hook order in every hook list, including ones with bodies
  (`orderHooksWithBodies` set to `true`).
- `Jumbo.Operators.OperatorPlacement` requires a wrapped line in a list item, such as a call argument, to be indented
  once past the item, not lined up with it (`requireListItemIndent` set to `true`).
- `SlevomatCodingStandard.TypeHints.DNFTypeHintFormat` reports `T|null`, which should be `?T`, as an error, not a
  warning.
- `Squiz.Arrays.ArrayDeclaration` also runs the checks PERCS31 leaves out. See [Squiz](#squiz).
- `Jumbo.Operators.OperatorSpacing` also reports more than one space around an operator, with `ignoreNewlines` on and
  `ignoreSpacingBeforeAssignments` off. See [Squiz](#squiz).
- `PSR12.Keywords.ShortFormTypeKeywords` is excluded, as `SlevomatCodingStandard.PHP.TypeCast` reports the same casts.
  See [Slevomat](#slevomat).

The sniffs below are house style, not PER rules.

---

## Generic

- **Generic.Formatting.SpaceAfterNot**: Disallows whitespace after `!`, including a line break (`spacing` set to 0).
- **Generic.CodeAnalysis.AssignmentInCondition**: Warns about assignments in conditions. See [Yoda-style Comparisons](../README.md#yoda-style-comparisons).
- **Jumbo.Files.LineLength**: PERCS31's line length check, with `lineLimit` set from the ruleset's `lineLength` entity.
  See [Line Length](LineLength.md).

---

## Squiz

- **Squiz.Arrays.ArrayDeclaration**: The checks PERCS31 leaves out: one space around `=>` and after a comma, no space
  inside an empty array `[]`, and no mix of keyed and unkeyed values. Its alignment checks and `SingleLineNotAllowed`
  stay excluded, so keys, values and arrows are not aligned, and a single-line array with several values is allowed.
  `Jumbo.Arrays.ArrayDeclaration` requires single-line where it fits.
- **Squiz.NamingConventions.ValidVariableName**: Enforces `$camelCase` for variables, parameters, and properties. Its
  checks for an underscore on properties are excluded, as one requires it on private properties. See
  [Variable and Property Names](VariableAndPropertyNames.md).
- **Squiz.Strings.DoubleQuoteUsage.NotRequired**: Ensures strings use single quotes unless double quotes are necessary.
- **Squiz.WhiteSpace.OperatorSpacing**: Requires exactly one space around operators, including before `=` and `=>`, so
  assignments and array arrows are not aligned. Used through [`Jumbo.Operators.OperatorSpacing`](PERCS31.md#jumbooperatorsoperatorspacing), which extends it to
  `&&`, `||`, `and`, `or`, `xor`, `.` and `|>`. PERCS31 excludes its codes for more than one space, and Jumbo turns them
  back on. `match` arms are covered by [`Jumbo.ControlStructures.MatchArrowSpacing`](PERCS31.md#jumbocontrolstructuresmatcharrowspacing).
- **Squiz.WhiteSpace.ObjectOperatorSpacing**: Disallows spaces around `->`, `?->` and `::`, e.g. `$a -> b()`. A line
  break before one, in a wrapped method chain, is allowed.

---

## Slevomat

Reference: [Slevomat coding standard repository and documentation](https://github.com/slevomat/coding-standard)

### Arrays

- **ArrayAccess**: Disallows whitespace between array access operators.
- **DisallowImplicitArrayCreation**: Disallows implicit array creation.
- **DisallowPartiallyKeyed**: Requires arrays to have keys for all or none of the values.
- **SingleLineArrayWhitespace**: Checks whitespace in single-line array declarations.

### Attributes

- **AttributesOrder**: Requires attributes to be ordered alphabetically.

### Classes

- **ClassMemberSpacing**: Enforces spacing between class members.
- **ConstantSpacing**: Enforces spacing around class constants.
- **DisallowStringExpressionPropertyFetch**: Disallows string expressions for property access.
- **EnumCaseSpacing**: Enforces spacing between enum cases.
- **MethodSpacing**: Enforces spacing between methods.
- **ModernClassNameReference**: Requires `::class` instead of `get_class()`, `get_parent_class()` and
  `get_called_class()`.
- **PropertySpacing**: Enforces spacing between properties.
- **RequireMultiLineMethodSignature**: Requires multi-line format for long method signatures.
- **RequireSelfReference**: Requires `self::` instead of `ClassName::` for self-references.
- **RequireSingleLineMethodSignature**: Requires single-line format for short method signatures. Used through
  [`Jumbo.Classes.RequireSingleLineMethodSignature`](CustomSniffs.md#jumboclassesrequiresinglelinemethodsignature), which skips a method with a hook on a promoted parameter.
- **TraitUseSpacing**: Disallows a blank line between trait `use` statements. Its checks before the first and after the
  last are excluded, as `PSR12.Traits.UseDeclaration`, in PERCS31, covers them.

### Commenting

- **AnnotationName**: Enforces correct annotation names.
- **DeprecatedAnnotationDeclaration**: Enforces the `@deprecated` annotation format.
- **EmptyComment**: Disallows empty comments.
- **RequireOneLinePropertyDocComment**: Requires one-line format for short property doc comments.
- **UselessInheritDocComment**: Disallows useless `@inheritDoc` comments.

### Control Structures

- **DisallowContinueWithoutIntegerOperandInSwitch**: Disallows `continue` without an operand in `switch`.
- **RequireMultiLineCondition**: Requires multi-line format for long conditions with boolean operators.
- **RequireMultiLineTernaryOperator**: Requires multi-line format for long ternary operators.
- **RequireNullCoalesceEqualOperator**: Requires `??=` when possible.
- **RequireNullCoalesceOperator**: Requires `??` when possible.
- **RequireNullSafeObjectOperator**: Requires `?->` when possible.
- **RequireSingleLineCondition**: Requires single-line format for short conditions.
- **RequireShortTernaryOperator**: Requires the short ternary `?:` when possible.
- **RequireTernaryOperator**: Requires ternary operator when possible.
- **UselessIfConditionWithReturn**: Disallows useless if conditions returning true or false.

### Exceptions

- **DeadCatch**: Disallows unreachable catch blocks, e.g. a catch after `catch (Throwable)`.
- **RequireNonCapturingCatch**: Requires catch without variable when exception is not used.

### Functions

- **FunctionLength**: Enforces maximum function length of 100 lines.
- **RequireArrowFunction**: Requires arrow functions for simple closures.
- **RequireMultiLineCall**: Requires multi-line format for long function calls.
- **StaticClosure**: Requires the `static` keyword for closures that don't use `$this`.
- **StrictCall**: Requires the strict argument in `in_array()`, `array_search()`, `array_keys()` and `base64_decode()`.
- **UselessParameterDefaultValue**: Disallows useless parameter default values.

### Namespaces

- **AlphabeticallySortedUses**: Requires use statements to be alphabetically sorted.
- **DisallowGroupUse**: Disallows group use declarations.
- **MultipleUsesPerLine**: Disallows multiple use statements on one line.
- **NamespaceDeclaration**: Enforces proper namespace declaration format.
- **ReferenceUsedNamesOnly**: Requires use statements for all referenced names.
- **RequireOneNamespaceInFile**: Requires exactly one namespace per file.
- **UseFromSameNamespace**: Disallows use statements from the same namespace.
- **UselessAlias**: Disallows useless use statement aliases.

### Operators

- **NegationOperatorSpacing**: Enforces the spacing after a unary `-`, including a line break. Its `spacesCount`
  defaults to 0, so no whitespace is allowed.
- **RequireCombinedAssignmentOperator**: Requires combined assignment operators (`+=`, `-=`, etc.).
- **RequireOnlyStandaloneIncrementAndDecrementOperators**: Requires `++` and `--` to be used standalone.
- **SpreadOperatorSpacing**: Enforces spacing around spread operator.

### PHP

- **DisallowDirectMagicInvokeCall**: Disallows direct `__invoke()` calls.
- **ReferenceSpacing**: Enforces spacing around reference operator.
- **ShortList**: Requires short list syntax `[]`.
- **TypeCast**: Requires `(int)`, `(bool)` and `(float)` instead of `(integer)`, `(boolean)`, `(double)` or `(real)`,
  and forbids `(binary)` and `(unset)`. It replaces PERCS31's `PSR12.Keywords.ShortFormTypeKeywords`, which only checks
  `(int)` and `(bool)`, so a long-form cast is reported once.
- **UselessSemicolon**: Disallows useless semicolons.

### Strings

- **DisallowVariableParsing**: Disallows variable parsing in strings.

### Type Hints

- **ClassConstantTypeHint**: Requires class constant type hints.
- **DeclareStrictTypes**: Requires `declare(strict_types=1)`. Used through [`Jumbo.TypeHints.DeclareStrictTypes`](CustomSniffs.md#jumbotypehintsdeclarestricttypes), which
  allows `<?php declare(strict_types=1) ?>` on the first line of a file with markup.
- **LongTypeHints**: Requires long type hints (`int` instead of `integer`).
- **NullableTypeForNullDefaultValue**: Requires nullable type for parameters with null default.
- **ParameterTypeHint**: Requires parameter type hints. Used through [`Jumbo.TypeHints.ParameterTypeHint`](CustomSniffs.md#jumbotypehintsparametertypehint), which skips a
  function with a hook on a promoted parameter.
- **ParameterTypeHintSpacing**: Enforces spacing around parameter type hints. Used through
  [`Jumbo.TypeHints.ParameterTypeHintSpacing`](CustomSniffs.md#jumbotypehintsparametertypehintspacing), which skips a function with a hook on a promoted parameter.
- **PropertyTypeHint**: Requires property type hints.
- **ReturnTypeHint**: Requires return type hints.
- **ReturnTypeHintSpacing**: Enforces spacing around return type hints.

### Variables

- **DisallowVariableVariable**: Disallows variable variables (`$$var`).
- **DuplicateAssignmentToVariable**: Disallows duplicate assignments to the same variable.
- **UselessVariable**: Disallows useless variables.
