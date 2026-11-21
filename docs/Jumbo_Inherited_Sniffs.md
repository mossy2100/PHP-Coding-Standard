# Jumbo Inherited Sniffs

The sniffs the [Jumbo](Jumbo.md) standard adds on top of [PERCS31](PERCS31.md), from PHP_CodeSniffer's Generic, Squiz and Zend standards, from
[Slevomat](https://github.com/slevomat/coding-standard), from [PHPCSExtra](https://github.com/PHPCSStandards/PHPCSExtra) (`Universal`) and from [php-collective/code-sniffer](https://github.com/php-collective/code-sniffer). They're extra rules, not PER rules. Each
section summarises what the sniff checks in Jumbo.

Where Jumbo changes a sniff PERCS31 already uses, see [PERCS31](Jumbo.md#percs31). The sniffs written for Jumbo are in [Jumbo Custom Sniffs](Jumbo_Custom_Sniffs.md).
Some Slevomat sniffs here are used through a Jumbo sniff that extends them, to fix a bug or skip a case, and their
sections link to it.

| Sniff                                                                                    | Checks                                                         | Fixable |
|------------------------------------------------------------------------------------------|----------------------------------------------------------------|---------|
| `Generic.CodeAnalysis.AssignmentInCondition`                                             | A warning for an assignment in a condition                     | No      |
| `PhpCollective.WhiteSpace.EmptyLines`                                                    | No more than one blank line in a row                           | Yes     |
| `SlevomatCodingStandard.Arrays.ArrayAccess`                                              | No white space around an array access's brackets               | Partly  |
| `SlevomatCodingStandard.Arrays.DisallowImplicitArrayCreation`                            | No array created by assigning to an undefined variable         | No      |
| `SlevomatCodingStandard.Arrays.DisallowPartiallyKeyed`                                   | Keys on all of an array's values, or none                      | No      |
| `SlevomatCodingStandard.Attributes.AttributesOrder`                                      | Attributes in alphabetical order                               | Yes     |
| `SlevomatCodingStandard.Classes.ClassMemberSpacing`                                      | The spacing between class members                              | Partly  |
| `SlevomatCodingStandard.Classes.ConstantSpacing`                                         | The blank lines after a constant, through a Jumbo sniff        | Yes     |
| `SlevomatCodingStandard.Classes.DisallowStringExpressionPropertyFetch`                   | No `$a->{'b'}` where `$a->b` works                             | Yes     |
| `SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces`                             | No blank lines inside a class's braces, through a Jumbo sniff  | Yes     |
| `SlevomatCodingStandard.Classes.EnumCaseSpacing`                                         | The blank lines after an enum case, through a Jumbo sniff      | Yes     |
| `SlevomatCodingStandard.Classes.MethodSpacing`                                           | The spacing between methods                                    | Yes     |
| `SlevomatCodingStandard.Classes.ModernClassNameReference`                                | `::class`, not `get_class()` and similar                       | Yes     |
| `SlevomatCodingStandard.Classes.PropertySpacing`                                         | The blank lines after a property, through a Jumbo sniff        | Yes     |
| `SlevomatCodingStandard.Classes.RequireMultiLineMethodSignature`                         | A long method signature split over lines                       | Yes     |
| `SlevomatCodingStandard.Classes.RequireSelfReference`                                    | `self::`, not the class's own name                             | Yes     |
| `SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature`                        | A short method signature on one line, through a Jumbo sniff    | Yes     |
| `SlevomatCodingStandard.Commenting.AnnotationName`                                       | Annotation names in the correct case                           | Yes     |
| `SlevomatCodingStandard.Commenting.DeprecatedAnnotationDeclaration`                      | The `@deprecated` annotation format                            | No      |
| `SlevomatCodingStandard.Commenting.EmptyComment`                                         | No empty comments                                              | Yes     |
| `SlevomatCodingStandard.Commenting.RequireOneLinePropertyDocComment`                     | A short property doc comment on one line                       | Yes     |
| `SlevomatCodingStandard.Commenting.UselessInheritDocComment`                             | No useless `@inheritDoc` comments                              | Yes     |
| `SlevomatCodingStandard.ControlStructures.DisallowContinueWithoutIntegerOperandInSwitch` | No `continue` without an operand in a `switch`                 | Yes     |
| `SlevomatCodingStandard.ControlStructures.LanguageConstructWithParentheses`              | No `echo(...)`, through a Jumbo sniff                          | Yes     |
| `SlevomatCodingStandard.ControlStructures.RequireMultiLineTernaryOperator`               | A long ternary split over lines                                | Yes     |
| `SlevomatCodingStandard.ControlStructures.RequireNullCoalesceEqualOperator`              | `??=` where possible                                           | Yes     |
| `SlevomatCodingStandard.ControlStructures.RequireNullCoalesceOperator`                   | `??` where possible                                            | Yes     |
| `SlevomatCodingStandard.ControlStructures.RequireNullSafeObjectOperator`                 | `?->` where possible                                           | Yes     |
| `SlevomatCodingStandard.ControlStructures.RequireShortTernaryOperator`                   | `?:` where possible, through a Jumbo sniff                     | Yes     |
| `SlevomatCodingStandard.ControlStructures.RequireTernaryOperator`                        | A ternary where possible                                       | Partly  |
| `SlevomatCodingStandard.ControlStructures.UselessIfConditionWithReturn`                  | No `if` that only returns `true` or `false`                    | Partly  |
| `SlevomatCodingStandard.Exceptions.DeadCatch`                                            | No unreachable `catch` blocks                                  | No      |
| `SlevomatCodingStandard.Exceptions.RequireNonCapturingCatch`                             | No variable on a `catch` that doesn't use it                   | Yes     |
| `SlevomatCodingStandard.Functions.FunctionLength`                                        | A function of no more than 100 lines                           | No      |
| `SlevomatCodingStandard.Functions.RequireMultiLineCall`                                  | A long call split over lines                                   | Yes     |
| `SlevomatCodingStandard.Functions.RequireSingleLineCall`                                 | A short call on one line, through a Jumbo sniff                | Yes     |
| `SlevomatCodingStandard.Functions.StaticClosure`                                         | `static` on a closure that doesn't use `$this`                 | Yes     |
| `SlevomatCodingStandard.Functions.StrictCall`                                            | The strict argument in `in_array()` and similar                | No      |
| `SlevomatCodingStandard.Functions.UselessParameterDefaultValue`                          | No useless parameter default values                            | Yes     |
| `SlevomatCodingStandard.Namespaces.AlphabeticallySortedUses`                             | `use` statements in alphabetical order                         | Partly  |
| `SlevomatCodingStandard.Namespaces.DisallowGroupUse`                                     | No compound namespace declarations                             | No      |
| `SlevomatCodingStandard.Namespaces.MultipleUsesPerLine`                                  | One `use` statement per line                                   | No      |
| `SlevomatCodingStandard.Namespaces.NamespaceDeclaration`                                 | A namespace declaration's format                               | Partly  |
| `SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly`                               | A `use` statement for every referenced name                    | Partly  |
| `SlevomatCodingStandard.Namespaces.RequireOneNamespaceInFile`                            | One namespace per file                                         | No      |
| `SlevomatCodingStandard.Namespaces.UnusedUses`                                           | No unused `use` statements, through a Jumbo sniff              | Yes     |
| `SlevomatCodingStandard.Namespaces.UseFromSameNamespace`                                 | No `use` statement from the same namespace                     | Yes     |
| `SlevomatCodingStandard.Namespaces.UselessAlias`                                         | No useless `use` aliases                                       | Yes     |
| `SlevomatCodingStandard.Operators.RequireCombinedAssignmentOperator`                     | `+=`, `-=` and the like, where possible                        | Partly  |
| `SlevomatCodingStandard.Operators.RequireOnlyStandaloneIncrementAndDecrementOperators`   | `++` and `--` only as standalone statements                    | No      |
| `SlevomatCodingStandard.PHP.DisallowDirectMagicInvokeCall`                               | No direct `__invoke()` calls                                   | Yes     |
| `SlevomatCodingStandard.PHP.ShortList`                                                   | `[]`, not `list()`                                             | Yes     |
| `SlevomatCodingStandard.PHP.UselessSemicolon`                                            | No useless semicolons                                          | Yes     |
| `SlevomatCodingStandard.Strings.DisallowVariableParsing`                                 | No variables parsed in strings                                 | No      |
| `SlevomatCodingStandard.TypeHints.ClassConstantTypeHint`                                 | Type hints on class constants                                  | Partly  |
| `SlevomatCodingStandard.TypeHints.DeclareStrictTypes`                                    | `declare(strict_types=1)`, through a Jumbo sniff               | Yes     |
| `SlevomatCodingStandard.TypeHints.LongTypeHints`                                         | `int` and `bool`, not `integer` and `boolean`                  | Yes     |
| `SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue`                       | A nullable type on a parameter that defaults to `null`         | Yes     |
| `SlevomatCodingStandard.TypeHints.ParameterTypeHint`                                     | Parameter type hints, through a Jumbo sniff                    | Partly  |
| `SlevomatCodingStandard.TypeHints.ParameterTypeHintSpacing`                              | The spacing around parameter type hints, through a Jumbo sniff | Yes     |
| `SlevomatCodingStandard.TypeHints.PropertyTypeHint`                                      | Property type hints                                            | Partly  |
| `SlevomatCodingStandard.TypeHints.ReturnTypeHint`                                        | Return type hints                                              | Partly  |
| `SlevomatCodingStandard.TypeHints.ReturnTypeHintSpacing`                                 | The spacing around return type hints                           | Yes     |
| `SlevomatCodingStandard.Variables.DisallowVariableVariable`                              | No variable variables, e.g. `$$var`                            | No      |
| `SlevomatCodingStandard.Variables.DuplicateAssignmentToVariable`                         | No duplicate assignments to a variable                         | No      |
| `Squiz.Arrays.ArrayDeclaration`                                                          | No mix of keyed and unkeyed values in an array                 | No      |
| `Squiz.Commenting.InlineComment`                                                         | One space after `//`                                           | Yes     |
| `Squiz.NamingConventions.ValidVariableName`                                              | `$camelCase` variable, parameter and property names            | No      |
| `Squiz.Strings.DoubleQuoteUsage.NotRequired`                                             | Single quotes, unless double quotes are needed                 | Yes     |
| `Squiz.WhiteSpace.FunctionOpeningBraceSpace.SpacingAfter`                                | No blank line after a function's opening brace                 | Yes     |
| `Squiz.WhiteSpace.OperatorSpacing`                                                       | Exactly one space around operators                             | Yes     |
| `Universal.Constants.UppercaseMagicConstants`                                            | Magic constants in upper case, e.g. `__DIR__`                  | Yes     |
| `Zend.Files.ClosingTag`                                                                  | No closing tag at the end of a file                            | Yes     |

---

## Generic.CodeAnalysis.AssignmentInCondition

Warns about assignments in conditions. See [Yoda-style Comparisons](../README.md#yoda-style-comparisons).

**Auto-fixable:** no.

---

## PhpCollective.WhiteSpace.EmptyLines

Disallows more than one blank line in a row, anywhere in PHP code. A blank line inside a heredoc, a nowdoc or a block
comment is part of that string or comment, so it's left alone, as is HTML outside the PHP tags. PER Coding Style 3.1,
section 2.3, allows blank lines wherever they aren't forbidden, so PERCS31 allows any number.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Arrays.ArrayAccess

Disallows whitespace between array access operators.

**Auto-fixable:** partly. Some of its codes can't be fixed.

---

## SlevomatCodingStandard.Arrays.DisallowImplicitArrayCreation

Disallows implicit array creation.

**Auto-fixable:** no.

---

## SlevomatCodingStandard.Arrays.DisallowPartiallyKeyed

Requires arrays to have keys for all or none of the values.

**Auto-fixable:** no.

---

## SlevomatCodingStandard.Attributes.AttributesOrder

Requires attributes to be ordered alphabetically.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.ClassMemberSpacing

Enforces spacing between class members.

**Auto-fixable:** partly. Some of its codes can't be fixed.

---

## SlevomatCodingStandard.Classes.ConstantSpacing

Enforces the blank lines after a constant. Used through [`Jumbo.Classes.ConstantSpacing`](Jumbo_Custom_Sniffs.md#jumboclassesconstantspacing-enumcasespacing-and-propertyspacing), which skips a constant on the
file's last line, as the parent crashes on one.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.DisallowStringExpressionPropertyFetch

Disallows string expressions for property access.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces

Enforces the blank lines inside a class's braces. Used through [`Jumbo.Classes.EmptyLinesAroundClassBraces`](Jumbo_Custom_Sniffs.md#jumboclassesemptylinesaroundclassbraces), which allows
an empty body as `{}`.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.EnumCaseSpacing

Enforces the blank lines after an enum case. Used through [`Jumbo.Classes.EnumCaseSpacing`](Jumbo_Custom_Sniffs.md#jumboclassesconstantspacing-enumcasespacing-and-propertyspacing), which skips a case on the
file's last line, as the parent crashes on one.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.MethodSpacing

Enforces spacing between methods.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.ModernClassNameReference

Requires `::class` instead of `get_class()`, `get_parent_class()` and `get_called_class()`.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.PropertySpacing

Enforces the blank lines after a property. Used through [`Jumbo.Classes.PropertySpacing`](Jumbo_Custom_Sniffs.md#jumboclassesconstantspacing-enumcasespacing-and-propertyspacing), which skips a property on the
file's last line, as the parent crashes on one.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.RequireMultiLineMethodSignature

Requires multi-line format for long method signatures.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.RequireSelfReference

Requires `self::` instead of `ClassName::` for self-references.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature

Requires single-line format for short method signatures. Used through [`Jumbo.Classes.RequireSingleLineMethodSignature`](Jumbo_Custom_Sniffs.md#jumboclassesrequiresinglelinemethodsignature),
which skips a method with a hook on a promoted parameter.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Commenting.AnnotationName

Enforces correct annotation names.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Commenting.DeprecatedAnnotationDeclaration

Enforces the `@deprecated` annotation format.

**Auto-fixable:** no.

---

## SlevomatCodingStandard.Commenting.EmptyComment

Disallows empty comments.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Commenting.RequireOneLinePropertyDocComment

Requires one-line format for short property doc comments.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Commenting.UselessInheritDocComment

Disallows useless `@inheritDoc` comments.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.ControlStructures.DisallowContinueWithoutIntegerOperandInSwitch

Disallows `continue` without an operand in `switch`.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.ControlStructures.LanguageConstructWithParentheses

Disallows parentheses around a language construct's argument. Used through
[`Jumbo.ControlStructures.LanguageConstructWithParentheses`](Jumbo_Custom_Sniffs.md#jumbocontrolstructureslanguageconstructwithparentheses), which skips `exit` and `die`.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.ControlStructures.RequireMultiLineTernaryOperator

Requires multi-line format for long ternary operators.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.ControlStructures.RequireNullCoalesceEqualOperator

Requires `??=` when possible.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.ControlStructures.RequireNullCoalesceOperator

Requires `??` when possible.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.ControlStructures.RequireNullSafeObjectOperator

Requires `?->` when possible.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.ControlStructures.RequireShortTernaryOperator

Requires the short ternary `?:` when possible. Used through [`Jumbo.ControlStructures.RequireShortTernaryOperator`](Jumbo_Custom_Sniffs.md#jumbocontrolstructuresrequireshortternaryoperator), which
skips a nullable type in a `set` hook's parameter.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.ControlStructures.RequireTernaryOperator

Requires ternary operator when possible.

**Auto-fixable:** partly. Some of its codes can't be fixed.

---

## SlevomatCodingStandard.ControlStructures.UselessIfConditionWithReturn

Disallows useless if conditions returning true or false.

**Auto-fixable:** partly. Some of its codes can't be fixed.

---

## SlevomatCodingStandard.Exceptions.DeadCatch

Disallows unreachable catch blocks, e.g. a catch after `catch (Throwable)`.

**Auto-fixable:** no.

---

## SlevomatCodingStandard.Exceptions.RequireNonCapturingCatch

Requires catch without variable when exception is not used.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Functions.FunctionLength

Enforces maximum function length of 100 lines.

**Auto-fixable:** no.

---

## SlevomatCodingStandard.Functions.RequireMultiLineCall

Requires multi-line format for long function calls.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Functions.RequireSingleLineCall

Requires a call that fits on one line to be on one line. Used through [`Jumbo.Functions.RequireSingleLineCall`](Jumbo_Custom_Sniffs.md#jumbofunctionsrequiresinglelinecall), which
skips a call with an anonymous class or `match` argument.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Functions.StaticClosure

Requires the `static` keyword for closures that don't use `$this`.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Functions.StrictCall

Requires the strict argument in `in_array()`, `array_search()`, `array_keys()` and `base64_decode()`.

**Auto-fixable:** no.

---

## SlevomatCodingStandard.Functions.UselessParameterDefaultValue

Disallows useless parameter default values.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Namespaces.AlphabeticallySortedUses

Requires use statements to be alphabetically sorted.

**Auto-fixable:** partly. Some of its codes can't be fixed.

---

## SlevomatCodingStandard.Namespaces.DisallowGroupUse

Disallows compound namespaces.

**Auto-fixable:** no.

---

## SlevomatCodingStandard.Namespaces.MultipleUsesPerLine

Disallows multiple use statements on one line.

**Auto-fixable:** no.

---

## SlevomatCodingStandard.Namespaces.NamespaceDeclaration

Enforces proper namespace declaration format.

**Auto-fixable:** partly. Some of its codes can't be fixed.

---

## SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly

Requires use statements for all referenced names.

**Auto-fixable:** partly. Some of its codes can't be fixed.

---

## SlevomatCodingStandard.Namespaces.RequireOneNamespaceInFile

Requires exactly one namespace per file.

**Auto-fixable:** no.

---

## SlevomatCodingStandard.Namespaces.UnusedUses

Disallows unused use statements. Used through [`Jumbo.Namespaces.UnusedUses`](Jumbo_Custom_Sniffs.md#jumbonamespacesunuseduses), which skips a file with an upper-case
`function` or `const` keyword in a use statement.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Namespaces.UseFromSameNamespace

Disallows use statements from the same namespace.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Namespaces.UselessAlias

Disallows useless use statement aliases.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Operators.RequireCombinedAssignmentOperator

Requires combined assignment operators (`+=`, `-=`, etc.).

**Auto-fixable:** partly. Some of its codes can't be fixed.

---

## SlevomatCodingStandard.Operators.RequireOnlyStandaloneIncrementAndDecrementOperators

Requires `++` and `--` to be used standalone.

**Auto-fixable:** no.

---

## SlevomatCodingStandard.PHP.DisallowDirectMagicInvokeCall

Disallows direct `__invoke()` calls.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.PHP.ShortList

Requires short list syntax `[]`.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.PHP.UselessSemicolon

Disallows useless semicolons.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Strings.DisallowVariableParsing

Disallows variable parsing in strings.

**Auto-fixable:** no.

---

## SlevomatCodingStandard.TypeHints.ClassConstantTypeHint

Requires class constant type hints.

**Auto-fixable:** partly. Some of its codes can't be fixed.

---

## SlevomatCodingStandard.TypeHints.DeclareStrictTypes

Requires `declare(strict_types=1)`. Used through [`Jumbo.TypeHints.DeclareStrictTypes`](Jumbo_Custom_Sniffs.md#jumbotypehintsdeclarestricttypes), which allows
`<?php declare(strict_types=1) ?>` on the first line of a file with markup.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.TypeHints.LongTypeHints

Requires long type hints (`int` instead of `integer`).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue

Requires nullable type for parameters with null default.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.TypeHints.ParameterTypeHint

Requires parameter type hints. Used through [`Jumbo.TypeHints.ParameterTypeHint`](Jumbo_Custom_Sniffs.md#jumbotypehintsparametertypehint), which skips a function with a hook on a
promoted parameter.

**Auto-fixable:** partly. Some of its codes can't be fixed.

---

## SlevomatCodingStandard.TypeHints.ParameterTypeHintSpacing

Enforces spacing around parameter type hints. Used through [`Jumbo.TypeHints.ParameterTypeHintSpacing`](Jumbo_Custom_Sniffs.md#jumbotypehintsparametertypehintspacing), which skips a
function with a hook on a promoted parameter.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.TypeHints.PropertyTypeHint

Requires property type hints.

**Auto-fixable:** partly. Some of its codes can't be fixed.

---

## SlevomatCodingStandard.TypeHints.ReturnTypeHint

Requires return type hints.

**Auto-fixable:** partly. Some of its codes can't be fixed.

---

## SlevomatCodingStandard.TypeHints.ReturnTypeHintSpacing

Enforces spacing around return type hints.

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Variables.DisallowVariableVariable

Disallows variable variables (`$$var`).

**Auto-fixable:** no.

---

## SlevomatCodingStandard.Variables.DuplicateAssignmentToVariable

Disallows duplicate assignments to the same variable.

**Auto-fixable:** no.

---

## Squiz.Arrays.ArrayDeclaration

The check PERCS31 leaves out: no mix of keyed and unkeyed values. Which line a comma goes on is left to
[`Jumbo.Arrays.ElementComma`](Jumbo_Custom_Sniffs.md#jumboarrayselementcomma). The spacing around `=>` is left to [`PERCS31.WhiteSpace.ArrowSpacing`](PERCS31_Custom_Sniffs.md#percs31whitespacearrowspacing), as Squiz only checks
it in a single-line array. Its alignment checks and `SingleLineNotAllowed` stay excluded, so keys, values and arrows are
not aligned, and a single-line array with several values is allowed. `Jumbo.Arrays.ArrayDeclaration` requires
single-line where it fits.

**Auto-fixable:** no.

---

## Squiz.Commenting.InlineComment

Requires one space after `//`, as in `// Text`. Only its `NoSpaceBefore`, `SpacingBefore` and `TabBefore` codes are
used, so `//Text`, `// Text` and `///` are reported. A `#` comment isn't checked.

**Auto-fixable:** yes.

---

## Squiz.NamingConventions.ValidVariableName

Enforces `$camelCase` for variables, parameters, and properties. Its checks for an underscore on properties are
excluded, as one requires it on private properties. See [Variable and Property Names](VariableAndPropertyNames.md).

**Auto-fixable:** no.

---

## Squiz.Strings.DoubleQuoteUsage.NotRequired

Ensures strings use single quotes unless double quotes are necessary.

**Auto-fixable:** yes.

---

## Squiz.WhiteSpace.FunctionOpeningBraceSpace.SpacingAfter

No blank line straight after the opening brace of a function, method or closure. PER only rules one out before the
closing brace, so PERCS31 allows it. See [A Blank Line at the Start of a Body](PER-CS_3.1_Judgement_Calls.md#a-blank-line-at-the-start-of-a-body).

**Auto-fixable:** yes.

---

## Squiz.WhiteSpace.OperatorSpacing

Requires exactly one space around operators, including before `=`, so assignments are not aligned. Used through
[`PERCS31.Operators.OperatorSpacing`](PERCS31_Custom_Sniffs.md#percs31operatorsoperatorspacing), which extends it to `&&`, `||`, `and`, `or`, `xor`, `.` and `|>`. PERCS31 excludes
its codes for more than one space, and Jumbo turns them back on. `=>` is covered by [`PERCS31.WhiteSpace.ArrowSpacing`](PERCS31_Custom_Sniffs.md#percs31whitespacearrowspacing).

**Auto-fixable:** yes.

---

## Universal.Constants.UppercaseMagicConstants

Requires a magic constant in upper case, as in `__DIR__`, not `__dir__`. PHP ignores their case. Section 2.5's rule that
keywords are in lower case isn't read as covering them, so PERCS31 doesn't check their case.

**Auto-fixable:** yes.

---

## Zend.Files.ClosingTag

Disallows a closing tag at the end of a file, including one that mixes PHP and HTML. PERCS31's `PSR2.Files.ClosingTag`
only checks PHP-only files. A file that ends in HTML has no closing tag at its end, so it passes.

**Auto-fixable:** yes.
