# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added

- **`OceanMoon.PHP.DisallowMultiConstantDefinition`** — disallows defining multiple constants in one statement (e.g.
  `const A = 1, B = 2;`), for both class constants and namespace/file-scope constants. A fork of
  `SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition`, which was removed in `2.0.1` because it false-
  positived on a single, valid constant whose value is a multi-argument `new` expression or function call (e.g.
  `const I = new Complex(0, 1);`) — the upstream sniff skips commas inside a short array (`[...]`) when scanning for
  constant separators, but not commas inside a parenthesized argument list. This fork adds that missing skip, fixing
  the false positive while still catching genuine multi-constant statements. See the "Custom Sniffs" section in
  `README.md`.
- **`Generic.CodeAnalysis.AssignmentInCondition`**, replacing
  `SlevomatCodingStandard.ControlStructures.AssignmentInCondition` (see Removed) — covers `if`, `elseif`, `while`,
  `for` (condition clause only), `switch`, `case`, and `match`, and every assignment operator (`=`, `+=`, `??=`,
  etc.), not just `=` in `if`/`elseif`/`do`-`while`. Confirmed by testing directly: the Slevomat rule doesn't fire at
  all on `while ($item = array_shift($items))`, `for ($i = 0; $x = $items[$i] ?? null; $i++)`, or `if ($y ??= 5)` —
  only the `do`-`while` case overlaps between the two. Reports as a warning rather than an error, but `phpcs` still
  exits non-zero on warnings alone, so this doesn't weaken enforcement in CI.
- **New "Yoda-style Comparisons" section in `README.md`**, explaining why this standard takes no position on Yoda
  conditions (Symfony requires them; WordPress required them but is phasing the requirement out; Drupal explicitly
  disallows them; Laravel actively rewrites them away via Pint; Slevomat disallows them as a readability
  anti-pattern; most other standards don't care) and that `Generic.CodeAnalysis.AssignmentInCondition` addresses the
  actual problem Yoda conditions were meant to solve, without dictating operand order.

### Removed

- `SlevomatCodingStandard.Operators.DisallowEqualOperators` — forcing `===`/`!==` everywhere is incompatible with common
  PHP ecosystem conventions (Drupal, Laravel, and, less strictly, WordPress all permit or rely on loose comparison in
  places), and it actively breaks code that intentionally overloads `==`/`!=` for value equality (e.g.
  `oceanmoon/math-ext`'s `Rational`). Loose vs. strict comparison is now left to the developer's judgement per call
  site.
- `SlevomatCodingStandard.ControlStructures.DisallowYodaComparison` — opinion is genuinely split across the PHP
  ecosystem (see the new README section), so this standard no longer takes a position on Yoda-style comparisons
  either way.
- `SlevomatCodingStandard.ControlStructures.AssignmentInCondition` — replaced by
  `Generic.CodeAnalysis.AssignmentInCondition` (see Added), which covers substantially more cases.

### Changed

- The rule `Generic.Formatting.SpaceAfterCast` had been overridden to have no space between a cast operator and the
  operand (e.g. `(int)$x`). This was reset to the default setting, which includes a space (e.g. `(int) $x`).
- **`OceanMoon.Arrays.ArrayDeclaration` reworked to classify list array elements as "simple" or "complex"**, loosely
  mirroring `OceanMoon\Core\Stringify::stringifyArray()`'s pretty-print logic. Simple elements — scalar literals,
  `null`/`true`/`false`, bare variables, and bare constants — are still eligible for the compact single-line or grid
  formats. Any complex element (nested array, function/method call, `new` expression, arithmetic, enum case access,
  property/static access, or any other expression) now forces one element per line **unconditionally**, even when
  the whole array would otherwise fit on one line — previously, such lists stayed compact if they fit within the
  line length regardless of content, and only fell back to grid/one-per-line once they didn't. Nested arrays already
  behaved this way; the change is extending the same treatment to every other kind of complex element.

---

## [2.0.1] - 2026-07-07

### Removed

- `SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition` — this sniff is scoped to class constant lists (e.g.
  `public const A = 1, B = 2;`) but misfires on single top-level `const` statements whose value is a `new` expression
  with more than one constructor argument (e.g. `const I = new Complex(0, 1);`), apparently miscounting the comma inside
  the constructor call as a constant-list separator. Removed rather than suppressed, since it produced a false positive
  on a single, valid constant declaration.

---

## [2.0.0] - 2026-06-18

### Changed

- **Renamed package** from `galaxon/coding-standard` to `oceanmoon/coding-standard` — update your `composer.json`
  require accordingly.
- **Renamed sniffs** — all `Galaxon.*` sniff references must be updated to `OceanMoon.*` in `phpcs.xml` and any inline
  `// phpcs:ignore` comments:
  - `Galaxon.Arrays.ArrayDeclaration` → `OceanMoon.Arrays.ArrayDeclaration`
  - `Galaxon.Classes.ClassInstantiationNoBrackets` → `OceanMoon.Classes.ClassInstantiationNoBrackets`
  - `Galaxon.Classes.PropertyDeclaration` → `OceanMoon.Classes.PropertyDeclaration`
  - `Galaxon.WhiteSpace.ScopeIndent` → `OceanMoon.WhiteSpace.ScopeIndent`
- **Renamed PHP namespaces** from `Galaxon\*` to `OceanMoon\*` throughout all source and test files.
- **Moved source tree** from `src/Galaxon/` to `src/OceanMoon/`.
- **Ruleset name** changed from `Galaxon` to `OceanMoon` — update `<rule ref="Galaxon"/>` to `<rule ref="OceanMoon"/>`
  in your `phpcs.xml`.
- `composer.json`: updated author email, homepage, and support URLs to Ocean Moon Software.

---

## [1.2.1] - 2026-04-09

### Changed

- Removed `SlevomatCodingStandard.Commenting.RequireOneLineDocComment` from the ruleset. The sniff was too aggressive in
  collapsing multi-line docblocks that callers preferred to keep expanded.

### Fixed

- **ClassInstantiationNoBracketsSniff** — No longer incorrectly removes parentheses from method/function call argument
  lists containing `new` expressions (e.g. `$obj->add(new Foo())->method()`).

### Documentation

- README: removed `RequireOneLineDocComment` from the sniff list; minor wording fix.

---

## [1.2.0] - 2026-03-30

### Changed

- **Single-line arrays take priority** — any list (without nested arrays) that fits on one line without overflowing the
  maximum line length and without multiline elements now uses single-line format, regardless of content. Function calls
  and `new` expressions no longer force multiline when the array fits on one line. Lists with nested arrays always use
  one-per-line format.
- **Nested arrays excluded from grid format** — lists containing nested arrays that are too long for a single line use
  one-per-line format instead of grid.
- **UseSpacing** — set `linesCountBetweenUseTypes` to 1 for a blank line between different import types (class,
  function, const).
- Excluded `Generic.Files.LineLength.TooLong` warning (replaced by Slevomat's `Files.LineLength.LineTooLong` error).
- Removed `UselessConstantTypeHint` sniff (not needed with `ClassConstantTypeHint`).

### Fixed

- **Array fix bouncing** — fixed infinite loop in PHPCBF when collapsing a multiline list to single line. The trailing
  comma removal and single-line collapse no longer conflict.

### Documentation

- Updated README to reflect single-line priority for all list types.
- Updated array formatting examples and descriptions.

---

## [1.1.1] - 2026-03-03

### Changed

- **Grid format eligibility expanded** — variables, properties, constants, enums, and simple expressions (e.g. grouping
  parentheses) are now eligible for single-line and grid formatting, not just scalar literals.
- **Function/method calls, `new` expressions, and closures always one-per-line** — arrays containing these elements are
  formatted one item per line regardless of total length.
- Updated README to reflect new grid eligibility rules and terminology.

---

## [1.1.0] - 2026-03-01

### Added

- **Grid format** for scalar list arrays too long for a single line — items arranged in uniformly padded rows.
- **One-per-line format** for non-scalar list arrays — function calls, expressions, etc. always formatted one element
  per line with trailing comma, regardless of length.
- **Unit tests** for all four custom Galaxon sniffs:
  - `Galaxon.Arrays.ArrayDeclaration` — covers scalar lists, grid format, one-per-line, list-of-arrays, associative
    arrays, mixed keyed/unkeyed, and explicit sequential integer keys.
  - `Galaxon.Classes.ClassInstantiationNoBrackets` — covers method calls, property access, nullsafe, constructor args,
    chained calls.
  - `Galaxon.Classes.PropertyDeclaration` — covers hooks, asymmetric visibility, modifier ordering, var keyword,
    underscore warning.
  - `Galaxon.WhiteSpace.ScopeIndent` — covers PHP 8.4 property hook indentation.
- PHPUnit configuration (`phpunit.xml.dist`) and test bootstrap.

### Changed

- `Galaxon.Arrays.ArrayDeclaration` sniff rewritten with format-selection logic: scalar lists use single-line or grid;
  non-scalar lists and associative arrays use one-per-line.
- README updated with documentation for all array formats, links to upstream standard documentation (PSR-12, Generic,
  Squiz, Slevomat), and improved descriptions.

### Fixed

- `Galaxon.Classes.ClassInstantiationNoBrackets` namespace corrected from `dev\Sniffs\Classes` to
  `Galaxon\Sniffs\Classes`.
- Trailing whitespace in `Core\Stringify::stringifyList()` grid format — last item on each row no longer padded.

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
