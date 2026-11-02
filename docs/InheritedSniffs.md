# Mossy2100 Coding Standard - Inherited Sniffs

This document lists the PHP_CodeSniffer sniffs inherited from other coding standards by the [Mossy2100 Coding Standard](../README.md).

PHP_CodeSniffer provides the PSR-12, Generic, and Squiz sniffs out of the box. Documentation is found in the [repository](https://github.com/PHPCSStandards/PHP_CodeSniffer)
and [wiki](https://github.com/PHPCSStandards/PHP_CodeSniffer/wiki).

---

## PSR-12

Reference: [PSR-12 standard](https://www.php-fig.org/psr/psr-12/)

The complete PSR-12 coding standard is included, with the following exceptions:

- **PSR2.Classes.PropertyDeclaration** is excluded, replaced by [`Mossy2100.Classes.PropertyDeclaration`](CustomSniffs.md#mossy2100classespropertydeclaration), which supports
  property hooks (PHP 8.4).
- **Generic.WhiteSpace.ScopeIndent** is excluded, replaced by [`Mossy2100.WhiteSpace.ScopeIndent`](CustomSniffs.md#mossy2100whitespacescopeindent), which also supports
  property hooks.
- **Generic.Formatting.DisallowMultipleStatements** is excluded, replaced by
  [`Mossy2100.Formatting.DisallowMultipleStatements`](CustomSniffs.md#mossy2100formattingdisallowmultiplestatements), which allows `{ get; set; }` on an abstract or interface property.
- **PSR2.Classes.ClassDeclaration**, **Squiz.Functions.MultiLineFunctionDeclaration** and
  **Squiz.WhiteSpace.ScopeClosingBrace** are excluded, replaced by Mossy2100 versions that allow an empty body as `{}`
  (see [Custom Sniffs](CustomSniffs.md)).

---

## Generic

- **Generic.Arrays.ArrayIndent**: Indents multi-line array elements one level, with the closing bracket on its own line
  at the indent of the statement.
- **Generic.Arrays.DisallowLongArraySyntax**: Requires short array syntax `[]` instead of `array()`.
- **Generic.Formatting.SpaceAfterCast**: Enforces a space after type casts (e.g. `(int) $value`).
- **Generic.CodeAnalysis.AssignmentInCondition**: Warns about assignments in conditions. See [Yoda-style Comparisons](../README.md#yoda-style-comparisons).
- **Generic.Files.LineLength**: Warns about a line over 120 characters, with no hard limit, as PER Coding Style 3.0
  requires. See [Line Length](LineLength.md).

---

## Squiz

- **Squiz.Arrays.ArrayDeclaration**: Requires one element per line in a multi-line array, and a trailing comma only on
  multi-line arrays. Its alignment checks are excluded, so keys, values and arrows are not aligned. Its
  `SingleLineNotAllowed` check is also excluded, since it would force every array with more than one value, list or
  associative, onto several lines. `Mossy2100.Arrays.ArrayDeclaration` requires single-line where it fits.
- **Squiz.NamingConventions.ValidVariableName**: Enforces `$camelCase` for variables, parameters, and properties. Its
  checks for an underscore on properties are excluded, as one requires it on private properties. See
  [Variable and Property Names](VariableAndPropertyNames.md).
- **Squiz.Strings.DoubleQuoteUsage.NotRequired**: Ensures strings use single quotes unless double quotes are necessary.
- **Squiz.WhiteSpace.OperatorSpacing**: Requires a single space around operators, including before `=` and `=>`, so
  assignments and array arrows are not aligned. `match` arms are covered by
  [`Mossy2100.ControlStructures.MatchArrowSpacing`](CustomSniffs.md#mossy2100controlstructuresmatcharrowspacing).
- **Squiz.WhiteSpace.ObjectOperatorSpacing**: Disallows spaces around `->`, `?->` and `::`, e.g. `$a -> b()`. A line
  break before one, in a wrapped method chain, is allowed.

---

## Slevomat

Reference: [Slevomat coding standard repository and documentation](https://github.com/slevomat/coding-standard)

### Arrays

- **ArrayAccess**: Disallows whitespace between array access operators.
- **DisallowImplicitArrayCreation**: Disallows implicit array creation.
- **DisallowPartiallyKeyed**: Requires arrays to have keys for all or none of the values.
- **MultiLineArrayEndBracketPlacement**: Requires the closing bracket of a multi-line array on its own line.
- **SingleLineArrayWhitespace**: Checks whitespace in single-line array declarations.

### Attributes

- **AttributeAndTargetSpacing**: Disallows blank lines between an attribute and its target. An attribute on the same
  line as its target is allowed here, and checked by [`Mossy2100.Attributes.AttributePlacement`](CustomSniffs.md#mossy2100attributesattributeplacement).
- **AttributesOrder**: Requires attributes to be ordered alphabetically.
- **DisallowAttributesJoining**: Disallows joining multiple attributes with commas.
- **DisallowMultipleAttributesPerLine**: Requires each attribute on its own line.
- **RequireAttributeAfterDocComment**: Requires attributes to appear after doc comments.

### Classes

- **BackedEnumTypeSpacing**: Enforces spacing around backed enum types.
- **ClassMemberSpacing**: Enforces spacing between class members.
- **ConstantSpacing**: Enforces spacing around class constants.
- **DisallowMultiPropertyDefinition**: Disallows defining multiple properties in one statement.
- **DisallowStringExpressionPropertyFetch**: Disallows string expressions for property access.
- **EnumCaseSpacing**: Enforces spacing between enum cases.
- **MethodSpacing**: Enforces spacing between methods.
- **ModernClassNameReference**: Requires `::class` instead of `get_class()`, `get_parent_class()` and
  `get_called_class()`.
- **PropertyDeclaration**: Enforces modifier order and the spacing around the type, including on promoted properties.
- **PropertySpacing**: Enforces spacing between properties.
- **RequireMultiLineMethodSignature**: Requires multi-line format for long method signatures.
- **RequireSelfReference**: Requires `self::` instead of `ClassName::` for self-references.
- **RequireSingleLineMethodSignature**: Requires single-line format for short method signatures.
- **TraitUseDeclaration**: Enforces proper trait use declaration format.
- **TraitUseSpacing**: Enforces spacing around trait use statements.

### Commenting

- **AnnotationName**: Enforces correct annotation names.
- **DeprecatedAnnotationDeclaration**: Enforces the `@deprecated` annotation format.
- **EmptyComment**: Disallows empty comments.
- **RequireOneLinePropertyDocComment**: Requires one-line format for short property doc comments.
- **UselessInheritDocComment**: Disallows useless `@inheritDoc` comments.

### Control Structures

- **DisallowContinueWithoutIntegerOperandInSwitch**: Disallows `continue` without an operand in `switch`.
- **DisallowTrailingMultiLineTernaryOperator**: Requires leading operators in multi-line ternary expressions.
- **LanguageConstructWithParentheses**: Disallows parentheses around language construct arguments, e.g. `echo('x')`.
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

- **ArrowFunctionDeclaration**: Enforces arrow function spacing, with no space after `fn`, e.g. `fn($x) => $x`.
- **DisallowTrailingCommaInCall**: Disallows a trailing comma in a single-line function call.
- **DisallowTrailingCommaInClosureUse**: Disallows a trailing comma in a single-line closure `use` list.
- **DisallowTrailingCommaInDeclaration**: Disallows a trailing comma in a single-line function declaration.
- **FunctionLength**: Enforces maximum function length of 100 lines.
- **NamedArgumentSpacing**: Enforces spacing around named arguments.
- **RequireArrowFunction**: Requires arrow functions for simple closures.
- **RequireMultiLineCall**: Requires multi-line format for long function calls.
- **RequireSingleLineCall**: Requires single-line format for short function calls.
- **RequireTrailingCommaInCall**: Requires a trailing comma in a multi-line function call.
- **RequireTrailingCommaInClosureUse**: Requires a trailing comma in a multi-line closure `use` list.
- **RequireTrailingCommaInDeclaration**: Requires a trailing comma in a multi-line function declaration.
- **StaticClosure**: Requires the `static` keyword for closures that don't use `$this`.
- **StrictCall**: Requires the strict argument in `in_array()`, `array_search()`, `array_keys()` and `base64_decode()`.
- **UselessParameterDefaultValue**: Disallows useless parameter default values.

### Namespaces

- **AlphabeticallySortedUses**: Requires use statements to be alphabetically sorted.
- **DisallowGroupUse**: Disallows group use declarations.
- **MultipleUsesPerLine**: Disallows multiple use statements on one line.
- **NamespaceDeclaration**: Enforces proper namespace declaration format.
- **NamespaceSpacing**: Enforces spacing around namespace declarations.
- **ReferenceUsedNamesOnly**: Requires use statements for all referenced names.
- **RequireOneNamespaceInFile**: Requires exactly one namespace per file.
- **UnusedUses**: Disallows unused use statements including in annotations.
- **UseDoesNotStartWithBackslash**: Disallows leading backslash in use statements.
- **UseFromSameNamespace**: Disallows use statements from the same namespace.
- **UseSpacing**: Enforces spacing between use statements.
- **UselessAlias**: Disallows useless use statement aliases.

### Operators

- **NegationOperatorSpacing**: Enforces spacing around negation operator.
- **RequireCombinedAssignmentOperator**: Requires combined assignment operators (`+=`, `-=`, etc.).
- **RequireOnlyStandaloneIncrementAndDecrementOperators**: Requires `++` and `--` to be used standalone.
- **SpreadOperatorSpacing**: Enforces spacing around spread operator.

### PHP

- **DisallowDirectMagicInvokeCall**: Disallows direct `__invoke()` calls.
- **ReferenceSpacing**: Enforces spacing around reference operator.
- **ShortList**: Requires short list syntax `[]`.
- **TypeCast**: Enforces proper type cast format.
- **UselessSemicolon**: Disallows useless semicolons.

### Strings

- **DisallowVariableParsing**: Disallows variable parsing in strings.

### Type Hints

- **ClassConstantTypeHint**: Requires class constant type hints.
- **DeclareStrictTypes**: Requires `declare(strict_types=1)`.
- **DNFTypeHintFormat**: Enforces DNF type format without spaces.
- **LongTypeHints**: Requires long type hints (`int` instead of `integer`).
- **NullableTypeForNullDefaultValue**: Requires nullable type for parameters with null default.
- **ParameterTypeHint**: Requires parameter type hints.
- **ParameterTypeHintSpacing**: Enforces spacing around parameter type hints.
- **PropertyTypeHint**: Requires property type hints.
- **ReturnTypeHint**: Requires return type hints.
- **ReturnTypeHintSpacing**: Enforces spacing around return type hints.

### Variables

- **DisallowVariableVariable**: Disallows variable variables (`$$var`).
- **DuplicateAssignmentToVariable**: Disallows duplicate assignments to the same variable.
- **UselessVariable**: Disallows useless variables.
