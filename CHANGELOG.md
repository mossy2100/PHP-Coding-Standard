# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added

- **Spacing in an import (breaking).** A space just inside the braces of a group `use` on one line is now reported, by
  `PERCS31.Namespaces.GroupUseLayout`, as `SpaceAfterOpenBrace` or `SpaceBeforeCloseBrace`, so `use Foo\{ A, B };` must
  be `use Foo\{A, B};`. And `Universal.UseStatements.KeywordSpacing` now requires one space after `use`, `function` and
  `const`, and each side of `as`, in any import. `use Foo\Bar  as  Baz;` passed. Both are auto-fixable.
- **`PERCS31.Files.Encoding` (breaking).** A file that isn't valid UTF-8 is now reported, as `NotUtf8`, at its first
  invalid line. PSR-1 requires UTF-8, and only the byte-order mark was checked. Not auto-fixable.
- **Strict UPPER_SNAKE_CASE for constants, in Jumbo (breaking).** `PERCS31.NamingConventions.UpperCaseConstantName` has
  a new `requireUpperSnakeCase` property, which Jumbo turns on. A constant in upper case must be words of capitals and
  digits, separated by single underscores, so `__HIDDEN`, `TWO__WORDS` and `TRAILING_` are reported, as
  `ConstantNotUpperSnakeCase`. It covers class constants, global constants from `const` or `define()`, and an enum
  constant that isn't in PascalCase. PERCS31 still allows them.
- **`PERCS31.Namespaces.GroupUseLayout` (breaking).** A group `use` split across lines must have one name per line, with
  the first on the line after the `{`, and the `}` on a line of its own, as PER Coding Style 3.1's example shows.
  `use A\{B,` then `C,` then `};` passed. Auto-fixable, unless a comment is in the way.
- **`PERCS31.Classes.ConstantDeclaration` (breaking).** Requires a single space after a class constant's `const`
  keyword, and between its type and its name. `public const  int  A = 1;` passed. PER Coding Style 3.1 sets this spacing
  for keywords and for a property's type, and it's read as covering a constant too. Auto-fixable.
- **A line indented 1 to 3 spaces too far (breaking).** `PERCS31.WhiteSpace.ConsistentIndent` now reports a statement
  indented 5, 6 or 7 spaces in a body indented 4, and the like. Only a whole extra level was reported. Auto-fixable.
- **A comment indented too far, in Jumbo (breaking).** `PERCS31.WhiteSpace.ConsistentIndent` has a new `checkComments`
  property, which Jumbo turns on. A comment indented further than its scope after a statement is reported as `Comment`.
  Auto-fixable for a comment on one line.
- **Several traits in a `use` statement with a block (breaking).** `use A, B { ... }` in a class is now reported, as PER
  Coding Style 3.1 gives each trait its own `use` statement. `SlevomatCodingStandard.Classes.TraitUseDeclaration` makes
  the check, so `PSR12.Traits.UseDeclaration.MultipleImport` is now
  `SlevomatCodingStandard.Classes.TraitUseDeclaration.MultipleTraitsPerDeclaration`. `use A, B;` is still fixed.
- **A short hook on the opening brace's line (breaking).** `PERCS31.Classes.PropertyHookLayout` now reports a hook that
  shares the `{`'s line in a list over several lines, as in `{ get => 1;` then `set => $value;`, as `HookNotOnOwnLine`.
  PER Coding Style 3.1 only lets a single short hook that doesn't wrap be inline. The fix for
  `{ get => 1; set => $value; }` used to leave that layout. Auto-fixable.
- **Code after a hook list's closing brace (breaking).** `PERCS31.Classes.PropertyHookLayout` now reports
  `} public int $b = 1;` after a hook list, as `CodeAfterCloser`, as PER Coding Style 3.1 puts that brace on its own
  line. Auto-fixable, unless a comment is in the way.
- **A parameter list split only by an attribute (breaking).** `function a(#[Foo]` then `$b)` is now reported by
  `PERCS31.Functions.MultiLineFunctionDeclaration`, and fixed to one parameter per line, with `) {` on a line of their
  own. It was read as a single-line declaration, so it passed. Auto-fixable.
- **No trailing comma after a single argument split over lines (breaking).** `foo([` ... `],);` is now reported, as PER
  Coding Style 3.1 says a single argument split across lines doesn't split the argument list, and only a split list
  takes a trailing comma. The new `PERCS31.Functions.DisallowTrailingCommaInCall` replaces
  `SlevomatCodingStandard.Functions.DisallowTrailingCommaInCall`, so a `phpcs:ignore` for the Slevomat code needs
  updating. `PERCS31.Formatting.TrailingComma` applies the same rule to an anonymous class's arguments, `clone()`,
  `exit()` and `die()`. Auto-fixable.
- **An empty control structure body as `{}`.** PERCS31 now allows `catch (Exception $e) {}`, `while (next($a)) {}` and
  the like, which were errors, as PER Coding Style 3.1 has no rule for a control structure's empty body, and writes
  every other empty body as `{}`. The braces on separate lines still pass. The new
  `PERCS31.ControlStructures.BodyOnNextLine` replaces `Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace`,
  so a `phpcs:ignore` for that code needs updating.
- **`{}` required for an empty control structure body, in Jumbo (breaking).** `PERCS31.Formatting.EmptyBody` has a new
  `checkControlStructures` property, which Jumbo turns on. An empty `catch`, `while`, `if` or other body with the braces
  on separate lines is an error there. Auto-fixable.
- **Blank lines around trait `use` statements, in PERCS31 (breaking).** PERCS31 now reports a blank line between two
  trait `use` statements, which only Jumbo did, and more than one after the last, as PER Coding Style 3.1's examples
  show none between them and one after. `SlevomatCodingStandard.Classes.TraitUseSpacing` makes these checks, so
  `PSR12.Traits.UseDeclaration.NoBlankLineAfterUse` and `BlankLineAfterLastUse` are now
  `SlevomatCodingStandard.Classes.TraitUseSpacing.IncorrectLinesCountAfterLastUse`. Auto-fixable.
- **A property hook's name in lower case (breaking).** `GET => 1;` and `Set(int $value) => $value;` are now reported, as
  PER Coding Style 3.1 requires keywords in lower case, and calls a hook's name "the hook keyword". The new
  `PERCS31.PHP.LowerCaseKeyword` replaces `Generic.PHP.LowerCaseKeyword`, and checks it too, so a `phpcs:ignore` for
  `Generic.PHP.LowerCaseKeyword.Found` needs updating. Auto-fixable.
- **`PERCS31.Classes.AnonClassOpeningBrace` (breaking).** Requires one space before an anonymous class's opening brace
  when it's on the declaration's line, and nothing after the brace on its line, as PER Coding Style 3.1's examples show.
  `new class extends Foo{` and `new class { public int $a;` passed. Auto-fixable.
- **At least one space around an array's `=>` (breaking).** `PERCS31.WhiteSpace.ArrowSpacing` now checks arrays in
  PERCS31, as PER Coding Style 3.1's array example has a space each side, using the new property
  `allowSeveralSpacesInArray`, so arrows can still be lined up. Jumbo still requires exactly one. Auto-fixable.
- **`Universal.Constants.UppercaseMagicConstants` in Jumbo (breaking).** A magic constant must be in upper case, as in
  `__DIR__`. PHP ignores their case, and PERCS31 doesn't check it. Auto-fixable.
- **At most one blank line between hooks (breaking).** `PERCS31.Classes.PropertyHookLayout` now reports two or more
  blank lines between the hooks of a list over several lines, as `TooManyBlankLinesBetweenHooks`, as PER Coding Style
  3.1 allows no blank line or one. Auto-fixable, unless a comment is between the hooks.
- **An import after the rest of the code (breaking).** `PERCS31.Files.FileHeader` now reports a `use` import after the
  file's code, as `ImportAfterCode`, as PER Coding Style 3.1 puts imports in the header. A file with more than one
  namespace is skipped. Not auto-fixable.
- **`PERCS31.Functions.FunctionCallLayout` replaces `PSR2.Methods.FunctionCallSignature` (breaking).** Its codes are now
  `PERCS31.Functions.FunctionCallLayout.*`, so a `phpcs:ignore` for the old ones needs updating. It also checks
  `clone()`'s argument list, as PER Coding Style 3.1 calls it a function, skipping parentheses that only group the
  object, as in `clone ($a)->b`. And it no longer reads a `set` hook's parameter list as a call. Auto-fixable.
- **A `set` hook's parameter list spaced like a method's (breaking).** `PERCS31.Classes.PropertyHookLayout` reports
  `set (string $value)` and `set( string $value )`, as `SpaceBeforeHookParameters` and `SpaceInsideHookParameters`, as
  PER Coding Style 3.1's examples show `set(string $value)`. Auto-fixable.
- **A backed enum's type in lower case (breaking).** `PERCS31.Classes.ClassDeclaration` now reports `enum Suit: STRING`,
  as PER Coding Style 3.1 requires types in lower case, and `Generic.PHP.LowerCaseType` doesn't check a backing type.
  Auto-fixable.
- **`PERCS31.TypeHints.NullType` (breaking).** `null` must be the last type in a union everywhere a type can appear, as
  PER Coding Style 3.1 requires: a type with an intersection, such as `null|(A&B)`, a typed constant, and a `set` hook's
  parameter are now checked, and `null|int` is an error, not just the `?T` warning. A warning recommends `?T` in a typed
  constant or a `set` hook's parameter, an error in Jumbo. Auto-fixable, except a split type.
- **Single-line rules for statements, signatures and class headers (breaking).** In the Jumbo standard, new
  `Jumbo.Formatting.RequireSingleLineStatement`, `Jumbo.Functions.RequireSingleLineSignature` and
  `Jumbo.Classes.RequireSingleLineClassHeader` require code split across lines to be on one line, if it fits. With a
  closure, `match` or anonymous class body, or a call or array too long for one line, the lines around it are joined. A
  chain of two or more methods is left alone, and so is a comment or a heredoc. Auto-fixable. PERCS31 doesn't check
  this, as PER allows the splits.
- **`Jumbo.WhiteSpace.LanguageConstructSpacing` (breaking).** In the Jumbo standard, a language construct such as
  `return`, `echo` or `throw` must be followed by a single space, so no line break. `new` and a `yield` with no value
  are left out. Auto-fixable. PERCS31 doesn't check this.
- **`Jumbo.PHP.EmbeddedPhp` (breaking).** In the Jumbo standard, a multi-line PHP block in a file with markup must have
  nothing after `<?php` on its line, and nothing before `?>` on its line, with `?>` level with the line `<?php` is on.
  The code is level with that line, or one level past it if markup comes before `<?php`. A short echo tag must be on one
  line. A tag on one line is exempt. Auto-fixable, except a short echo tag. PERCS31 doesn't check this.
- **`SlevomatCodingStandard.Variables.UnusedVariable` (breaking).** In the Jumbo standard, a variable that's assigned
  but never used is now an error. Not auto-fixable.
- **`PERCS31.WhiteSpace.ConsistentIndent` (breaking).** Reports a line indented further than its scope after a complete
  statement, as PER Coding Style 3.1 requires an indent of 4 spaces per level. It extends php-collective's
  `ConsistentIndent`, adding the pipe operator and the other binary operators to the lines it treats as continuation
  lines. `php-collective/code-sniffer` is now a dependency. It also checks a value wrapped after an array's `=>`, a
  group `use`'s items, and dead code in a `switch`, each indented exactly. Auto-fixable.
- **`Squiz.PHP.NonExecutableCode` in Jumbo.** A warning for code that can never run, such as code after a `return`.
- **`Universal.ControlStructures.DisallowAlternativeSyntax` (breaking).** The alternative syntax for a control
  structure, such as `if ($a): ... endif;`, is now reported directly, as PER Coding Style 3.1 says a structure's body
  MUST be in braces. It was only reported as a spacing error before. Auto-fixable, including in a file that mixes PHP
  and HTML.
- **`PERCS31.NamingConventions.AcronymCasing`.** A warning for an acronym in capitals in a name, e.g. `getID()` or
  `$userID`, as PER Coding Style 3.1 recommends writing acronyms like words. It covers classes, interfaces, traits,
  enums, enum cases, namespaces, functions, methods, properties, parameters, variables, import aliases, trait aliases,
  and enum and global constants with a lower-case letter. An acronym is a word of two or more capitals, so a single
  capital, as in `ClassA` or `getX`, isn't reported. Set `excludedNames`, a comma-separated list, to allow whole names.
- **`PERCS31.NamingConventions.ValidClassName` (breaking).** Replaces `Squiz.Classes.ValidClassName`. Class, interface,
  trait and enum names must be in PascalCase: a capital first, only letters and digits, and at least one lower-case
  letter. So `FOO`, `A` and `B2B` are now errors. An acronym in capitals, as in `HTTPClient`, gets the `AcronymCasing`
  warning instead. Not auto-fixable.
- **`Jumbo.TypeHints.DeclareStrictTypes` writes `strict_types=1`.** Its fix wrote `strict_types = 1`, which PER Coding
  Style 3.1 forbids. For a file starting with a block `declare`, it adds a separate statement on the line above, as
  `strict_types` in one is a fatal error.
- **`Jumbo.ControlStructures.MatchArmWrapIndent` (breaking).** In the Jumbo standard, a `match` arm must be on one line
  up to its value if it fits, and otherwise indented one level past its first line. A comma before an arm's `=>` is also
  an error there, even with its conditions split over lines (new `PERCS31.Formatting.TrailingComma` property
  `disallowInSplitMatchConditions`). Auto-fixable.
- **`Jumbo.ControlStructures.StatementAfterClosingBrace` (breaking).** A control structure's closing brace must be on
  its own line in the Jumbo standard, so `} stop();` is an error. A comment such as `} // for $i` is allowed, and so is
  what continues the structure, such as `} else {`. Auto-fixable.
- **`PERCS31.ControlStructures.MatchArmPlacement` (breaking).** Each `match` arm must start on a new line, as PER Coding
  Style 3.1, section 5.2, shows. Auto-fixable.
- **Comma spacing in every list (breaking).** As PER Coding Style 3.1's examples show, a comma has no space before it
  and one after, in any list: an array, including one nested in a multi-line array, a short list, group `use`, `match`
  conditions, `clone()` arguments, and others such as `echo 1, 2;`. Uses the general codes of
  `Universal.WhiteSpace.CommaSpacing`. Auto-fixable.
- **No space inside a single-line array's brackets (breaking).** In PERCS31, as in Jumbo, as section 11 shows. Uses
  `SlevomatCodingStandard.Arrays.SingleLineArrayWhitespace`, now in PERCS31. Auto-fixable.
- **A one-line ternary is no longer read as spanning lines.** `PERCS31.ControlStructures.MultiLineTernaryOperator`
  reported and split one after `+=`, `.=`, `echo`, `print` or `yield`, as a statement, or in a `match` arm.
- **A multi-line array can go in a `foreach` header.** `foreach ([` ... `] as $x) {` was reported, and `phpcbf` rewrote
  it into a form that breaks PER Coding Style 3.1, section 11. `PERCS31.ControlStructures.ControlStructureSpacing`
  replaces `PSR12.ControlStructures.ControlStructureSpacing`, so update any `phpcs:ignore` comments or `<exclude>` rules
  that name it.
- **A comment on its own line between an attribute and its parameter is an error (breaking).** Reported by
  `PERCS31.Attributes.AttributePlacement`. Not auto-fixable.
- **An attribute can be on a short property hook's line.** Section 12.2 of PER Coding Style 3.1 doesn't list a hook, and
  a short hook, as in `#[A] get => 1;`, fits on a line. An attribute on a hook's parameter now follows the parameter
  rule, instead of being reported.
- **An attribute on a value in a multi-line array is allowed.** `#[Pure] static fn() => 1,` on a line of its own was
  reported as two values on one line, as `Squiz.Arrays.ArrayDeclaration`'s `ValueNoNewline` check read the attribute as
  an earlier value. The new `PERCS31.Arrays.ValuePerLine` replaces that check.
- **Two or more attribute blocks put their target on the next line (breaking).** As each block must be on its own line,
  `#[A] #[B] case X;` is now an error in both standards, as is `#[A] #[B]` on a property hook, which was missed. In a
  single-line parameter or argument list, blocks stay inline, each followed by a single space. One block holding several
  attributes can stay inline on a short hook, as in `#[A, B] get => 1;`. Uses
  `PERCS31.Attributes.DisallowMultipleAttributesPerLine`, which replaces the Slevomat sniff of the same name.
  Auto-fixable.
- **One space after an attribute on its target's line (breaking).** On a short property hook or a closure outside a
  call, `PERCS31.Attributes.AttributePlacement` requires a single space, as PER Coding Style 3.1 does for a parameter in
  a single-line list. An enum case is treated as a constant, and a hook with a body in braces, as in `get {`, as a
  method, so the attribute on either must be on the line before. Auto-fixable.
- **A default value's `=` can be aligned in PERCS31.** It's a binary operator, so PER Coding Style 3.1 only requires at
  least one space. `PERCS31.Operators.OperatorSpacing` now checks it, with the new property `checkDefaultValues`, in
  place of `Squiz.Functions.FunctionDeclarationArgumentSpacing`. The Jumbo standard still requires exactly one space.
- **A hook's own `{` must be one space after its name (breaking).** `get{`, `get  {` and `{` on the next line are errors
  in `PERCS31.Classes.PropertyHookLayout`, as PER Coding Style 3.1's examples show `get {`. Auto-fixable, unless a
  comment is in the way.
- **A multi-line `case` condition must start on the next line and be indented (breaking).**
  `PERCS31.ControlStructures.SwitchDeclaration` reports `case ($a`, and a line not indented past the `case`, as PER
  Coding Style 3.1, section 5.2, requires. Auto-fixable, unless a comment is in the way.
- **One blank line is allowed between parameters in PERCS31.** PER Coding Style 3.1, section 12.4's example has one, so
  `PERCS31.Functions.MultiLineFunctionDeclaration` reports any other blank line in a parameter or closure `use` list as
  `BlankLine`, not every one, as `EmptyLine` did. A blank line between call arguments is still reported, as 4.7's
  examples have none.
- **A body's first line indented too far is an error (breaking).** `PERCS31.WhiteSpace.ScopeIndent` now reports it,
  including in a closure, a `case` or a property hook. Auto-fixable.
- **A `match` arm indented too far is an error (breaking).** `PERCS31.WhiteSpace.ScopeIndent` requires a line starting
  an arm to be indented exactly once, as PER Coding Style 3.1, section 5.2, shows. It only reported too little before.
  Auto-fixable.
- **`Jumbo.Classes.ConstantSpacing`, `EnumCaseSpacing` and `PropertySpacing`.** Replace Slevomat's sniffs of the same
  names, which end the whole run with a fatal error on a member on a file's last line, e.g. `public int $a = 1; }`.
  Update any `phpcs:ignore` comments or `<exclude>` rules that name them.
- **`PERCS31.Arrays.ArrayBracketSpacing` (breaking).** A blank line before the closing bracket of a multi-line array is
  an error, as PER Coding Style 3.1 requires it on the next line after the last value. It extends php-collective's
  `ArrayBracketSpacing`, skipping a destructuring assignment. Auto-fixable, except after a comment.
- **`PERCS31.Files.DeclareStatement` (breaking).** Replaces `PSR12.Files.DeclareStatement`, which rejected a declare
  statement with several directives, such as `declare(ticks=1,strict_types=1);`, or a string value, such as
  `declare(encoding='UTF-8');`, however it was spaced. As PER Coding Style 3.1 requires, there must be no spaces inside
  the parentheses, including after a comma. Its codes change prefix from `PSR12.Files.DeclareStatement` to
  `PERCS31.Files.DeclareStatement`. It also fixes `declare(...){`, with no space before the brace, which ran `phpcbf`
  out of memory. Auto-fixable.
- **`Zend.Files.ClosingTag` (breaking).** In the Jumbo standard, a file that mixes PHP and HTML must not end in a
  closing tag either. It replaces `PSR2.Files.ClosingTag`, which only checks PHP-only files, so the code on those files
  changes from `PSR2.Files.ClosingTag.NotAllowed` to `Zend.Files.ClosingTag.NotAllowed`. Auto-fixable.
- **`Jumbo.Arrays.ElementComma` (breaking).** In a multi-line array or destructuring list, the comma after an element
  must be on the element's last line, keyed or not, in the Jumbo standard. It replaces Squiz `ArrayDeclaration`'s
  `NoComma`, which only checked a keyed element, and reported a heredoc element written `EOT,`, as PHP 7.3 and later
  allow. Auto-fixable.
- **`SlevomatCodingStandard.Arrays.TrailingArrayComma` in PERCS31.** Requires the trailing comma after a multi-line
  array's last element, keyed or not, as PER Coding Style 3.1 requires, including after a heredoc. It replaces Squiz
  `ArrayDeclaration`'s `NoCommaAfterLast` and `NoComma`, between which the trailing comma was checked before.
- **`PERCS31.TypeHints.CompoundTypeLayout` (breaking).** As PER Coding Style 3.1 requires, a compound type split across
  lines has one type per line, or for a DNF type, one union segment per line with its intersections kept together.
  Auto-fixable.
- **Continuation line indent (breaking).** As PER Coding Style 3.1 requires, a line starting with a wrapped operator,
  including `.` and an arrow function's `=>`, must be indented once past the line its expression starts on. That
  includes a list item, such as a call's argument, array element or `match` arm, and grouping parentheses. In a control
  structure's condition, a line must be indented at least once past the line with the `(`. Jumbo requires exactly once
  for `&&` and `||`, and once past the operand's line for any other operator (new property
  `requireExactConditionIndent`). A `case` condition isn't checked. New code
  `PERCS31.Operators.OperatorPlacement.Indent`. Auto-fixable.
- **A short ternary must be written `?:` (breaking).** A space, line break or comment between a short ternary's `?` and
  `:`, as in `$a ? : $b`, is an error (new code `PERCS31.Operators.OperatorPlacement.SplitShortTernary`), as PER Coding
  Style 3.1 treats `?:` as a binary operator, and its examples write it as one. Auto-fixable, unless there's a comment
  between them.
- **`PERCS31.Functions.ArrowFunctionSemicolonSpacing` (breaking).** No space before the semicolon that ends an arrow
  function, as PER Coding Style 3.1 requires. Auto-fixable.
- **Arrow function parameter lists (breaking).** `PERCS31.Functions.MultiLineFunctionDeclaration` now checks an arrow
  function's split parameter list, as PER Coding Style 3.1 requires: the first parameter on the next line, one parameter
  per line, each indented once, and the closing parenthesis on a line of its own. Auto-fixable.
- **Method chain layout (breaking).** As PER Coding Style 3.1 requires, in a method chain split across lines, each `->`
  or `?->` must start its line, indented once, and the first method must be on the next line, not the object's line.
  Adds `PEAR.WhiteSpace.ObjectOperatorIndent` and the new `PERCS31.Formatting.MethodChaining`. Auto-fixable.
- **`PERCS31.Formatting.TrailingComma` (breaking).** As PER Coding Style 3.1 requires, the last arm of a multi-line
  `match`, the last name in a multi-line group `use`, the last item in a multi-line `list()`, and the last argument in
  multi-line arguments to an anonymous class, `clone()`, `exit()` or `die()` must have a trailing comma, and one on a
  single line is an error, including in a `match` arm's conditions. Auto-fixable.
- **`Universal.Attributes.TrailingComma` (breaking).** A trailing comma in an attribute block on one line is an error,
  and so is a missing one in a split block of several attributes, as PER Coding Style 3.1 requires. Auto-fixable.
- **Blank lines and spacing around attributes (breaking).** As PER Coding Style 3.1 requires,
  `PERCS31.Attributes.AttributePlacement` now reports a blank line between a docblock and the attributes after it, or
  between two attribute blocks, and anything but a single space between an attribute and its parameter on the same line.
  A closure that's a call's argument follows the parameter rule: its attribute is inline after a single space when the
  argument list is on one line, and on its own line before it when the list is split. Auto-fixable.
- **Spacing inside attribute blocks (breaking).** As PER Coding Style 3.1 requires, there's no space after `#[` or
  before `]`, and attributes in one block are separated by a comma with a space after it and none before, e.g.
  `#[Foo, Bar]`. Adds PHPCSExtra's `Universal.Attributes.BracketSpacing`, and the attribute block codes of
  `Universal.WhiteSpace.CommaSpacing`. Auto-fixable.
- **`PERCS31.Strings.HeredocIndentation` (breaking).** A heredoc's or nowdoc's body and closing marker must be indented
  once past the line it starts on, as PER Coding Style 3.1 requires. Auto-fixable, keeping any extra indentation within
  the text. A heredoc or nowdoc must also begin on the line of its context, so `return` or `$x =` followed by `<<<` on
  the next line is an error, and so is `$x` followed by `= <<<`. Auto-fixable, except a comment in the way.
- **`PERCS31.Classes.PropertyHookLayout` (breaking).** Checks the layout of a property's hooks, as PER Coding Style 3.1
  requires: the opening brace on the property's line after a single space, the closing brace of a multi-line list on its
  own line with no comment after it, a single space either side of a short hook's `=>` with its body on the same line,
  and a single space inside the braces of an inline hook. In a list with a `{}` body, each hook starts on its own line,
  so `get { return $this->a; }` must be split over three lines. A list with no bodies, as on an interface or abstract
  property, must be on one line as `{ get; set; }`, and a bodiless hook has no space before its `;`, as section 4.10
  requires. Auto-fixable, except a comment after the closing brace, or a comment in the way. It also reports a space in
  a by-reference hook, `& get`, as for a by-reference parameter.
- **No blank line between declare statements, including a block `declare` (breaking).** As PER Coding Style 3.1
  requires, `declare(strict_types=1);` then a blank line then `declare(ticks=1) {` is now an error, in PERCS31 and
  Jumbo. Auto-fixable. Jumbo no longer wants a blank line after `strict_types` when another declare statement follows.
- **`<?php declare(strict_types=1) ?>` above markup is allowed.** As PER Coding Style 3.1 says, a file with markup
  outside PHP tags declares strict types on its first line, between its own tags, with or without the `;`.
  `PERCS31.Files.FileHeader` and `Jumbo.TypeHints.DeclareStrictTypes` replace `PSR12.Files.FileHeader` and
  `SlevomatCodingStandard.TypeHints.DeclareStrictTypes`, which required blank lines around it. Update any `phpcs:ignore`
  comments or `<exclude>` rules that name the old codes. In a file with markup, a `strict_types` declaration anywhere
  else is now an error, as PER requires, and Jumbo's fix for a missing one adds the first-line form. Auto-fixable. A
  file with markup before its first `<?php`, such as a template, isn't checked for a file header, as it has none.
- **`PERCS31.TypeHints.CompoundTypeOperatorSpacing`.** A union, intersection or DNF type can now be split across lines,
  with the `|` or `&` starting each line, as PER Coding Style 3.1 allows. It replaces
  `SlevomatCodingStandard.TypeHints.DNFTypeHintFormat.DisallowedWhitespaceAroundOperator`, which counted the line break
  as a space. Spaces around the symbol on one line, or the symbol at the end of a line, are still errors. Auto-fixable.
- **One blank line before a parameter's attributes is allowed.** As PER Coding Style 3.1 allows, a multi-line parameter
  list can have a blank line between a parameter and the attributes of the next.
  `PERCS31.Functions.MultiLineFunctionDeclaration` now reports other blank lines in the list itself, as `BlankLine`, in
  place of the parent's `EmptyLine`, which reported them all. Update any `phpcs:ignore` comments or `<exclude>` rules
  that name `EmptyLine`.
- **`PERCS31.NamingConventions.UpperCaseConstantName`.** Replaces `Generic.NamingConventions.UpperCaseConstantName`,
  which required an enum constant in UPPER_CASE. As PER Coding Style 3.1 allows, an enum constant can now be in
  PascalCase, and an UPPER_CASE one gets a warning, as PascalCase is recommended. Jumbo hides that warning, at a
  severity of 4, as UPPER_CASE is its house style. Any other class constant still must be in UPPER_CASE. PERCS31 no
  longer checks a global constant, from `const` or `define()`, as PER doesn't require UPPER_CASE for it. Jumbo still
  does, by setting `checkGlobalConstants`, and now requires it of an alias of a constant too. A global `const` is
  reported as `Constants must be uppercase`, with the `ConstantNotUpperCase` code of a `define()` constant, not as a
  class constant.
- **`PERCS31.Files.LineLength`.** Replaces `Generic.Files.LineLength`. A line over 120 characters still gets a `TooLong`
  warning, and a line over 80 now gets an `OverRecommended` warning with a severity of 4, as PER Coding Style 3.1 says
  lines SHOULD NOT be longer than 80. It's hidden by default; run `phpcs --warning-severity=4` to see it. Update any
  `phpcs:ignore` comments or `<exclude>` rules that name `Generic.Files.LineLength`.
- **The PERCS31 ruleset.** A second ruleset in the package, for [PER Coding Style 3.1](https://www.php-fig.org/per/coding-style/) and nothing more, built on PSR-12.
  Use `<rule ref="PERCS31"/>` to follow PER Coding Style without the extra rules. The Jumbo ruleset is PERCS31 plus
  extra rules. PERCS31's custom sniffs are `PERCS31.*`, and the ones Jumbo adds are `Jumbo.*`. See [PERCS31](docs/PERCS31.md).
- **`PERCS31.Operators.OperatorSpacing` (breaking).** Replaces `PSR12.Operators.OperatorSpacing` and
  `Squiz.WhiteSpace.OperatorSpacing`. It requires at least one space around a binary operator, and adds the pipe
  operator `|>`. The Jumbo standard requires exactly one, as before, and now also around `&&`, `||`, `and`, `or`, `xor`
  and `.`, e.g. `$a  &&  $b` is now an error. Auto-fixable. The `|` in a multi-catch is a union type, so it must have no
  spaces, e.g. `catch (A|B $e)`, as PER Coding Style 3.1 requires; PSR-12's sniff required them. That's checked by
  `PhpCollective.WhiteSpace.CatchTypeSeparatorSpacing`. `$x=&$a`, which PSR-12's sniff skips, is also an error now.
- **`Jumbo.Functions.RequireSingleLineCall`.** Replaces `SlevomatCodingStandard.Functions.RequireSingleLineCall`, which
  reported a call with a multi-line anonymous class argument, e.g. `consume(new class {` ... `});`, and fixed it by
  putting the class body on one line. Such a call is now skipped, as one with a closure argument already was. So is a
  call with a `match` argument, where the same fix stopped `phpcbf` finishing.
- **`PERCS31.Arrays.OpeningBracketPlacement` (breaking).** The opening bracket of a multi-line array must not be on a
  line of its own, as PER Coding Style 3.1 requires, e.g. `return [`, not `return` then `[` on the next line. An array
  that's an element of a multi-line array is allowed, and so is one that's an argument in an argument list, or a
  condition of a `match` arm. Auto-fixable.
- **The pipe operator `|>` (PHP 8.5) (breaking).** As PER Coding Style 3.1 requires, `|>` needs a space either side, and
  starts the line when a statement wraps at it. `PERCS31.Operators.OperatorSpacing` checks the spacing, and
  `PERCS31.Operators.OperatorPlacement` now covers `|>`. Only checked when PHPCS runs on PHP 8.5. Auto-fixable.
- **`PERCS31.ControlStructures.SwitchDeclaration` (breaking).** Replaces `PSR2.ControlStructures.SwitchDeclaration`,
  adding two rules from PER Coding Style 3.1: the last non-empty case must end with `break`, `return` or similar, and a
  multi-line case condition must be wrapped in parentheses, with `):` on a line of their own. Auto-fixable. A non-empty
  `default` that falls through into another case now needs a comment, as a `case` does. That part isn't auto-fixable.
- **`PERCS31.PHP.RequireCloneParentheses`.** A warning for `clone $a`, which should be `clone($a)`, as PER Coding Style
  3.1 says `clone()` SHOULD be called with parentheses. Auto-fixable.
- **`Generic.Strings.UnnecessaryHeredoc`.** A warning for a heredoc with no interpolation or escape sequences, which
  could be a nowdoc, as PER Coding Style 3.1 says a nowdoc SHOULD be used wherever possible. Auto-fixable.
- **`PERCS31.Attributes.DisallowMultiLineAttributesJoining` (breaking).** An attribute block split over several lines
  must have one attribute per block, as PER Coding Style 3.1 requires. A joined block on one line, such as
  `#[Attribute, Deprecated]`, is still allowed. Auto-fixable.
- **`PERCS31.Classes.EnumMemberVisibility` (breaking).** Requires a non-public method or constant in an enum to be
  `private`, not `protected`, as PER Coding Style 3.1 requires. Auto-fixable.
- **`PERCS31.NamingConventions.ValidEnumCaseName` (breaking).** Requires enum case names to be in PascalCase, as PER
  Coding Style 3.1 requires, e.g. `case BigJoker;`, not `case BIG_JOKER;`, `case bigJoker;` or `case A;`. An enum
  constant must be in PascalCase, by the same rule, or UPPER_CASE. Not auto-fixable.
- **No whitespace after a prefix unary operator (breaking).** A space or line break between a prefix operator and its
  operand is now an error for `!` (`Generic.Formatting.SpaceAfterNot`, with `spacing` set to 0), and for `+`, `~` and
  `@` (`Jumbo.Operators.UnaryOperatorSpacing`, a new sniff). `-`, `++`, `--`, `&` and `...` were already checked.
  Auto-fixable.
- **A wrapped operator must start the next line (breaking).** As PER Coding Style 3.1 requires, a statement that wraps
  at an operator has the operator at the start of the new line, not the end of the previous one, e.g. `$total = $a` then
  `+ $b` on the next line. Three sniffs check this:
  - `PERCS31.Operators.OperatorPlacement`, a new sniff, for assignment, arithmetic, comparison, bitwise and logical
    operators, `??`, `instanceof`, `?:` and an arrow function's `=>`.
  - `Universal.Operators.ConcatPosition`, for `.`.
  - `PSR12.ControlStructures.BooleanOperatorPlacement`, now with `allowOnly` set to `first`, for `&&` and `||` in a
    condition.

  Auto-fixable.
- **`Jumbo.Namespaces.UnusedUses`.** Replaces `SlevomatCodingStandard.Namespaces.UnusedUses`, which reads
  `use Function Vendor\helper;` as a class import, and deletes it as unused. This sniff skips a file while its `use`
  keyword is miscased, until `Universal.UseStatements.LowercaseFunctionConst` has fixed it.
- **PHPCSExtra as a base standard (breaking).** `phpcsstandards/phpcsextra` is now required, and six of its sniffs are
  used, each for a PER Coding Style 3.1 rule:
  - `Universal.Attributes.DisallowAttributeParentheses`: no `()` on an attribute with no arguments.
  - `Universal.Classes.DisallowAnonClassParentheses`: no `()` on an anonymous class with no arguments.
  - `Universal.Constants.LowercaseClassResolutionKeyword`: `::class` in lower case.
  - `Universal.PHP.LowercasePHPTag`: `<?php` in lower case.
  - `Universal.PHP.RequireExitDieParentheses`: a warning for `exit` or `die` without parentheses.
  - `Universal.UseStatements.LowercaseFunctionConst`: `function` and `const` in lower case in a `use` statement.
- **`Jumbo.ControlStructures.LanguageConstructWithParentheses`.** Replaces
  `SlevomatCodingStandard.ControlStructures.LanguageConstructWithParentheses`, which removed the parentheses from
  `die()`, against PER Coding Style 3.1. It still reports `echo('x')` and the like.
- **`PERCS31.ControlStructures.MultiLineTernaryOperator` (breaking).** Requires a ternary that spans lines to take
  three, with `?` and `:` each starting a line, indented once, as PER Coding Style 3.1 requires. Replaces
  `SlevomatCodingStandard.ControlStructures.DisallowTrailingMultiLineTernaryOperator`, which only reported a trailing
  `?`. Auto-fixable.
- **`PERCS31.NamingConventions.ValidConstantName` (breaking).** Disallows a constant name that starts with an
  underscore, e.g. `public const int _LIMIT = 10;`, as PER Coding Style 3.1 requires. Not auto-fixable.
- **`SlevomatCodingStandard.Classes.ClassKeywordOrder` (breaking).** Requires `abstract` or `final` before `readonly` on
  a class, e.g. `final readonly class`, not `readonly final class`, as PER Coding Style 3.1 requires. Auto-fixable.
- **`PERCS31.Formatting.EmptyBody` (breaking).** Warns about an empty class or function body that isn't written as `{}`
  on the same line as the declaration, as PER Coding Style 3.1 recommends, e.g. `public function reset(): void {}`. An
  empty closure or anonymous class body that isn't `{}` is an error, as PER 3.1 requires it, e.g.
  `static function (): void {}` or `new class {}`. Auto-fixable.
- **`PERCS31.Formatting.DisallowMultipleStatements`.** Replaces `Generic.Formatting.DisallowMultipleStatements`, which
  reported `{ get; set; }` on an abstract or interface property as two statements on one line. PER Coding Style 3.1
  requires them on one line. Update any `phpcs:ignore` comments or `<exclude>` rules that name the Generic code.
- **`PERCS31.Attributes.AttributePlacement` (breaking).** Requires an attribute to be on the line before its target,
  except on a parameter in a single-line parameter list, where it must be inline, as PER Coding Style 3.1 requires. It
  also reports a blank line, or a comment on a line of its own, between an attribute and its target, replacing
  `SlevomatCodingStandard.Attributes.AttributeAndTargetSpacing`, whose fix deleted such a comment. Attributes on an
  anonymous class follow PER Coding Style 3.1 instead: starting on the line after `new`, indented once, with `class` on
  the line after them. `PERCS31.WhiteSpace.ScopeIndent` indents the class body to match. Auto-fixable.
- **`Jumbo.NamingConventions.ValidVariableName` (breaking).** Warns about a variable or parameter that starts with an
  underscore, inside a class, enum, trait or interface. `Squiz.NamingConventions.ValidVariableName` reports these
  outside a class, but allows them inside one. Superglobals such as `$_GET` are exempt. Not auto-fixable.
- **`Jumbo.ControlStructures.RequireSingleLineHeader` (breaking).** Requires a multi-line `foreach`, `for`, `switch` or
  `match` header to be written on one line if it fits, as Slevomat's `RequireSingleLineCondition` does for `if` and
  `while`. Auto-fixable.
- **`PERCS31.WhiteSpace.ArrowSpacing` (breaking).** Requires one space before and after `=>` in a `match` arm, so arms
  aren't aligned, and at least one in an array. No stock sniff checks these. Jumbo requires exactly one in an array too,
  so array arrows are never aligned, and in a `yield`, which PER Coding Style 3.1 leaves undefined. Auto-fixable.
- **`PERCS31.Classes.PromotedPropertyHooks` (breaking).** A hook on a promoted constructor parameter must be a single
  hook with a short `=>` body, on one line, as PER Coding Style 3.1 requires, e.g.
  `public function __construct(public string $name { set => trim($value); })`. Otherwise, declare the property in the
  class body. Not auto-fixable.
- **`Jumbo.Classes.RequireSingleLineMethodSignature`, `Jumbo.TypeHints.ParameterTypeHint` and
  `Jumbo.TypeHints.ParameterTypeHintSpacing`.** Replace the Slevomat sniffs of the same names, which misread a hook on a
  promoted constructor parameter. They skip a function with one, and check everything else as before.
- **`Squiz.WhiteSpace.ObjectOperatorSpacing` (breaking).** Disallows spaces around `->`, `?->` and `::`, e.g.
  `$a -> b()` or `Foo :: bar()`. A line break before one, in a wrapped method chain, is allowed. Auto-fixable.
- **`PERCS31.Classes.ClosingBrace` (breaking).** Replaces `PSR12.Classes.ClosingBrace`, and also reports a comment after
  a property hook list's or hook body's closing brace on the same line, as PER Coding Style 3.1, section 4, covers
  properties. A phpcs annotation is allowed. Not auto-fixable.
- **`Universal.Constants.ModifierKeywordOrder` (breaking).** In the Jumbo standard, `final` must come before the
  visibility on a constant, as in `final public const A`, the order PER Coding Style 3.1 sets for classes, properties
  and methods. Auto-fixable. PERCS31 allows either order, as section 4.6 doesn't name constants.

### Changed

- **A wrapped operator in grouping parentheses lines up with the group's first line (breaking).** In `$a && (` with `$b`
  and `|| $c` on the lines after it, `PERCS31.Operators.OperatorPlacement` wanted `|| $c` indented once past `$b`. Every
  line in grouping parentheses is now indented once past the line with the `(`, so `|| $c` lines up with `$b`, in a
  condition or any other expression. Auto-fixable, in PERCS31 and Jumbo.
- **A multi-catch's types may be split across lines (breaking).** `catch (` then `A` then `|B $e` then `) {` now passes
  in PERCS31, as PER Coding Style 3.1 lets a union type be split before `|`. It was reported twice, with no fix.
  `PERCS31.TypeHints.CompoundTypeOperatorSpacing` now checks the `|`, and
  `PhpCollective.WhiteSpace.CatchTypeSeparatorSpacing` is no longer used, so `catch (A | B $e)` is reported as
  `PERCS31.TypeHints.CompoundTypeOperatorSpacing.SpaceAroundOperator`. A `|` at the end of a line is now fixed.
- **A leading underscore on a global constant is only reported in Jumbo.** PERCS31 no longer reports `const _FOO = 1;`
  outside a class, as PER Coding Style 3.1's rule is about showing visibility, which a global constant doesn't have.
  `PERCS31.NamingConventions.ValidConstantName` has a new `checkGlobalConstants` property, which Jumbo turns on.
- **`PERCS31.PHP.TypeCast` (breaking).** PER Coding Style 3.1 requires the short form of a type keyword, and PHP 8.5
  deprecates `(double)` and `(binary)` just as it does `(boolean)` and `(integer)`. Both standards now report all four
  with this sniff's `InvalidCastUsed` code. It replaces `PSR12.Keywords.ShortFormTypeKeywords` in PERCS31, and
  `SlevomatCodingStandard.PHP.TypeCast`, which it extends, in Jumbo, so update any `phpcs:ignore` comments or
  `<exclude>` rules that name them. Auto-fixable.
- **A `(binary)` cast is fixed to `(string)`, not deleted.** `SlevomatCodingStandard.PHP.TypeCast`'s fix removed it, as
  that sniff says it has no effect. It's an alias of `(string)`, so `(binary) 5` is `'5'`, and removing it could change
  what the code does.
- **Comma spacing in a call is reported by `Universal.WhiteSpace.CommaSpacing` (breaking).** The codes are now
  `SpaceBeforeInFunctionCall`, `NoSpaceAfterInFunctionCall` and `TooMuchSpaceAfterInFunctionCall`. They replace
  `SpaceBeforeComma`, `NoSpaceAfterComma` and `TooMuchSpaceAfterComma`, from both
  `Generic.Functions.FunctionCallArgumentSpacing` and `PSR12.Classes.AnonClassDeclaration`, so update any `phpcs:ignore`
  comments or `<exclude>` rules that name them. They also report two cases those missed: a comma on the line after a
  heredoc's closing marker, and the spacing around a comma in an array or a `match` inside a call's parentheses, as in
  `foo([1,2])`. Auto-fixable.
- **A class constant with no visibility is an error (breaking).** `PSR12.Properties.ConstantVisibility` only gives a
  warning, but PER Coding Style 3.1 says visibility MUST be declared on all constants, so PERCS31 makes it an error.
- **Renamed the standard from `OceanMoon` to the Jumbo Coding Standard, and the package from `oceanmoon/coding-standard`
  to `mossy2100/coding-standard` (breaking).** The package name matches the author's GitHub and Packagist name. Use
  `<rule ref="Jumbo"/>` in `phpcs.xml`. Sniff codes and class namespaces change to match, e.g.
  `OceanMoon.WhiteSpace.ScopeIndent` is now `PERCS31.WhiteSpace.ScopeIndent`, and `OceanMoon\Sniffs\...` is now
  `PERCS31\Sniffs\...`, or `Jumbo\Sniffs\...` for a sniff only Jumbo uses. Update any `phpcs:ignore` comments or
  `<exclude>` rules that name them.
- **`PERCS31.Classes.ClassInstantiationNoBrackets` is now a warning, and safer.** Renamed from
  `OceanMoon.Classes.ClassInstantiationNoBrackets`. As PER Coding Style 3.1 says SHOULD, `(new Foo())->bar()` is now a
  warning, not an error, fixed to `new Foo()->bar()`. It now covers `::` as well as `->` and `?->`, and only reports
  parentheses holding exactly one `new` expression, so `(new Foo() ?: $default)->bar()` and `new self(new Foo())->bar()`
  are no longer changed.
- **Trailing commas follow PER Coding Style 3.1 (breaking).** A multi-line function call, function declaration or
  closure `use` list now requires a trailing comma, and a single-line one still disallows it. Adds
  `SlevomatCodingStandard.Functions.RequireTrailingCommaInCall`, `...InDeclaration` and `...InClosureUse`. Run `phpcbf`
  to add the commas.
- **Line length is a soft limit (breaking).** A line over 120 characters is now a warning from
  `Generic.Files.LineLength`, PSR-12's own sniff, not an error from `SlevomatCodingStandard.Files.LineLength`, as PER
  Coding Style 3.1 says there must be no hard limit. `phpcs` still exits with a non-zero code on warnings by default. To
  change the limit, set `lineLimit` on the Generic sniff (see `docs/LineLength.md`). Update any `phpcs:ignore` comments
  or `<exclude>` rules that name `SlevomatCodingStandard.Files.LineLength.LineTooLong`.
- **An empty class or function body can be `{}` (breaking).** `PSR2.Classes.ClassDeclaration`,
  `Squiz.Functions.MultiLineFunctionDeclaration`, `Squiz.WhiteSpace.ScopeClosingBrace` and
  `SlevomatCodingStandard.Classes.EmptyLinesAroundClassBraces` rejected it. Each is replaced by a version that allows
  it, e.g. `PERCS31.Classes.ClassDeclaration`, or `Jumbo.Classes.EmptyLinesAroundClassBraces` for the Slevomat one, so
  their error codes change prefix. Update any `phpcs:ignore` comments or `<exclude>` rules that name them.
- **No space after `fn` (breaking).** Arrow functions are now written `fn($x) => $x`, not `fn ($x) => $x`, as PER Coding
  Style 3.1 requires. Run `phpcbf` to reformat.
- **`null` must be last in a union type (breaking).** `int|null` and `null|int|string` are now errors, as PER Coding
  Style 3.1 requires `null` last. Set by `nullPosition` on `SlevomatCodingStandard.TypeHints.DNFTypeHintFormat`.
  Auto-fixable.
- **`PERCS31.Classes.PropertyDeclaration` checks `get` before `set` (breaking).** When a property has both hooks, `set`
  before `get` is now an error (`HookOrder`). PER Coding Style 3.1 requires `{ get; set; }` on an abstract or interface
  property, so PERCS31 only checks a hook list with no bodies, and the Jumbo standard checks every hook list.
  Auto-fixable, unless there's a comment between the hooks.
- **An arrow function may span lines.** Its expression can go on the next line, as PER Coding Style 3.1 allows.
  `SlevomatCodingStandard.Functions.ArrowFunctionDeclaration` previously required it on one line.
- **Array formatting follows PER Coding Style, with no alignment (breaking).** `Jumbo.Arrays.ArrayDeclaration` was
  rewritten to do one thing: collapse a multi-line array that fits on one line. Stock sniffs handle the rest:
  - An array is single-line if it fits, otherwise one element per line. Grid layout is gone, and a single-line
    associative array is now allowed.
  - Trailing commas are required on multi-line arrays and disallowed on single-line ones, as before.
  - Arrows and assignments aren't aligned. A single space is required before `=>` and `=`, since alignment makes noisy
    diffs.

  The added stock sniffs are `Generic.Arrays.ArrayIndent`, `Squiz.Arrays.ArrayDeclaration` (alignment checks excluded),
  `Squiz.WhiteSpace.OperatorSpacing` and `SlevomatCodingStandard.Arrays.MultiLineArrayEndBracketPlacement`. The sniff's
  `indent` property was removed; `maxLineLength` remains. Run `phpcbf` to reformat existing code. Its error codes are
  replaced by one, `Jumbo.Arrays.ArrayDeclaration.ShouldBeSingleLine`. Update any `phpcs:ignore` comments or `<exclude>`
  rules that name an old code, such as `ListShouldBeSingleLine`.
- **`PERCS31.Classes.PropertyDeclaration.LeadingUnderscore` replaces the `Underscore` warning, as an error, and covers
  promoted constructor parameters (breaking).** A property named like `$_name` was a warning, and a promoted one wasn't
  reported at all. PER Coding Style 3.1 says property names must not start with a single underscore, so a double one, as
  in `$__name`, passes, as it does on a method and, in `PERCS31.NamingConventions.ValidConstantName`, a constant. A
  promoted parameter with no visibility, such as `readonly int $a`, is now a `ScopeMissing` error too, as PER requires
  visibility on all properties.
- **Requires `slevomat/coding-standard` 8.27 or later** (was 8.25). Earlier versions report
  `SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification` on `#[Override]` methods
  that inherit their array shape from the parent's docblock, and 8.25.0 also misreports hooked properties as multiple
  property definitions.

### Removed

- **`SlevomatCodingStandard.Arrays.MultiLineArrayEndBracketPlacement` from PERCS31.** It only looks at a nested array
  that starts on its parent's first line, and `PERCS31.Arrays.ValuePerLine` and
  `Squiz.Arrays.ArrayDeclaration.IndexNoNewline` now put every multi-line array's first value on a new line, so it had
  nothing left to report.
- **Four duplicate messages.** Each of these was reported twice. The second report is gone, so a `phpcs:ignore` for it
  can be removed:
  - `PERCS31.Classes.PropertyHookLayout.CommentAfterCloser`, as `PERCS31.Classes.ClosingBrace` reports a comment after a
    hook list's closing brace.
  - `SlevomatCodingStandard.Classes.BackedEnumTypeSpacing.IncorrectSpacesBeforeType`, as
    `PERCS31.Classes.ClassDeclaration` reports the space after an enum's colon.
  - `Squiz.Arrays.ArrayDeclaration.CloseBraceNewLine`, as `Generic.Arrays.ArrayIndent` reports a closing bracket that
    isn't on a new line.
  - `SlevomatCodingStandard.Classes.PropertyDeclaration.WhitespaceAfterNullabilitySymbol`, as
    `PERCS31.Functions.NullableTypeDeclaration` reports a space after a nullable type's `?`.
- **Four Slevomat checks that PERCS31 already covers.** `SlevomatCodingStandard.Classes.TraitUseDeclaration`,
  `SlevomatCodingStandard.Namespaces.NamespaceSpacing`,
  `SlevomatCodingStandard.Namespaces.UseDoesNotStartWithBackslash`, and the before-first check of
  `SlevomatCodingStandard.Classes.TraitUseSpacing`. The same cases are reported by PSR-12's
  `PSR12.Traits.UseDeclaration`, `PSR12.Files.FileHeader` and `PSR12.Files.ImportStatement`. Update any `phpcs:ignore`
  comments or `<exclude>` rules that name the old codes.
- **`SlevomatCodingStandard.Namespaces.UseSpacing`.** `PSR12.Files.FileHeader` already reports, and fixes, the same
  blank lines before, between and after `use` statements. Update any `phpcs:ignore` comments or `<exclude>` rules that
  name a `UseSpacing` code.
- **`SlevomatCodingStandard.Attributes.DisallowAttributesJoining`.** Several attributes in one block, e.g.
  `#[Attribute, Deprecated]`, are now allowed, as PER Coding Style 3.1 allows them.
- **`SlevomatCodingStandard.Functions.DisallowEmptyFunction`.** It required a comment in an empty function body, so PER
  Coding Style 3.1's recommended `public function reset(): void {}` was an error.
- **Seven `PERCS31.Classes.PropertyDeclaration` error codes, which duplicated Slevomat sniffs already in the standard.**
  Each case is still reported, and fixed, by the Slevomat sniff. Remove any references to the old codes:
  - `Multiple`, now reported by `SlevomatCodingStandard.Classes.DisallowMultiPropertyDefinition`.
  - `SpacingAfterType`, `AvizKeywordOrder`, `StaticBeforeVisibility`, `ReadonlyBeforeVisibility`, `FinalAfterVisibility`
    and `AbstractAfterVisibility`, now reported by `SlevomatCodingStandard.Classes.PropertyDeclaration`.
- **`SlevomatCodingStandard.Functions.RequireArrowFunction` from Jumbo.** Its fix rewrote a closure in a constant
  expression, such as `const A = static function () { return 1; };`, as an arrow function, which PHP 8.5 doesn't allow
  there, so the result was a fatal error. A simple closure is no longer required to be an arrow function.
### Fixed

- **A call on one line beside a split chain is left alone.** `PERCS31.Formatting.MethodChaining` took every `->` in a
  statement as one chain, so in `$a->isValid()` then `&& $b` then `->check()`, it reported `->isValid()` as the first
  method of a split chain, and the fix moved it. A chain now ends at an operator, a comma or an enclosing bracket.
- **A braced `case` body before a `default` no longer stops the run.** `PERCS31.ControlStructures.SwitchDeclaration`
  crashed on some `switch` statements with a `case` body wrapped in braces, so the rest of the file wasn't checked,
  and `phpcbf` could end with a fatal error. The braces are now reported, as `WrongOpener`, without the crash. Such a
  `switch` gets no other check from this sniff until the braces are removed.
- **A braced `case` body before another label no longer hangs.** `phpcs` never finished on a `switch` whose braced
  `case` body's `}` shared a line with the next `case` or `default`, as in `} default:`, and `phpcbf` could reach
  that state from other code. `PERCS31.WhiteSpace.ScopeIndent` now gets through it, so the braces are reported.
- **An acronym check for every name but an upper-case constant (breaking).** `PERCS31.NamingConventions.AcronymCasing`
  skipped a class, interface, trait, enum or enum case with no lower-case letter, such as `class HTTP`. It's now
  reported, as a warning, alongside the PascalCase error. Only a constant in upper case is skipped.
- **Two hooks on a promoted parameter are left alone.** `phpcbf` split `{ get => 'a'; set => ucfirst($value); }` on a
  promoted constructor parameter over lines, though `PERCS31.Classes.PromotedPropertyHooks` still said to move the
  property to the class body. `PERCS31.Formatting.DisallowMultipleStatements` now skips a promoted parameter's hooks.
- **An upper-case keyword is reported once (breaking).** `IF`, `FUNCTION`, `PUBLIC` and the like got a second error,
  from `Squiz.ControlStructures.LowercaseDeclaration` or `Squiz.Functions.LowercaseFunctionKeywords`. Both are now
  excluded, as `PERCS31.PHP.LowerCaseKeyword` reports the same keywords, so update any `phpcs:ignore` comments or
  `<exclude>` rules that name them.
- **A `->` at the end of a line (breaking).** `$user = $repository->` then `find($id);` passed, as
  `PEAR.WhiteSpace.ObjectOperatorIndent.StartOfLine` skips a statement's first `->`. It's now reported by
  `PERCS31.Formatting.MethodChaining`, as `OperatorAtEndOfLine`, for every `->` and `?->`, and the PEAR code is
  excluded, so update any `phpcs:ignore` comments or `<exclude>` rules that name it. The fix no longer leaves a blank
  line when the operator is alone on its line. Auto-fixable, unless a comment follows the operator.
- **The space before a `catch`'s variable (breaking).** `catch (Exception  $e)` and `catch (Exception$e)` passed.
  `PERCS31.ControlStructures.ControlStructureSpacing` now requires one space between the type and the variable, as
  `SpaceBeforeCatchVariable`, as PER Coding Style 3.1's example shows. Auto-fixable.
- **A hook's closing brace indented too far (breaking).** `PERCS31.WhiteSpace.ScopeIndent` only reported a property
  hook's `}`, or a hook list's, when it was indented too little. One indented too far is now reported, and fixed.
- **A trailing comma in a call through an expression (breaking).** `$handlers[$name](`, `[$object, 'method'](`,
  `$object->{$method}(` and `'strlen'(` weren't checked, so a split argument list with no trailing comma passed, as did
  a comma on one line. `PERCS31.Formatting.TrailingComma` now checks them. Auto-fixable.
- **`new (expression)` with no argument list (breaking).** `new (className())` passed, though PER Coding Style 3.1
  requires the parentheses of an argument list on every instantiation. The new
  `PERCS31.Classes.DynamicClassInstantiation` reports it, and fixes it to `new (className())()`.
- **A `match` whose subject spans lines.** `match ($this->lookup([` then the argument's lines then `])) {` was reported
  by `PERCS31.ControlStructures.ControlStructureSpacing`, and the fix moved the call onto a line of its own. PER Coding
  Style 3.1 only gives that rule to `if`, `switch`, `while` and `for`, so a multi-line `match` now only has the spaces
  just inside its parentheses checked, as a `foreach` does.
- **A `foreach` split before `as` or `=>` (breaking).** `foreach (` then `$this->items()` then `as $item` on the next
  line was reported, and the fix left that line indented by one space. A line break is now allowed before or after
  either one. The new `PERCS31.ControlStructures.ForEachSpacing` replaces
  `Squiz.ControlStructures.ForEachLoopDeclaration`, so update any `phpcs:ignore` comments or `<exclude>` rules that name
  the old codes. The `=>` of a key in `as ['key' => $value]` is now checked as a list's, by
  `PERCS31.WhiteSpace.ArrowSpacing`.
- **A line wrapped before `::`, or before a `match` arm's `=>`.** After a line ending in `)`,
  `PERCS31.WhiteSpace.ConsistentIndent` reported the wrapped line, and its fix removed the indent. Both now pass.
- **`phpcbf` no longer fails on a split condition that starts with a multi-line array.** In `if (` then `[` on the next
  line, `PERCS31.Arrays.OpeningBracketPlacement` moved the `[` up, and
  `PERCS31.ControlStructures.ControlStructureSpacing` moved it back down. The array may now start the line there, in an
  `if`, `elseif`, `while`, `switch` or `for`, as PER Coding Style 3.1 puts the first condition on the line after the
  opening parenthesis.
- **`$a =` followed by a multi-line array is fixed to `$a = [`.** `phpcbf` gave `$a` then `= [` on the next line, as
  `PERCS31.Operators.OperatorPlacement` moved the `=` down while `PERCS31.Arrays.OpeningBracketPlacement` moved the `[`
  up. The operator sniff now leaves this case to the array sniff.
- **A half-split type is fixed to one indent.** `PERCS31.TypeHints.CompoundTypeLayout`'s fix for `int|string` then
  `|float` on the next line now lines the new break up with the `|` that already starts a line.
- **`null` and `?T` on a static property.** `PERCS31.TypeHints.NullType` now reports `public static null|int $a` and
  `public static int|null $a`. It read the `static` modifier as part of the type, so both passed.
- **`phpcbf` no longer fails on a closure's `use` on its own line.** With `use` on the line after the parameter list's
  closing parenthesis, two inherited fixes undid each other until `phpcbf` gave up. It's now joined to that line, as
  `) use ($var) {`.
- **A type's case in a `set` hook's parameter (breaking).** `set(INT $value)` passed, though PER Coding Style 3.1
  requires types in lower case. The new `PERCS31.PHP.LowerCaseType` replaces `Generic.PHP.LowerCaseType`, and checks it
  too, so a `phpcs:ignore` for a `Generic.PHP.LowerCaseType` code needs updating. Auto-fixable.
- **A trait alias in an anonymous class passed as an argument.** `PERCS31.NamingConventions.AcronymCasing` skipped an
  alias such as `hello as sayHTTPHello` there, as it took any `as` in parentheses for a `foreach`'s. It's now reported.
- **An attribute on a `readonly` anonymous class.** `PERCS31.Attributes.AttributePlacement` didn't see the class behind
  `readonly`, so `new #[A] readonly class {` was fixed to a layout PER Coding Style 3.1 doesn't allow, which then
  passed. The attributes now go on the lines after `new`, with `readonly class` after them, as for any anonymous class.
- **An import in a file that starts with markup (breaking).** `PERCS31.Files.FileHeader` now reports a `use` import in a
  file with markup before its first `<?php`, as `ImportAfterCode`, as PER Coding Style 3.1 says the header's blocks MUST
  be at the top of the file. Such a file was skipped. Not auto-fixable.
- **One value per line in more arrays (breaking).** `PERCS31.Arrays.ValuePerLine` now checks a multi-line array that
  mixes keyed and unkeyed values, as in `'a' => 1, 2,`, and one with a single value on the opening bracket's line, as in
  `[1,` then `];`. Neither was reported, though PER Coding Style 3.1 requires the first value on the next line, and one
  value per line. Auto-fixable.
- **A value after an arrow function in a multi-line array.** `PERCS31.Arrays.ValuePerLine` missed a value on the same
  line as an arrow function before it, as in `fn($x) => $x, 2,`, and an arrow function on the opening bracket's line.
  Both are now reported. Auto-fixable.
- **A closure in a constant's value.** With PHP 8.5's closure in a constant expression, as in
  `const Handler = static function () { $total = 1; ... };`, a variable assigned in the closure was reported as a badly
  named constant, by `PERCS31.NamingConventions.UpperCaseConstantName` and `ValidConstantName`. A constant's name is now
  only looked for outside the value's brackets.
- **A template starting with a PHP tag and a statement.** A file with markup whose only PHP tag is on the first line, as
  in `<?php require 'header.php'; ?>`, was reported by `PERCS31.Files.FileHeader` as `SpacingAfterTagBlock`, and the fix
  split the tag over three lines. It now passes, as PER Coding Style 3.1 lets the tag share its line in a file with
  markup.
- **`phpcbf` no longer breaks a first-class callable split over lines (breaking).**
  `SlevomatCodingStandard.Functions.RequireTrailingCommaInCall` added a comma after the `...` in `foo(` then `...` then
  `)`, a parse error. The new `PERCS31.Functions.RequireTrailingCommaInCall` replaces it, and skips a first-class
  callable, so a `phpcs:ignore` for the Slevomat code needs updating. `PERCS31.Functions.FunctionCallLayout` now reports
  the split, as `SplitFirstClassCallable`, as PER Coding Style 3.1 allows no whitespace around the `...`, and fixes it
  to `foo(...)`.
- **`phpcbf` no longer breaks a `static|null` return type (breaking).** The `?T` fix from
  `SlevomatCodingStandard.TypeHints.DNFTypeHintFormat` changed `static|null` to `?`, a parse error, and `null|static` to
  `?null`. `PERCS31.TypeHints.NullType` now makes the `?T` check for every type, as `ShortNullable`, and fixes both to
  `?static`. A `phpcs:ignore` for `SlevomatCodingStandard.TypeHints.DNFTypeHintFormat.RequiredShortNullable` needs
  updating.
- **`PERCS31.Formatting.EmptyBody` and a comment before the brace (breaking).** An empty body after a comment, as in
  `function () /* Why. */ {` then `};`, was not reported. It now is, and the fix puts `{}` after a block comment. After
  a `//` or `#` comment, it's reported but not fixed.
- **`PERCS31.ControlStructures.SwitchDeclaration` and the `case` line (breaking).** A blank line straight after a `case`
  or `default` label is reported, as PER Coding Style 3.1's example has none, with the code for code on the label's
  line, `BodyOnNextLineCASE` or `BodyOnNextLineDEFAULT`. A comment on the label's line is still allowed. Auto-fixable.
- **`PERCS31.WhiteSpace.ConsistentIndent` checks closures, anonymous classes and `switch` bodies.** A later line
  indented too far in one wasn't reported, in either standard. Auto-fixable.
- **A ternary with a multi-line value.** `PERCS31.ControlStructures.MultiLineTernaryOperator` no longer reports a
  ternary whose value or condition is split over lines, with no line break next to `?` or `:`, such as `$a = $b ? [`
  over several lines to `] : [];`. A ternary split at `?` or `:` must still take the three-line form.
- **PHP indented inside markup.** `PERCS31.WhiteSpace.ConsistentIndent` and `PERCS31.WhiteSpace.ScopeIndent` no longer
  report PHP indented to match the HTML around it, and a block's code may be level with its tags or one level past them.
  The indent of `?>` isn't checked, as PER has no rule on it. A body on the line after `<?php if ($a) {` is now measured
  from the tag, so one indented too little is reported too.
- **A `set` hook's parameter type.** `set(int|string $value)` and `set(A&B $value)` were reported as missing the spaces
  that PER Coding Style 3.1 forbids, and `phpcbf` added them. They now pass, and `set(int | string $value)` is reported
  and fixed by `PERCS31.TypeHints.CompoundTypeOperatorSpacing`. A split type there is checked as it is elsewhere.
  `set(?int $value)` no longer stops the file being checked, in either standard, and `set(? int $value)` is reported and
  fixed. This adds `PERCS31.Functions.NullableTypeDeclaration`, in place of PSR-12's sniff, and in Jumbo,
  `Jumbo.ControlStructures.RequireShortTernaryOperator`, in place of Slevomat's, so update any `phpcs:ignore` comments
  or `<exclude>` rules that name the old codes.
- **`SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition` replaces our fork (breaking).** Slevomat 8.31.3
  fixes what the fork was for, so the minimum Slevomat version is now 8.31.3, and the fork
  `OceanMoon.PHP.DisallowMultiConstantDefinition` is gone. A comma inside parentheses isn't read as a second constant,
  and the fix keeps the type and modifiers, e.g. `final public const int B = 2;`. Update any `phpcs:ignore` comments or
  `<exclude>` rules that name the old code.

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
