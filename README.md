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

The standard is PSR-12, plus sniffs from PHP_CodeSniffer and Slevomat, plus these custom sniffs:

| Sniff                                                 | Checks                                                        | Fixable |
| ----------------------------------------------------- | ------------------------------------------------------------- | ------- |
| `Mossy2100.Arrays.ArrayDeclaration`                   | A multi-line array is on one line if it fits                  | Yes     |
| `Mossy2100.Classes.DisallowPromotedPropertyHooks`     | No property hooks on promoted constructor parameters          | No      |
| `Mossy2100.Classes.PropertyDeclaration`               | No leading `_`, no `var`, and a visibility, with hook support | No      |
| `Mossy2100.ControlStructures.MatchArrowSpacing`       | One space around `=>` in `match` arms                         | Yes     |
| `Mossy2100.ControlStructures.RequireSingleLineHeader` | A multi-line loop, `switch` or `match` header fits on a line  | Yes     |
| `Mossy2100.NamingConventions.ValidVariableName`       | No leading underscore on a variable or parameter in a class   | No      |
| `Mossy2100.PHP.DisallowMultiConstantDefinition`       | One constant per statement                                    | Yes     |
| `Mossy2100.WhiteSpace.ScopeIndent`                    | Indentation, including property hooks                         | Yes     |

- [Custom Sniffs](docs/CustomSniffs.md): each sniff in detail.
- [Inherited Sniffs](docs/InheritedSniffs.md): every sniff from PSR-12, Generic, Squiz and Slevomat.

---

## Variable and Property Naming Convention

Variables, parameters and properties must use `$camelCase`, with no leading underscore. Superglobals such as `$_GET` are
exempt.

| Name                                                      | Severity | Sniff                                           |
| --------------------------------------------------------- | -------- | ----------------------------------------------- |
| Not camelCase, e.g. `$user_name`                          | Error    | `Squiz.NamingConventions.ValidVariableName`     |
| Property with a leading `_`, including promoted ones      | Error    | `Mossy2100.Classes.PropertyDeclaration`         |
| Variable or parameter with a leading `_`, outside a class | Error    | `Squiz.NamingConventions.ValidVariableName`     |
| Variable or parameter with a leading `_`, inside a class  | Warning  | `Mossy2100.NamingConventions.ValidVariableName` |

Squiz allows a leading underscore inside a class, because the Squiz convention prefixes private members with one. The
Mossy2100 sniffs fill that gap.

**Good:**

```php
$userName = 'John';
$orderTotal = 100;
$isValid = true;
```

**Bad:**

```php
$user_name = 'John';   // snake_case not allowed
$_private = 'value';   // leading underscore not allowed
$UserName = 'John';    // UpperCamelCase not allowed
```

### Why camelCase

PSR-1, PSR-12 and PER Coding Style 3.0 don't set a naming convention for variables or properties.
[PSR-1, Section 4.2 "Properties"](https://www.php-fig.org/psr/psr-1/#42-properties) only asks for consistency:

> This guide intentionally avoids any recommendation regarding the use of \$StudlyCaps, \$camelCase, or \$under_score
> property names. Whatever naming convention is used SHOULD be applied consistently within a reasonable scope. That
> scope may be vendor-level, package-level, class-level, or method-level.

[PER Coding Style 3.0, Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) rules out the underscore prefix:

> Property or constant names MUST NOT be prefixed with a single underscore to indicate protected or private visibility.
> That is, an underscore prefix explicitly has no meaning.

`$camelCase` is now the common choice in object-oriented PHP, and matches most major conventions:

- [Symfony](https://symfony.com/doc/current/contributing/code/standards.html#naming-conventions) requires `$camelCase`.
- [Spatie's Laravel guidelines](https://spatie.be/guidelines/laravel-php#content-general-php-rules) require `$camelCase`. Laravel itself has no official rule.
- [Drupal](https://project.pages.drupalcode.org/coding_standards/php/coding/#functions-and-variables) allows `$camelCase` or `$snake_case` for variables, if consistent. [Drupal properties](https://project.pages.drupalcode.org/coding_standards/php/coding/#classes-methods-and-properties) use `$camelCase`, with no
  underscore prefix.
- WordPress is the exception: `$snake_case` for variables, and either style for properties.

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
