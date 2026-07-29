# Ocean Moon Coding Standard - Inherited Sniffs

This document lists the PHP_CodeSniffer sniffs inherited from other coding standards by the [Ocean Moon Coding Standard](../README.md).

PHP_CodeSniffer provides the PSR-12, Generic, and Squiz sniffs out of the box. Documentation is found in the [repository](https://github.com/PHPCSStandards/PHP_CodeSniffer) and
  [wiki](https://github.com/PHPCSStandards/PHP_CodeSniffer/wiki).

---
## PSR-12

Reference: [PSR-12 standard](https://www.php-fig.org/psr/psr-12/)

The complete PSR-12 coding standard is included, with the following exceptions:

- **PSR1.Classes.ClassDeclaration.MultipleClasses** is disabled for files in `tests` folders, because PHPUnit test files
  often include small additional classes for testing purposes.
- **PSR2.Classes.PropertyDeclaration** is excluded, replaced by `OceanMoon.Classes.PropertyDeclaration`, which adds
  support for property hooks (PHP 8.4) (see [Custom Sniffs](CustomSniffs.md)).
- **Generic.WhiteSpace.ScopeIndent** is excluded, replaced by `OceanMoon.WhiteSpace.ScopeIndent`, which also adds
  support for property hooks (see [Custom Sniffs](CustomSniffs.md)).

---
## Generic

- **Generic.Arrays.DisallowLongArraySyntax**: Requires short array syntax `[]` instead of `array()`.
- **Generic.Formatting.SpaceAfterCast**: Enforces a space after type casts (e.g. `(int) $value`).
- **Generic.CodeAnalysis.AssignmentInCondition**: Warns about assignments in `if`, `elseif`, `while`, `for`, `switch`,
  `case`, and `match` conditions/expressions.

---
## Squiz

- **Squiz.NamingConventions.ValidVariableName**: Enforces `$camelCase` for variables, parameters, and properties.
- **Squiz.Strings.DoubleQuoteUsage.NotRequired**: Ensures strings use single quotes unless double quotes are necessary.

---
## Slevomat

Reference: [Slevomat coding standard repository and documentation](https://github.com/slevomat/coding-standard)

### Arrays

- **ArrayAccess**: Disallows whitespace between array access operators.
- **DisallowImplicitArrayCreation**: Disallows implicit array creation.
- **DisallowPartiallyKeyed**: Requires arrays to have keys for all or none of the values.
- **SingleLineArrayWhitespace**: Checks whitespace in single-line array declarations.

### Attributes

- **AttributeAndTargetSpacing**: Enforces spacing between attributes and their targets.
- **AttributesOrder**: Requires attributes to be ordered alphabetically.
- **DisallowAttributesJoining**: Disallows joining multiple attributes with commas.
- **DisallowMultipleAttributesPerLine**: Requires each attribute on its own line.
- **RequireAttributeAfterDocComment**: Requires attributes to appear after doc comments.

### Classes

- **BackedEnumTypeSpacing**: Enforces spacing around backed enum types.
- **ClassConstantVisibility**: Requires visibility modifiers on class constants.
- **ClassMemberSpacing**: Enforces spacing between class members.
- **ConstantSpacing**: Enforces spacing around class constants.
- **DisallowMultiPropertyDefinition**: Disallows defining multiple properties in one statement.
- **DisallowStringExpressionPropertyFetch**: Disallows string expressions for property access.
- **EmptyLinesAroundClassBraces**: Enforces no empty lines after opening or before closing class braces.
- **EnumCaseSpacing**: Enforces spacing between enum cases.
- **MethodSpacing**: Enforces spacing between methods.
- **ModernClassNameReference**: Requires ::class syntax for class name references.
- **PropertyDeclaration**: Enforces proper property declaration format including promoted properties.
- **PropertySpacing**: Enforces spacing between properties.
- **RequireMultiLineMethodSignature**: Requires multi-line format for long method signatures.
- **RequireSelfReference**: Requires self:: instead of ClassName:: for self-references.
- **RequireSingleLineMethodSignature**: Requires single-line format for short method signatures.
- **TraitUseDeclaration**: Enforces proper trait use declaration format.
- **TraitUseSpacing**: Enforces spacing around trait use statements.

### Commenting

- **AnnotationName**: Enforces correct annotation names.
- **DeprecatedAnnotationDeclaration**: Enforces proper @deprecated annotation format.
- **EmptyComment**: Disallows empty comments.
- **RequireOneLinePropertyDocComment**: Requires one-line format for short property doc comments.
- **UselessInheritDocComment**: Disallows useless @inheritDoc comments.

### Control Structures

- **DisallowContinueWithoutIntegerOperandInSwitch**: Disallows continue without integer operand in switch.
- **DisallowTrailingMultiLineTernaryOperator**: Requires leading operators in multi-line ternary expressions.
- **LanguageConstructWithParentheses**: Requires parentheses for language constructs.
- **RequireMultiLineCondition**: Requires multi-line format for long conditions with boolean operators.
- **RequireMultiLineTernaryOperator**: Requires multi-line format for long ternary operators.
- **RequireNullCoalesceEqualOperator**: Requires ??= operator when possible.
- **RequireNullCoalesceOperator**: Requires ?? operator when possible.
- **RequireNullSafeObjectOperator**: Requires ?-> operator when possible.
- **RequireSingleLineCondition**: Requires single-line format for short conditions.
- **RequireShortTernaryOperator**: Requires short ternary operator ?: when possible.
- **RequireTernaryOperator**: Requires ternary operator when possible.
- **UselessIfConditionWithReturn**: Disallows useless if conditions returning true or false.

### Exceptions

- **DeadCatch**: Disallows empty catch blocks that don't handle exceptions.
- **RequireNonCapturingCatch**: Requires catch without variable when exception is not used.

### Files

- **LineLength**: Enforces maximum line length of 120 characters.

### Functions

- **ArrowFunctionDeclaration**: Enforces proper arrow function declaration format.
- **DisallowEmptyFunction**: Disallows empty function bodies.
- **FunctionLength**: Enforces maximum function length of 100 lines.
- **RequireArrowFunction**: Requires arrow functions for simple closures.
- **RequireMultiLineCall**: Requires multi-line format for long function calls.
- **RequireSingleLineCall**: Requires single-line format for short function calls.
- **NamedArgumentSpacing**: Enforces spacing around named arguments.
- **DisallowTrailingCommaInCall**: Disallows trailing comma in function calls.
- **DisallowTrailingCommaInClosureUse**: Disallows trailing comma in closure use statements.
- **DisallowTrailingCommaInDeclaration**: Disallows trailing comma in function declarations.
- **StaticClosure**: Requires static keyword for closures that don't use $this.
- **StrictCall**: Enforces strict call_user_func_array and call_user_func usage.
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
- **RequireCombinedAssignmentOperator**: Requires combined assignment operators (+=, -=, etc.).
- **RequireOnlyStandaloneIncrementAndDecrementOperators**: Requires ++ and -- to be used standalone.
- **SpreadOperatorSpacing**: Enforces spacing around spread operator.

### PHP

- **DisallowDirectMagicInvokeCall**: Disallows direct \_\_invoke() calls.
- **ReferenceSpacing**: Enforces spacing around reference operator.
- **ShortList**: Requires short list syntax [].
- **TypeCast**: Enforces proper type cast format.
- **UselessSemicolon**: Disallows useless semicolons.

### Strings

- **DisallowVariableParsing**: Disallows variable parsing in strings.

### Type Hints

- **DeclareStrictTypes**: Requires declare(strict_types=1).
- **ParameterTypeHintSpacing**: Enforces spacing around parameter type hints.
- **ReturnTypeHintSpacing**: Enforces spacing around return type hints.
- **LongTypeHints**: Requires long type hints (int instead of integer).
- **DNFTypeHintFormat**: Enforces DNF type format without spaces.
- **NullableTypeForNullDefaultValue**: Requires nullable type for parameters with null default.
- **ParameterTypeHint**: Requires parameter type hints.
- **PropertyTypeHint**: Requires property type hints.
- **ReturnTypeHint**: Requires return type hints.
- **ClassConstantTypeHint**: Requires class constant type hints.

### Variables

- **DisallowVariableVariable**: Disallows variable variables (\$$var).
- **DuplicateAssignmentToVariable**: Disallows duplicate assignments to the same variable.
- **UselessVariable**: Disallows useless variables.
