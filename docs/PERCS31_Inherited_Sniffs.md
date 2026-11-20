# PERCS31 Inherited Sniffs

The sniffs the [PERCS31](PERCS31.md) standard takes from PHP_CodeSniffer's Generic and Squiz standards, from [Slevomat](https://github.com/slevomat/coding-standard) and from
[PHPCSExtra](https://github.com/PHPCSStandards/PHPCSExtra) (`Universal`), each for a rule in [PER Coding Style 3.1](https://www.php-fig.org/per/coding-style/). Each section summarises what the sniff checks in
PERCS31, including any codes it excludes, and which section of the spec it's for.

PSR-12's own sniffs are covered in [PSR-12](PERCS31.md#psr-12). The sniffs written for PERCS31 are in [PERCS31 Custom Sniffs](PERCS31_Custom_Sniffs.md). Three of those
extend sniffs from [php-collective/code-sniffer](https://github.com/php-collective/code-sniffer): [`PERCS31.Arrays.ArrayBracketSpacing`](PERCS31_Custom_Sniffs.md#percs31arraysarraybracketspacing),
[`PERCS31.ControlStructures.ControlStructureEmptyStatement`](PERCS31_Custom_Sniffs.md#percs31controlstructurescontrolstructureemptystatement) and [`PERCS31.WhiteSpace.ConsistentIndent`](PERCS31_Custom_Sniffs.md#percs31whitespaceconsistentindent).

| Sniff                                                                 | Checks                                                          | Fixable |
| --------------------------------------------------------------------- | --------------------------------------------------------------- | ------- |
| `Generic.Arrays.ArrayIndent`                                          | A multi-line array's elements indented one level, and its `]`   | Yes     |
| `Generic.Arrays.DisallowLongArraySyntax`                              | `[]`, not `array()`                                             | Yes     |
| `Generic.Files.LineLength`                                            | A warning for a line over 120 characters, or over 80            | No      |
| `Generic.Formatting.SpaceAfterCast`                                   | One space after a cast                                          | Yes     |
| `Generic.Strings.UnnecessaryHeredoc`                                  | A warning for a heredoc that could be a nowdoc                  | Yes     |
| `Generic.WhiteSpace.SpreadOperatorSpacingAfter`                       | No white space after a spread `...`                             | Yes     |
| `SlevomatCodingStandard.Arrays.SingleLineArrayWhitespace`             | No space inside a single-line array's brackets                  | Yes     |
| `SlevomatCodingStandard.Arrays.TrailingArrayComma`                    | A trailing comma in a multi-line array or destructuring list    | Yes     |
| `SlevomatCodingStandard.Attributes.RequireAttributeAfterDocComment`   | A doc comment before the attributes, not after                  | Yes     |
| `SlevomatCodingStandard.Classes.BackedEnumTypeSpacing`                | No space before a backed enum's `:`                             | Yes     |
| `SlevomatCodingStandard.Classes.ClassKeywordOrder`                    | `abstract` or `final` before `readonly` on a class              | Yes     |
| `SlevomatCodingStandard.Classes.DisallowMultiPropertyDefinition`      | One property per statement                                      | Yes     |
| `SlevomatCodingStandard.Classes.PropertyDeclaration`                  | A property's modifier order, and the spacing around its type    | Yes     |
| `SlevomatCodingStandard.Classes.TraitUseDeclaration`                  | One trait per `use` statement                                   | Partly  |
| `SlevomatCodingStandard.Functions.ArrowFunctionDeclaration`           | No space after `fn`                                             | Yes     |
| `SlevomatCodingStandard.Functions.DisallowTrailingCommaInClosureUse`  | No trailing comma in a single-line closure's `use` list         | Yes     |
| `SlevomatCodingStandard.Functions.DisallowTrailingCommaInDeclaration` | No trailing comma in a single-line parameter list               | Yes     |
| `SlevomatCodingStandard.Functions.NamedArgumentSpacing`               | No space before a named argument's `:`, and one after           | Yes     |
| `SlevomatCodingStandard.Functions.RequireTrailingCommaInClosureUse`   | A trailing comma in a multi-line closure's `use` list           | Yes     |
| `SlevomatCodingStandard.Functions.RequireTrailingCommaInDeclaration`  | A trailing comma in a multi-line parameter list                 | Yes     |
| `SlevomatCodingStandard.TypeHints.DNFTypeHintFormat`                  | No spaces inside a DNF type's parentheses                       | Yes     |
| `Squiz.Arrays.ArrayDeclaration`                                       | A multi-line array's first value on the next line, one per line | Yes     |
| `Universal.Attributes.BracketSpacing`                                 | No space after `#[` or before `]`                               | Yes     |
| `Universal.Attributes.DisallowAttributeParentheses`                   | No `()` on an attribute with no arguments                       | Yes     |
| `Universal.Attributes.TrailingComma`                                  | No trailing comma in an attribute block                         | Yes     |
| `Universal.Classes.DisallowAnonClassParentheses`                      | No `()` on an anonymous class with no arguments                 | Yes     |
| `Universal.Constants.LowercaseClassResolutionKeyword`                 | `::class` in lower case                                         | Yes     |
| `Universal.Constants.ModifierKeywordOrder`                            | `final` before the visibility on a constant                     | Yes     |
| `Universal.ControlStructures.DisallowAlternativeSyntax`               | Braces, not the alternative syntax, e.g. `if (): endif;`        | Yes     |
| `Universal.PHP.LowercasePHPTag`                                       | The `<?php` tag in lower case                                   | Yes     |
| `Universal.PHP.RequireExitDieParentheses`                             | A warning for `exit` or `die` without parentheses               | Yes     |
| `Universal.UseStatements.KeywordSpacing`                              | One space after `use`, `function` and `const`, and around `as`  | Yes     |
| `Universal.UseStatements.LowercaseFunctionConst`                      | `function` and `const` in lower case in a `use` statement       | Yes     |
| `Universal.WhiteSpace.CommaSpacing`                                   | No space before a comma and one after, in any list              | Yes     |
| `Universal.WhiteSpace.FirstClassCallableSpacing`                      | No white space inside `foo(...)`                                | Yes     |

---

## Generic.Arrays.ArrayIndent

Indents a multi-line array's elements one level, with the closing bracket at the indent of the statement.

**PER Coding Style 3.1:** [Section 11](https://www.php-fig.org/per/coding-style/#11-arrays).

**Auto-fixable:** yes.

---

## Generic.Arrays.DisallowLongArraySyntax

Requires short array syntax `[]` instead of `array()`.

**PER Coding Style 3.1:** [Section 11](https://www.php-fig.org/per/coding-style/#11-arrays).

**Auto-fixable:** yes.

---

## Generic.Files.LineLength

Warns about a line over 120 characters, and, at a severity of 4, about one over 80. PSR-12 includes it with one limit of
120. The sniff has one warning limit, so PERCS31 sets its `lineLimit` to 80 and its `absoluteLineLimit` to 120, and
turns that limit's error into a warning, as PER says there MUST NOT be a hard limit. So a line over 120 is reported as
`MaxExceeded`, and one over 80 as `TooLong`, which `phpcs` only shows with `--warning-severity=4`. See [Line Length](LineLength.md).

**PER Coding Style 3.1:** [Section 2.3](https://www.php-fig.org/per/coding-style/#23-lines).

**Auto-fixable:** no, as only a person can choose where to split a line.

---

## Generic.Formatting.SpaceAfterCast

Requires exactly one space after a cast, e.g. `(int) $value`.

**PER Coding Style 3.1:** [Section 6.1](https://www.php-fig.org/per/coding-style/#61-unary-operators).

**Auto-fixable:** yes.

---

## Generic.Strings.UnnecessaryHeredoc

Warns about a heredoc with no interpolation or escape sequences, which could be a nowdoc.

**PER Coding Style 3.1:** [Section 10](https://www.php-fig.org/per/coding-style/#10-heredoc-and-nowdoc).

**Auto-fixable:** yes.

---

## Generic.WhiteSpace.SpreadOperatorSpacingAfter

Disallows white space, including a line break, after a spread `...`, wherever it's used: a variadic parameter, as in
`int ...$rest`, a call's arguments, as in `foo(...$args)`, and an array, as in `[...$a]`. Section 4.5 states the rule
for a parameter, and it's read as the rule for the operator. A first-class callable's `...`, as in `foo(...)`, isn't a
spread, so it's skipped, and `Universal.WhiteSpace.FirstClassCallableSpacing` checks it (4.8).

**PER Coding Style 3.1:** [Section 4.5](https://www.php-fig.org/per/coding-style/#45-method-and-function-parameters).

**Auto-fixable:** yes, unless a comment follows the `...`.

---

## SlevomatCodingStandard.Arrays.SingleLineArrayWhitespace

Requires no space inside a single-line array's brackets, as section 11's example shows. An empty array's brackets are
checked by `Squiz.Arrays.ArrayDeclaration.SpaceInEmptyArray`. Its comma checks are excluded, as
`Universal.WhiteSpace.CommaSpacing` covers them, including in an array nested in a multi-line one, which this sniff
skips.

**PER Coding Style 3.1:** [Section 11](https://www.php-fig.org/per/coding-style/#11-arrays).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Arrays.TrailingArrayComma

Requires a trailing comma after the last element of a multi-line array or short destructuring list, as in
`[$a, $b] = $c;`, including after a heredoc's closing marker. `list()` is covered by [`PERCS31.Formatting.TrailingComma`](PERCS31_Custom_Sniffs.md#percs31formattingtrailingcomma).

**PER Coding Style 3.1:** [Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Attributes.RequireAttributeAfterDocComment

Requires a doc comment before the attributes, not after.

**PER Coding Style 3.1:** [Section 12.2](https://www.php-fig.org/per/coding-style/#122-placement).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.BackedEnumTypeSpacing

Requires no space before a backed enum's colon. Its check for one space after it is excluded, as
[`PERCS31.Classes.ClassDeclaration`](PERCS31_Custom_Sniffs.md#percs31classesclassdeclaration) reports the same.

**PER Coding Style 3.1:** [Section 9](https://www.php-fig.org/per/coding-style/#9-enumerations).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.ClassKeywordOrder

Requires `abstract` or `final` before `readonly` on a class, e.g. `final readonly class`.

**PER Coding Style 3.1:** [Section 4.6](https://www.php-fig.org/per/coding-style/#46-modifier-keywords).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.DisallowMultiPropertyDefinition

Disallows several properties in one statement.

**PER Coding Style 3.1:** [Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.PropertyDeclaration

Requires the modifier order, and the spacing around the modifiers and the type, including on promoted properties. Its
check for a space after a nullable type's `?` is replaced by [`PERCS31.Functions.NullableTypeDeclaration`](PERCS31_Custom_Sniffs.md#percs31functionsnullabletypedeclaration), which is more
complete: it checks every place a type can appear, including a `set` hook's parameter, not just a property.

**PER Coding Style 3.1:** [Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants), [Section 4.6](https://www.php-fig.org/per/coding-style/#46-modifier-keywords).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Classes.TraitUseDeclaration

Disallows several traits in one `use` statement, as in `use A, B;`. It replaces
`PSR12.Traits.UseDeclaration.MultipleImport`, which misses a statement with a `{ }` block, as in `use A, B {`.

**PER Coding Style 3.1:** [Section 4.2](https://www.php-fig.org/per/coding-style/#42-using-traits).

**Auto-fixable:** partly. A statement with a `{ }` block is reported but not fixed.

---

## SlevomatCodingStandard.Functions.ArrowFunctionDeclaration

Requires no space after `fn`, e.g. `fn($x) => $x`. The expression may be on the next line.

**PER Coding Style 3.1:** [Section 7.1](https://www.php-fig.org/per/coding-style/#71-short-closures).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Functions.DisallowTrailingCommaInClosureUse

Disallows a trailing comma in a single-line closure's `use` list.

**PER Coding Style 3.1:** [Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Functions.DisallowTrailingCommaInDeclaration

Disallows a trailing comma in a single-line parameter list. For a call, [`PERCS31.Functions.DisallowTrailingCommaInCall`](PERCS31_Custom_Sniffs.md#percs31functionsdisallowtrailingcommaincall)
replaces `SlevomatCodingStandard.Functions.DisallowTrailingCommaInCall`.

**PER Coding Style 3.1:** [Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Functions.NamedArgumentSpacing

Requires no space before a named argument's colon, and one after.

**PER Coding Style 3.1:** [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Functions.RequireTrailingCommaInClosureUse

Requires a trailing comma in a multi-line closure's `use` list.

**PER Coding Style 3.1:** [Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.Functions.RequireTrailingCommaInDeclaration

Requires a trailing comma in a multi-line parameter list. For a call, [`PERCS31.Functions.RequireTrailingCommaInCall`](PERCS31_Custom_Sniffs.md#percs31functionsrequiretrailingcommaincall)
replaces `SlevomatCodingStandard.Functions.RequireTrailingCommaInCall`.

**PER Coding Style 3.1:** [Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas).

**Auto-fixable:** yes.

---

## SlevomatCodingStandard.TypeHints.DNFTypeHintFormat

Requires no spaces inside the parentheses of a DNF type. Its check for spaces around `|` and `&` is replaced by
[`PERCS31.TypeHints.CompoundType`](PERCS31_Custom_Sniffs.md#percs31typehintscompoundtype), which allows a split type. Its checks for `null` last and for `?T` are replaced by
that sniff too, which covers every type.

**PER Coding Style 3.1:** [Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types).

**Auto-fixable:** yes.

---

## Squiz.Arrays.ArrayDeclaration

Requires the first value of a multi-line array on the next line, and one value per line, and no trailing comma on a
single-line array. Its other checks aren't PER rules, so they're excluded: alignment, spacing around `=>`, mixing keyed
and unkeyed values, and which line a comma goes on. Its `SpaceInEmptyArray` code is kept, so an empty array is `[]`,
with no space or line break inside. Its check for the closing bracket on a new line is excluded too, as
`Generic.Arrays.ArrayIndent` reports the same. So is its check of the `array` keyword's case, as
[`PERCS31.PHP.LowerCaseKeyword`](PERCS31_Custom_Sniffs.md#percs31phplowercasekeyword) reports the same. Its comma spacing checks never run on a single-line array, so
`Universal.WhiteSpace.CommaSpacing` checks that instead. Its check for a multi-line array's trailing comma is replaced
by `SlevomatCodingStandard.Arrays.TrailingArrayComma`, as it missed a keyed last element, and wanted the comma after a
heredoc on the next line, as before PHP 7.3. Its `ValueNoNewline` check is replaced by
[`PERCS31.Arrays.MultiLineArrayLayout`](PERCS31_Custom_Sniffs.md#percs31arraysmultilinearraylayout), as it read an attribute on a value as an earlier value.

**PER Coding Style 3.1:** [Section 11](https://www.php-fig.org/per/coding-style/#11-arrays), [Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas).

**Auto-fixable:** yes.

---

## Universal.Attributes.BracketSpacing

Disallows a space after `#[` or before `]`, e.g. `#[ Override ]`.

**PER Coding Style 3.1:** [Section 12.1](https://www.php-fig.org/per/coding-style/#121-basics).

**Auto-fixable:** yes.

---

## Universal.Attributes.DisallowAttributeParentheses

Disallows `()` on an attribute with no arguments, e.g. `#[Override()]`.

**PER Coding Style 3.1:** [Section 12.1](https://www.php-fig.org/per/coding-style/#121-basics).

**Auto-fixable:** yes.

---

## Universal.Attributes.TrailingComma

Disallows a trailing comma in any attribute block, including after one attribute whose arguments span lines, e.g.
`#[Foo(` ... `),]`. Sections 12.1 and 12.3 mean a block's list is never split. Its `RequiredMultiAttributeMultiLine`
code is excluded, as 12.3 already rejects a multi-line block of several attributes.

**PER Coding Style 3.1:** [Section 2.6](https://www.php-fig.org/per/coding-style/#26-trailing-commas).

**Auto-fixable:** yes.

---

## Universal.Classes.DisallowAnonClassParentheses

Disallows `()` on an anonymous class with no arguments, e.g. `new class () {}`.

**PER Coding Style 3.1:** [Section 8](https://www.php-fig.org/per/coding-style/#8-anonymous-classes).

**Auto-fixable:** yes.

---

## Universal.Constants.LowercaseClassResolutionKeyword

Requires `::class` in lower case.

**PER Coding Style 3.1:** [Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types).

**Auto-fixable:** yes.

---

## Universal.Constants.ModifierKeywordOrder

Requires `final` before the visibility on a constant, as in `final public const A`.

**PER Coding Style 3.1:** [Section 4.6](https://www.php-fig.org/per/coding-style/#46-modifier-keywords).

**Auto-fixable:** yes.

---

## Universal.ControlStructures.DisallowAlternativeSyntax

Disallows the alternative syntax, e.g. `if ($a): ... endif;`, as a structure's body must be in braces.

**PER Coding Style 3.1:** [Section 5](https://www.php-fig.org/per/coding-style/#5-control-structures).

**Auto-fixable:** yes.

---

## Universal.PHP.LowercasePHPTag

Requires the `<?php` tag in lower case.

**PER Coding Style 3.1:** [Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements).

**Auto-fixable:** yes.

---

## Universal.PHP.RequireExitDieParentheses

Warns about `exit` or `die` without parentheses, as a SHOULD.

**PER Coding Style 3.1:** [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls).

**Auto-fixable:** yes.

---

## Universal.UseStatements.KeywordSpacing

Requires one space after `use`, `function` and `const`, and each side of `as`, in an import, as in
`use Vendor\Package\{ClassA as A, ClassB};`.

**PER Coding Style 3.1:** [Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements).

**Auto-fixable:** yes.

---

## Universal.UseStatements.LowercaseFunctionConst

Requires `function` and `const` in lower case in a `use` statement.

**PER Coding Style 3.1:** [Section 2.5](https://www.php-fig.org/per/coding-style/#25-keywords-and-types).

**Auto-fixable:** yes.

---

## Universal.WhiteSpace.CommaSpacing

Requires no space before a comma and one after, in an attribute block, e.g. `#[Foo, Bar]`, and in any other list, such
as an array, group `use`, `match` conditions or `clone()`, as the examples show. Its codes ending in `InFunctionCall`
check a comma inside a call's parentheses. They replace `Generic.Functions.FunctionCallArgumentSpacing`, which misses a
comma on the line after a heredoc's closing marker, and one in an array or a `match` inside the call, as in
`foo([1,2])`. Its codes for parameter, closure `use` and `declare` lists aren't used, as other sniffs check those. It
also covers lists with no example, such as `echo 1, 2;` and `global $a, $b;`.

**PER Coding Style 3.1:** [Section 3](https://www.php-fig.org/per/coding-style/#3-declare-statements-namespace-and-import-statements), [Section 4.7](https://www.php-fig.org/per/coding-style/#47-method-and-function-calls), [Section 5.2](https://www.php-fig.org/per/coding-style/#52-switch-case-match), [Section 11](https://www.php-fig.org/per/coding-style/#11-arrays), [Section 12.3](https://www.php-fig.org/per/coding-style/#123-compound-attributes).

**Auto-fixable:** yes.

---

## Universal.WhiteSpace.FirstClassCallableSpacing

Requires a first-class callable written as `foo(...)`, with no white space or line break inside the parentheses.

**PER Coding Style 3.1:** [Section 4.8](https://www.php-fig.org/per/coding-style/#48-function-callable-references).

**Auto-fixable:** yes.
