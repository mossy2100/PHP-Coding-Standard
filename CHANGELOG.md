# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added

- **`PERCS31.Files.LineLength`.** Replaces `Generic.Files.LineLength`. A line over 120 characters still gets a `TooLong`
  warning, and a line over 80 now gets an `OverRecommended` warning with a severity of 4, as PER Coding Style 3.1 says
  lines SHOULD NOT be longer than 80. It's hidden by default; run `phpcs --warning-severity=4` to see it. Update any
  `phpcs:ignore` comments or `<exclude>` rules that name `Generic.Files.LineLength`.
- **The PERCS31 standard (breaking).** A second standard in the package, for [PER Coding Style 3.1](https://www.php-fig.org/per/coding-style/) and nothing more,
  built on PSR-12. Use `<rule ref="PERCS31"/>` to follow PER Coding Style without the house rules. The Mossy2100
  standard is now PERCS31 plus house rules, and reports the same violations as before. The sniffs for PER rules move to
  PERCS31, so their codes change prefix, e.g. `Mossy2100.Formatting.EmptyBody` is now `PERCS31.Formatting.EmptyBody`.
  Update any `phpcs:ignore` comments or `<exclude>` rules that name them. See [PERCS31](docs/PERCS31.md).
- **`PERCS31.Operators.OperatorSpacing`.** Replaces `PSR12.Operators.OperatorSpacing`, requiring at least one space
  around a binary operator, and adds the pipe operator `|>`. The Mossy2100 standard still requires exactly one.
- **`orderHooksWithBodies` on `PERCS31.Classes.PropertyDeclaration`.** `HookOrder` only checks a hook list with no
  bodies by default, as PER Coding Style 3.1 only sets the order of an abstract or interface property's hooks. The
  Mossy2100 standard turns it on, to check every hook list as before.
- **`Mossy2100.Functions.RequireSingleLineCall`.** Replaces `SlevomatCodingStandard.Functions.RequireSingleLineCall`,
  which reported a call with a multi-line anonymous class argument, e.g. `consume(new class {` ... `});`, and fixed it
  by putting the class body on one line. Such a call is now skipped, as one with a closure argument already was.
- **`PERCS31.Arrays.OpeningBracketPlacement` (breaking).** The opening bracket of a multi-line array must not be on a
  line of its own, as PER Coding Style 3.1 requires, e.g. `foo([` ... `]);`, not `foo(` then `[` on the next line. An
  array that's an element of a multi-line array is allowed. Auto-fixable, except next to another argument.
- **The pipe operator `|>` (PHP 8.5) (breaking).** As PER Coding Style 3.1 requires, `|>` needs a space either side, and
  starts the line when a statement wraps at it. `Mossy2100.WhiteSpace.OperatorSpacing` replaces
  `Squiz.WhiteSpace.OperatorSpacing` to check the spacing, and `PERCS31.Operators.OperatorPlacement` now covers `|>`.
  Only checked when PHPCS runs on PHP 8.5. Auto-fixable.
- **`PERCS31.ControlStructures.SwitchDeclaration` (breaking).** Replaces `PSR2.ControlStructures.SwitchDeclaration`,
  adding two rules from PER Coding Style 3.1: the last non-empty case must end with `break`, `return` or similar, and a
  multi-line case condition must be wrapped in parentheses, with `):` on a line of their own. Auto-fixable.
- **`PERCS31.PHP.RequireCloneParentheses`.** A warning for `clone $a`, which should be `clone($a)`, as PER Coding Style
  3.1 says `clone()` SHOULD be called with parentheses. Auto-fixable.
- **`Generic.Strings.UnnecessaryHeredoc`.** A warning for a heredoc with no interpolation or escape sequences, which
  could be a nowdoc, as PER Coding Style 3.0 says a nowdoc SHOULD be used wherever possible. Auto-fixable.
- **`PERCS31.Attributes.DisallowMultiLineAttributesJoining` (breaking).** An attribute block split over several lines
  must have one attribute per block, as PER Coding Style 3.0 requires. A joined block on one line, such as
  `#[Attribute, Deprecated]`, is still allowed. Auto-fixable.
- **`PERCS31.Classes.EnumMemberVisibility` (breaking).** Requires a non-public method or constant in an enum to be
  `private`, not `protected`, as PER Coding Style 3.1 requires. Auto-fixable.
- **`PERCS31.NamingConventions.ValidEnumCaseName` (breaking).** Requires enum case names to be in PascalCase, as PER
  Coding Style 3.0 requires, e.g. `case BigJoker;`, not `case BIG_JOKER;` or `case bigJoker;`. Not auto-fixable.
- **No whitespace after a prefix unary operator (breaking).** A space or line break between a prefix operator and its
  operand is now an error for `!` (`Generic.Formatting.SpaceAfterNot`, with `spacing` set to 0), and for `+`, `~` and
  `@` (`Mossy2100.Operators.UnaryOperatorSpacing`, a new sniff). `-`, `++`, `--`, `&` and `...` were already checked.
  Auto-fixable.
- **A wrapped operator must start the next line (breaking).** As PER Coding Style 3.0 requires, a statement that wraps
  at an operator has the operator at the start of the new line, not the end of the previous one, e.g. `$total = $a` then
  `+ $b` on the next line. Three sniffs check this:
  - `PERCS31.Operators.OperatorPlacement`, a new sniff, for assignment, arithmetic, comparison, bitwise and logical
    operators, `??`, `instanceof`, `?:` and an arrow function's `=>`.
  - `Universal.Operators.ConcatPosition`, for `.`.
  - `PSR12.ControlStructures.BooleanOperatorPlacement`, now with `allowOnly` set to `first`, for `&&` and `||` in a
    condition.

  Auto-fixable.
- **`Mossy2100.Namespaces.UnusedUses`.** Replaces `SlevomatCodingStandard.Namespaces.UnusedUses`, which reads
  `use Function Vendor\helper;` as a class import, and deletes it as unused. This sniff skips a file while its `use`
  keyword is miscased, until `Universal.UseStatements.LowercaseFunctionConst` has fixed it.
- **PHPCSExtra as a base standard (breaking).** `phpcsstandards/phpcsextra` is now required, and six of its sniffs are
  used, each for a PER Coding Style 3.0 rule:
  - `Universal.Attributes.DisallowAttributeParentheses`: no `()` on an attribute with no arguments.
  - `Universal.Classes.DisallowAnonClassParentheses`: no `()` on an anonymous class with no arguments.
  - `Universal.Constants.LowercaseClassResolutionKeyword`: `::class` in lower case.
  - `Universal.PHP.LowercasePHPTag`: `<?php` in lower case.
  - `Universal.PHP.RequireExitDieParentheses`: a warning for `exit` or `die` without parentheses.
  - `Universal.UseStatements.LowercaseFunctionConst`: `function` and `const` in lower case in a `use` statement.
- **`Mossy2100.ControlStructures.LanguageConstructWithParentheses`.** Replaces
  `SlevomatCodingStandard.ControlStructures.LanguageConstructWithParentheses`, which removed the parentheses from
  `die()`, against PER Coding Style 3.0. It still reports `echo('x')` and the like.
- **`PERCS31.ControlStructures.MultiLineTernaryOperator` (breaking).** Requires a ternary that spans lines to take
  three, with `?` and `:` each starting a line, indented once, as PER Coding Style 3.0 requires. Replaces
  `SlevomatCodingStandard.ControlStructures.DisallowTrailingMultiLineTernaryOperator`, which only reported a trailing
  `?`. Auto-fixable.
- **`PERCS31.NamingConventions.ValidConstantName` (breaking).** Disallows a constant name that starts with an
  underscore, e.g. `public const int _LIMIT = 10;`, as PER Coding Style 3.0 requires. Not auto-fixable.
- **`SlevomatCodingStandard.Classes.ClassKeywordOrder` (breaking).** Requires `abstract` or `final` before `readonly` on
  a class, e.g. `final readonly class`, not `readonly final class`, as PER Coding Style 3.0 requires. Auto-fixable.
- **`PERCS31.Formatting.EmptyBody` (breaking).** Warns about an empty class or function body that isn't written as `{}`
  on the same line as the declaration, as PER Coding Style 3.1 recommends, e.g. `public function reset(): void {}`. An
  empty closure or anonymous class body that isn't `{}` is an error, as PER 3.1 requires it, e.g.
  `static function (): void {}` or `new class {}`. Auto-fixable.
- **`PERCS31.Formatting.DisallowMultipleStatements`.** Replaces `Generic.Formatting.DisallowMultipleStatements`, which
  reported `{ get; set; }` on an abstract or interface property as two statements on one line. PER Coding Style 3.0
  requires them on one line. Update any `phpcs:ignore` comments or `<exclude>` rules that name the Generic code.
- **`PERCS31.Attributes.AttributePlacement` (breaking).** Requires an attribute to be on the line before its target,
  except on a parameter in a single-line parameter list, where it must be inline, as PER Coding Style 3.0 requires.
  `SlevomatCodingStandard.Attributes.AttributeAndTargetSpacing` now allows an attribute on the same line as its target,
  and this sniff reports every other case. Attributes on an anonymous class follow PER Coding Style 3.1 instead:
  starting on the line after `new`, indented once, with `class` on the line after them. `PERCS31.WhiteSpace.ScopeIndent`
  indents the class body to match. Auto-fixable.
- **`Mossy2100.NamingConventions.ValidVariableName` (breaking).** Warns about a variable or parameter that starts with
  an underscore, inside a class, enum, trait or interface. `Squiz.NamingConventions.ValidVariableName` reports these
  outside a class, but allows them inside one. Superglobals such as `$_GET` are exempt. Not auto-fixable.
- **`Mossy2100.ControlStructures.RequireSingleLineHeader` (breaking).** Requires a multi-line `foreach`, `for`, `switch`
  or `match` header to be written on one line if it fits, as Slevomat's `RequireSingleLineCondition` does for `if` and
  `while`. Auto-fixable.
- **`PERCS31.ControlStructures.MatchArrowSpacing` (breaking).** Requires one space before and after `=>` in a `match`
  arm, so arms aren't aligned. No stock sniff checks these. Auto-fixable.
- **`PERCS31.Classes.PromotedPropertyHooks` (breaking).** A hook on a promoted constructor parameter must be a single
  hook with a short `=>` body, on one line, as PER Coding Style 3.1 requires, e.g.
  `public function __construct(public string $name { set => trim($value); })`. Otherwise, declare the property in the
  class body. Not auto-fixable.
- **`Mossy2100.Classes.RequireSingleLineMethodSignature`, `Mossy2100.TypeHints.ParameterTypeHint` and
  `Mossy2100.TypeHints.ParameterTypeHintSpacing`.** Replace the Slevomat sniffs of the same names, which misread a hook
  on a promoted constructor parameter. They skip a function with one, and check everything else as before.
- **`Squiz.WhiteSpace.ObjectOperatorSpacing` (breaking).** Disallows spaces around `->`, `?->` and `::`, e.g.
  `$a -> b()` or `Foo :: bar()`. A line break before one, in a wrapped method chain, is allowed. Auto-fixable.

### Changed

- **Renamed the package from `oceanmoon/coding-standard` to `mossy2100/coding-standard`, and the standard from
  `OceanMoon` to `Mossy2100` (breaking),** matching the author's GitHub and Packagist name. Use
  `<rule ref="Mossy2100"/>` in `phpcs.xml`. Sniff codes change to match, e.g. `Mossy2100.Arrays.ArrayDeclaration`, as do
  the sniff class namespaces (`Mossy2100\Sniffs\...`).
- **`PERCS31.Classes.ClassInstantiationNoBrackets` is now a warning, and safer.** Renamed from
  `OceanMoon.Classes.ClassInstantiationNoBrackets`. As PER Coding Style 3.0 says SHOULD, `(new Foo())->bar()` is now a
  warning, not an error, fixed to `new Foo()->bar()`. It now covers `::` as well as `->` and `?->`, and only reports
  parentheses holding exactly one `new` expression, so `(new Foo() ?: $default)->bar()` and `new self(new Foo())->bar()`
  are no longer changed.
- **Trailing commas follow PER Coding Style 3.0 (breaking).** A multi-line function call, function declaration or
  closure `use` list now requires a trailing comma, and a single-line one still disallows it. Adds
  `SlevomatCodingStandard.Functions.RequireTrailingCommaInCall`, `...InDeclaration` and `...InClosureUse`. Run `phpcbf`
  to add the commas.
- **Line length is a soft limit (breaking).** A line over 120 characters is now a warning from
  `Generic.Files.LineLength`, PSR-12's own sniff, not an error from `SlevomatCodingStandard.Files.LineLength`, as PER
  Coding Style 3.0 says there must be no hard limit. `phpcs` still exits with a non-zero code on warnings by default. To
  change the limit, set `lineLimit` on the Generic sniff (see `docs/LineLength.md`). Update any `phpcs:ignore` comments
  or `<exclude>` rules that name `SlevomatCodingStandard.Files.LineLength.LineTooLong`.
- **An empty class or function body can be `{}` (breaking).** `PSR2.Classes.ClassDeclaration`,
  `Squiz.Functions.MultiLineFunctionDeclaration`, `Squiz.WhiteSpace.ScopeClosingBrace` and
  `SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces` rejected it. Each is replaced by a version that allows
  it, e.g. `PERCS31.Classes.ClassDeclaration`, or `Mossy2100.Classes.EmptyLinesAroundClassBraces` for the Slevomat one,
  so their error codes change prefix. Update any `phpcs:ignore` comments or `<exclude>` rules that name them.
- **No space after `fn` (breaking).** Arrow functions are now written `fn($x) => $x`, not `fn ($x) => $x`, as PER Coding
  Style 3.0 requires. Run `phpcbf` to reformat.
- **`null` must be last in a union type (breaking).** `int|null` and `null|int|string` are now errors, as PER Coding
  Style 3.0 requires `null` last. Set by `nullPosition` on `SlevomatCodingStandard.TypeHints.DNFTypeHintFormat`.
  Auto-fixable.
- **`PERCS31.Classes.PropertyDeclaration` checks `get` before `set` (breaking).** When a property has both hooks, `set`
  before `get` is now an error (`HookOrder`). PER Coding Style 3.1 requires `{ get; set; }` on an abstract or interface
  property, so PERCS31 only checks a hook list with no bodies, and the Mossy2100 standard checks every hook list.
  Auto-fixable, unless there's a comment between the hooks.
- **An arrow function may span lines.** Its expression can go on the next line, as PER Coding Style 3.0 allows.
  `SlevomatCodingStandard.Functions.ArrowFunctionDeclaration` previously required it on one line.
- **Array formatting follows PER Coding Style, with no alignment (breaking).** `Mossy2100.Arrays.ArrayDeclaration` was
  rewritten to do one thing: collapse a multi-line array that fits on one line. Stock sniffs handle the rest:
  - An array is single-line if it fits, otherwise one element per line. Grid layout is gone, and a single-line
    associative array is now allowed.
  - Trailing commas are required on multi-line arrays and disallowed on single-line ones, as before.
  - Arrows and assignments aren't aligned. A single space is required before `=>` and `=`, since alignment makes noisy
    diffs.

  The added stock sniffs are `Generic.Arrays.ArrayIndent`, `Squiz.Arrays.ArrayDeclaration` (alignment checks excluded),
  `Squiz.WhiteSpace.OperatorSpacing` and `SlevomatCodingStandard.Arrays.MultiLineArrayEndBracketPlacement`. The sniff's
  `indent` property was removed; `maxLineLength` remains. Run `phpcbf` to reformat existing code. Its error codes are
  replaced by one, `Mossy2100.Arrays.ArrayDeclaration.ShouldBeSingleLine`. Update any `phpcs:ignore` comments or
  `<exclude>` rules that name an old code, such as `ListShouldBeSingleLine`.
- **`PERCS31.Classes.PropertyDeclaration.Underscore` is an error, not a warning, and covers promoted constructor
  parameters (breaking).** A property named like `$_name` was a warning, and a promoted one wasn't reported at all. PER
  Coding Style 3.0 says property names must not start with an underscore.
- **Requires `slevomat/coding-standard` 8.27 or later** (was 8.25). Earlier versions report
  `SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification` on `#[Override]` methods
  that inherit their array shape from the parent's docblock, and 8.25.0 also misreports hooked properties as multiple
  property definitions.

### Removed

- **Four Slevomat checks that PERCS31 already covers.** `SlevomatCodingStandard.Classes.TraitUseDeclaration`,
  `SlevomatCodingStandard.Namespaces.NamespaceSpacing`,
  `SlevomatCodingStandard.Namespaces.UseDoesNotStartWithBackslash`, and the before-first and after-last checks of
  `SlevomatCodingStandard.Classes.TraitUseSpacing`. The same cases are reported by PSR-12's
  `PSR12.Traits.UseDeclaration`, `PSR12.Files.FileHeader` and `PSR12.Files.ImportStatement`. Update any `phpcs:ignore`
  comments or `<exclude>` rules that name the old codes.
- **`SlevomatCodingStandard.Namespaces.UseSpacing`.** `PSR12.Files.FileHeader` already reports, and fixes, the same
  blank lines before, between and after `use` statements. Update any `phpcs:ignore` comments or `<exclude>` rules that
  name a `UseSpacing` code.
- **`SlevomatCodingStandard.Attributes.DisallowAttributesJoining`.** Several attributes in one block, e.g.
  `#[Attribute, Deprecated]`, are now allowed, as PER Coding Style 3.0 allows them.
- **`SlevomatCodingStandard.Functions.DisallowEmptyFunction`.** It required a comment in an empty function body, so PER
  Coding Style 3.0's recommended `public function reset(): void {}` was an error.
- **Seven `PERCS31.Classes.PropertyDeclaration` error codes, which duplicated Slevomat sniffs already in the standard.**
  Each case is still reported, and fixed, by the Slevomat sniff. Remove any references to the old codes:
  - `Multiple`, now reported by `SlevomatCodingStandard.Classes.DisallowMultiPropertyDefinition`.
  - `SpacingAfterType`, `AvizKeywordOrder`, `StaticBeforeVisibility`, `ReadonlyBeforeVisibility`, `FinalAfterVisibility`
    and `AbstractAfterVisibility`, now reported by `SlevomatCodingStandard.Classes.PropertyDeclaration`.

### Fixed

- **`PERCS31.PHP.DisallowMultiConstantDefinition` fixer keeps the type and modifiers.** Splitting
  `final public const int A = 1, B = 2;` gave `public const B = 2;`, dropping the type, and indented the new line with a
  tab. Each constant now keeps the full declaration, e.g. `final public const int B = 2;`, with the original
  indentation.

---

## [2.2.0] - 2026-08-23

### Removed

- The `tests/*` exclude-pattern on `PSR1.Classes.ClassDeclaration.MultipleClasses`. Every class, interface, trait, and
  enum must now live in its own file, test fixtures included. There are no exceptions for `tests/*` any more. Existing
  multi-class test files should split their extra fixture classes into their own files (e.g. a `tests/Fixtures/`
  subdirectory), matching PSR-4 autoloading expectations.

---

## [2.1.0] - 2026-08-16

### Added

- **`OceanMoon.PHP.DisallowMultiConstantDefinition`**: disallows defining multiple constants in one statement (e.g.
  `const A = 1, B = 2;`), for both class constants and namespace/file-scope constants. A fork of
  `SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition`, which was removed in `2.0.1` because it false-
  positived on a single, valid constant whose value is a multi-argument `new` expression or function call (e.g.
  `const I = new Complex(0, 1);`). The upstream sniff skips commas inside a short array (`[...]`) when scanning for
  constant separators, but not commas inside a parenthesized argument list. This fork adds that missing skip, fixing
  the false positive while still catching genuine multi-constant statements. See the "Custom Sniffs" section in
  `README.md`.
- **`Generic.CodeAnalysis.AssignmentInCondition`**, replacing
  `SlevomatCodingStandard.ControlStructures.AssignmentInCondition` (see Removed): covers `if`, `elseif`, `while`,
  `for` (condition clause only), `switch`, `case`, and `match`, and every assignment operator (`=`, `+=`, `??=`,
  etc.), not just `=` in `if`/`elseif`/`do`-`while`. Confirmed by testing directly: the Slevomat rule doesn't fire at
  all on `while ($item = array_shift($items))`, `for ($i = 0; $x = $items[$i] ?? null; $i++)`, or `if ($y ??= 5)`;
  only the `do`-`while` case overlaps between the two. Reports as a warning rather than an error, but `phpcs` still
  exits non-zero on warnings alone, so this doesn't weaken enforcement in CI.
- **New "Yoda-style Comparisons" section in `README.md`**, explaining why this standard takes no position on Yoda
  conditions (Symfony requires them; WordPress required them but is phasing the requirement out; Drupal explicitly
  disallows them; Laravel actively rewrites them away via Pint; Slevomat disallows them as a readability
  anti-pattern; most other standards don't care) and that `Generic.CodeAnalysis.AssignmentInCondition` addresses the
  actual problem Yoda conditions were meant to solve, without dictating operand order.

### Removed

- `SlevomatCodingStandard.Operators.DisallowEqualOperators`: forcing `===`/`!==` everywhere is incompatible with common
  PHP ecosystem conventions (Drupal, Laravel, and, less strictly, WordPress all permit or rely on loose comparison in
  places), and it actively breaks code that intentionally overloads `==`/`!=` for value equality (e.g.
  `oceanmoon/math-ext`'s `Rational`). Loose vs. strict comparison is now left to the developer's judgment per call
  site.
- `SlevomatCodingStandard.ControlStructures.DisallowYodaComparison`: opinion is genuinely split across the PHP
  ecosystem (see the new README section), so this standard no longer takes a position on Yoda-style comparisons
  either way.
- `SlevomatCodingStandard.ControlStructures.AssignmentInCondition`: replaced by
  `Generic.CodeAnalysis.AssignmentInCondition` (see Added), which covers substantially more cases.
- `SlevomatCodingStandard.Classes.ClassConstantVisibility`: duplicated `PSR12.Properties.ConstantVisibility.NotFound`
  exactly (same trigger condition: a class constant with no explicit visibility keyword), and neither sniff was
  configured to auto-fix, so it added nothing beyond the PSR-12 check already inherited from `PSR12`.

### Changed

- The rule `Generic.Formatting.SpaceAfterCast` had been overridden to have no space between a cast operator and the
  operand (e.g. `(int)$x`). This was reset to the default setting, which includes a space (e.g. `(int) $x`).
- **`OceanMoon.Arrays.ArrayDeclaration` reworked to classify list array elements as "simple" or "complex"**, loosely
  mirroring `OceanMoon\Core\Stringify::stringifyArray()`'s pretty-print logic. Simple elements (scalar literals,
  `null`/`true`/`false`, bare variables, and bare constants) are still eligible for the compact single-line or grid
  formats. Any complex element (nested array, function/method call, `new` expression, arithmetic, enum case access,
  property/static access, or any other expression) now forces one element per line **unconditionally**, even when
  the whole array would otherwise fit on one line. Previously, such lists stayed compact if they fit within the
  line length regardless of content, and only fell back to grid/one-per-line once they didn't. Nested arrays already
  behaved this way; the change is extending the same treatment to every other kind of complex element.

---

## [2.0.1] - 2026-07-07

### Removed

- `SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition`: this sniff is scoped to class constant lists (e.g.
  `public const A = 1, B = 2;`) but misfires on single top-level `const` statements whose value is a `new` expression
  with more than one constructor argument (e.g. `const I = new Complex(0, 1);`), apparently miscounting the comma inside
  the constructor call as a constant-list separator. Removed rather than suppressed, since it produced a false positive
  on a single, valid constant declaration.

---

## [2.0.0] - 2026-06-18

### Changed

- **Renamed package** from `galaxon/coding-standard` to `oceanmoon/coding-standard`: update your `composer.json`
  require accordingly.
- **Renamed sniffs**: all `Galaxon.*` sniff references must be updated to `OceanMoon.*` in `phpcs.xml` and any inline
  `// phpcs:ignore` comments:
  - `Galaxon.Arrays.ArrayDeclaration` → `OceanMoon.Arrays.ArrayDeclaration`
  - `Galaxon.Classes.ClassInstantiationNoBrackets` → `OceanMoon.Classes.ClassInstantiationNoBrackets`
  - `Galaxon.Classes.PropertyDeclaration` → `OceanMoon.Classes.PropertyDeclaration`
  - `Galaxon.WhiteSpace.ScopeIndent` → `OceanMoon.WhiteSpace.ScopeIndent`
- **Renamed PHP namespaces** from `Galaxon\*` to `OceanMoon\*` throughout all source and test files.
- **Moved source tree** from `src/Galaxon/` to `src/OceanMoon/`.
- **Ruleset name** changed from `Galaxon` to `OceanMoon`: update `<rule ref="Galaxon"/>` to `<rule ref="OceanMoon"/>`
  in your `phpcs.xml`.
- `composer.json`: updated author email, homepage, and support URLs to Ocean Moon Software.

---

## [1.2.1] - 2026-04-09

### Changed

- Removed `SlevomatCodingStandard.Commenting.RequireOneLineDocComment` from the ruleset. The sniff was too aggressive in
  collapsing multi-line docblocks that callers preferred to keep expanded.

### Fixed

- **ClassInstantiationNoBracketsSniff**: No longer incorrectly removes parentheses from method/function call argument
  lists containing `new` expressions (e.g. `$obj->add(new Foo())->method()`).

### Documentation

- README: removed `RequireOneLineDocComment` from the sniff list; minor wording fix.

---

## [1.2.0] - 2026-03-30

### Changed

- **Single-line arrays take priority**: any list (without nested arrays) that fits on one line without overflowing the
  maximum line length and without multiline elements now uses single-line format, regardless of content. Function calls
  and `new` expressions no longer force multiline when the array fits on one line. Lists with nested arrays always use
  one-per-line format.
- **Nested arrays excluded from grid format**: lists containing nested arrays that are too long for a single line use
  one-per-line format instead of grid.
- **UseSpacing**: set `linesCountBetweenUseTypes` to 1 for a blank line between different import types (class,
  function, const).
- Excluded `Generic.Files.LineLength.TooLong` warning (replaced by Slevomat's `Files.LineLength.LineTooLong` error).
- Removed `UselessConstantTypeHint` sniff (not needed with `ClassConstantTypeHint`).

### Fixed

- **Array fix bouncing**: fixed infinite loop in PHPCBF when collapsing a multiline list to single line. The trailing
  comma removal and single-line collapse no longer conflict.

### Documentation

- Updated README to reflect single-line priority for all list types.
- Updated array formatting examples and descriptions.

---

## [1.1.1] - 2026-03-03

### Changed

- **Grid format eligibility expanded**: variables, properties, constants, enums, and simple expressions (e.g. grouping
  parentheses) are now eligible for single-line and grid formatting, not just scalar literals.
- **Function/method calls, `new` expressions, and closures always one-per-line**: arrays containing these elements are
  formatted one item per line regardless of total length.
- Updated README to reflect new grid eligibility rules and terminology.

---

## [1.1.0] - 2026-03-01

### Added

- **Grid format** for scalar list arrays too long for a single line, with items arranged in uniformly padded rows.
- **One-per-line format** for non-scalar list arrays: function calls, expressions, etc. always formatted one element
  per line with trailing comma, regardless of length.
- **Unit tests** for all four custom Galaxon sniffs:
  - `Galaxon.Arrays.ArrayDeclaration`: covers scalar lists, grid format, one-per-line, list-of-arrays, associative
    arrays, mixed keyed/unkeyed, and explicit sequential integer keys.
  - `Galaxon.Classes.ClassInstantiationNoBrackets`: covers method calls, property access, nullsafe, constructor args,
    chained calls.
  - `Galaxon.Classes.PropertyDeclaration`: covers hooks, asymmetric visibility, modifier ordering, var keyword,
    underscore warning.
  - `Galaxon.WhiteSpace.ScopeIndent`: covers PHP 8.4 property hook indentation.
- PHPUnit configuration (`phpunit.xml.dist`) and test bootstrap.

### Changed

- `Galaxon.Arrays.ArrayDeclaration` sniff rewritten with format-selection logic: scalar lists use single-line or grid;
  non-scalar lists and associative arrays use one-per-line.
- README updated with documentation for all array formats, links to upstream standard documentation (PSR-12, Generic,
  Squiz, Slevomat), and improved descriptions.

### Fixed

- `Galaxon.Classes.ClassInstantiationNoBrackets` namespace corrected from `dev\Sniffs\Classes` to
  `Galaxon\Sniffs\Classes`.
- Trailing whitespace in `Core\Stringify::stringifyList()` grid format: the last item on each row is no longer padded.

### Removed

- Old manual test fixtures in `tests/` directory (replaced by proper PHPUnit-based unit tests in `src/Galaxon/Tests/`).

---

## [1.0.0] - 2026-01-04

### First Stable Release

This is the first stable release of Galaxon Coding Standard, ready for publication on Packagist.

### Changed

- **composer.json** - Updated for Packagist publication:
  - Added keywords for discoverability
  - Added author information
  - Added homepage and support URLs
  - Improved description

---

## [0.3.0] - 2026-01-02

### Added

- Custom `Galaxon.Arrays.ArrayDeclaration` sniff for consistent array formatting:
  - Simple list arrays: single line if possible, no trailing comma
  - List of arrays: one element per line, trailing comma required
  - Associative arrays: one key-value pair per line, arrows aligned, trailing comma required
- Proper Unicode support using `mb_strlen()` for accurate arrow alignment with multibyte characters

---

## [0.2.0] - 2025-12-09

### Added

- Integrated Slevomat Coding Standard with 89 comprehensive sniffs covering:
  - Arrays (4 sniffs)
  - Attributes (5 sniffs)
  - Classes (18 sniffs)
  - Commenting (6 sniffs)
  - Control Structures (15 sniffs)
  - Exceptions (2 sniffs)
  - Files (1 sniff)
  - Functions (13 sniffs)
  - Namespaces (12 sniffs)
  - Operators (5 sniffs)
  - PHP (5 sniffs)
  - Strings (1 sniff)
  - Type Hints (11 sniffs)
  - Variables (3 sniffs)
- Added Squiz sniffs for variable naming and string quote usage
- Comprehensive README documentation listing all included sniffs with descriptions
- Tests directory structure

### Changed

- Replaced custom `Galaxon.NamingConventions.ValidVariableName` with `Squiz.NamingConventions.ValidVariableName`
- Moved `php_codesniffer` from dev dependencies to runtime dependencies
- Updated README structure:
  - Separated "Custom Sniffs" section for Galaxon-specific sniffs
  - Created dedicated "Variable and Property Naming Convention" section
  - Listed all PSR-12, Squiz, and Slevomat sniffs with concise descriptions
- Renamed ClassInstantiationNoBracketsSniff error code from `UnnecessaryParentheses` to `NewWithUnnecessaryParentheses`
- Updated composer scripts: added `-vp` flags to `fix` command for verbose progress output

### Removed

- Custom `Galaxon.NamingConventions.ValidVariableNameSniff` (moved to \_dev directory)

---

## [0.1.0] - 2025-11-23

### Added

- Initial release
- `Galaxon.NamingConventions.ValidVariableName` sniff - enforces `$lowerCamelCase` variable naming without leading
  underscores
- `Galaxon.Classes.ClassInstantiationNoBrackets` sniff - removes unnecessary parentheses around class instantiation (PHP
  8.4+)
- Extends PSR-12 coding standard
- Auto-registration via `dealerdirect/phpcodesniffer-composer-installer`
