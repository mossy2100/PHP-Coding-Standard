# Mossy2100 PHP Coding Standard

A PHP_CodeSniffer coding standard for PHP 8.4, developed by Shaun Moss.

**[License](LICENSE)** | **[Changelog](CHANGELOG.md)**

![PHP 8.4](docs/logo_php8_4.png)

---

## Description

This package provides a custom PHP_CodeSniffer coding standard for PHP, which extends PSR-12 with additional rules for
consistent naming conventions and modern PHP 8.4+ syntax.

**Key Features:**

- Extends PSR-12 coding standard.
- Enforces `$camelCase` naming for variables, parameters and properties.
- Enforces consistent array formatting: single-line if it fits, otherwise one element per line with a trailing comma. No
  alignment of arrows or assignments, to keep diffs small.
- Enforces correct indentation for property hooks.

---

## Development and Quality Assurance

This package was developed with [Claude Code](https://claude.com/product/claude-code):

- The author developed the initial concept, designed the rules, and did most of the initial coding.
- Claude Code wrote the tests and most of the documentation.
- Claude Code also assisted with multiple code reviews, which led to bug fixes, performance improvements and other
  refinements.

The code is checked with:

- [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer/), for [PSR-12](https://www.php-fig.org/psr/psr-12/) compliance plus the Mossy2100 coding standard
- [PHPStan](https://phpstan.org/), at level 9
- [PHPUnit](https://phpunit.de/index.html), with a unit test for each custom sniff

---

## Requirements

- PHP ^8.4
- squizlabs/php_codesniffer ^4.0
- slevomat/coding-standard ^8.27
- dealerdirect/phpcodesniffer-composer-installer ^1.0

---

## Installation

```bash
composer require --dev mossy2100/coding-standard
```

The standard is automatically registered with PHP_CodeSniffer via the `dealerdirect/phpcodesniffer-composer-installer`
plugin.

---

## Usage

Create a `phpcs.xml` file in your project root:

```xml
<?xml version="1.0"?>
<ruleset name="My Project">
    <description>Coding standard for my project</description>

    <file>src</file>
    <file>tests</file>

    <rule ref="Mossy2100"/>
</ruleset>
```

Then run:

```bash
vendor/bin/phpcs        # Check for issues
vendor/bin/phpcbf       # Auto-fix issues
```

The line length limit is 120. To change it, see [Line Length](docs/LineLength.md).

---

## Included Sniffs

This coding standard is built on four base standards, each documented in [Inherited Sniffs](docs/InheritedSniffs.md):

- **PSR-12**, **Generic** and **Squiz**, which come with PHP_CodeSniffer.
- **Slevomat**, a separate package of sniffs for modern PHP.

It adds the following custom sniffs, which fill gaps in the base standards, for compliance with PER Coding Style 3.0 and support
for PHP 8.4 features such as property hooks, or set house-style choices. Each one's reason is in [Custom Sniffs](docs/CustomSniffs.md):

| Sniff                                                 | Checks                                                        | Fixable |
| ----------------------------------------------------- | ------------------------------------------------------------- | ------- |
| `Mossy2100.Arrays.ArrayDeclaration`                   | A multi-line array is on one line if it fits                  | Yes     |
| `Mossy2100.Attributes.AttributePlacement`             | An attribute is on its own line, except on a parameter        | Yes     |
| `Mossy2100.Classes.ClassDeclaration`                  | Class declarations, allowing an empty body as `{}`            | Yes     |
| `Mossy2100.Classes.EmptyLinesAroundClassBraces`       | No blank lines inside class braces, allowing `{}`             | Yes     |
| `Mossy2100.Classes.DisallowPromotedPropertyHooks`     | No property hooks on promoted constructor parameters          | No      |
| `Mossy2100.Classes.PropertyDeclaration`               | No leading `_`, no `var`, and a visibility, with hook support | No      |
| `Mossy2100.ControlStructures.MatchArrowSpacing`       | One space around `=>` in `match` arms                         | Yes     |
| `Mossy2100.ControlStructures.RequireSingleLineHeader` | A multi-line loop, `switch` or `match` header fits on a line  | Yes     |
| `Mossy2100.Formatting.DisallowMultipleStatements`     | One statement per line, allowing `{ get; set; }`              | Yes     |
| `Mossy2100.Formatting.EmptyBody`                      | An empty class or function body is `{}` (a warning)           | Yes     |
| `Mossy2100.Functions.MultiLineFunctionDeclaration`    | Function declarations, allowing an empty body as `{}`         | Yes     |
| `Mossy2100.NamingConventions.ValidVariableName`       | No leading underscore on a variable or parameter in a class   | No      |
| `Mossy2100.PHP.DisallowMultiConstantDefinition`       | One constant per statement                                    | Yes     |
| `Mossy2100.WhiteSpace.ScopeClosingBrace`              | A closing brace on its own line, allowing `{}`                | Yes     |
| `Mossy2100.WhiteSpace.ScopeIndent`                    | Indentation, including property hooks                         | Yes     |

---

## Variable and Property Names

Variables, parameters and properties must use `$camelCase`, with no leading underscore. See [Variable and Property Names](docs/VariableAndPropertyNames.md)
for which sniff checks each kind of name, and why the standard uses camelCase.

---

## Yoda-style Comparisons

A **Yoda condition** puts the constant on the left of a comparison (`true === $x` instead of `$x === true`), so an
accidental `=` when `==` was meant becomes a parse error instead of a silent, always-truthy assignment.

Opinion on Yoda conditions is split:

- [Symfony](https://symfony.com/doc/current/contributing/code/standards.html#structure) requires them.
- WordPress required them, but is [phasing them out](https://make.wordpress.org/core/2022/06/14/upcoming-disallow-assignments-in-conditions-and-remove-the-yoda-condition-requirement-for-php/) in favor of tooling that catches accidental assignments.
- Drupal disallows them, for left-to-right readability. Its `drupal/coder` ruleset has a [`DisallowYodaConditions`](https://www.drupal.org/project/coding_standards/issues/2372543) sniff.
- Laravel doesn't use them, and [Laravel Pint](https://laravel.com/docs/pint) rewrites them to the usual order.
- Slevomat, which this standard borrows from, disallows them with `DisallowYodaComparison` (not included here).

So this standard is silent on operand order. Write comparisons in whichever order reads best to you.

It does include `Generic.CodeAnalysis.AssignmentInCondition`, which flags assignments in `if`, `elseif`, `while`, `for`,
`switch`, `case` and `match` conditions. That solves the problem Yoda conditions were meant to solve, without dictating
operand order. IDEs such as PhpStorm, and VS Code with Intelephense, flag these assignments too.

---

## License

MIT License - see [LICENSE](LICENSE) for details.

---

## Support

- **Issues**: https://github.com/mossy2100/PHP-Coding-Standard/issues
- **Examples**: The [`phpcs.xml` in Mossy2100 Core](https://github.com/mossy2100/PHP-Core/blob/main/phpcs.xml), and in the other Mossy2100 packages

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and changes.
