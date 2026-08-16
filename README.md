# OceanMoon Coding Standard for PHP

A PHP_CodeSniffer coding standard for PHP 8.4, developed by Shaun Moss.

**[License](LICENSE)** | **[Changelog](CHANGELOG.md)**

![PHP 8.4](docs/logo_php8_4.png)

---

## Description

This package provides a custom PHP_CodeSniffer coding standard for PHP, which extends PSR-12 with additional rules for consistent naming conventions and modern PHP 8.4+ syntax.

**Key Features:**

- Extends PSR-12 coding standard.
- Enforces "camelCase" naming for variables, parameters, and properties.
- Enforces consistent array formatting: single-line, grid, one-per-line, and associative with aligned arrows.
- Removes unnecessary parentheses around class instantiation (PHP 8.4+).
- Enforces correct indentation for property hooks.
- Automatic registration with PHP_CodeSniffer.

The package provides several custom sniffs to cover gaps in the available standards. These include:

- **OceanMoon.Arrays.ArrayDeclaration**: Enforces consistent array formatting with lists and associative arrays.
- **OceanMoon.Classes.ClassInstantiationNoBrackets**: Removes unnecessary parentheses around class instantiation when
  accessing members (PHP 8.4+).
- **OceanMoon.Classes.PropertyDeclaration**: Verifies property declarations, with property hook support (PHP 8.4+).
- **OceanMoon.WhiteSpace.ScopeIndent**: Checks that control structures and code are indented correctly, with property hook support (PHP 8.4+).
- **OceanMoon.PHP.DisallowMultiConstantDefinition**: Disallows defining multiple constants in one statement. A fork
  of `SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition` fixing a false positive on constants with a
  parenthesized multi-argument value (e.g. `const I = new Complex(0, 1);`).

See [Custom Sniffs](docs/CustomSniffs.md) for details.

---

## Development and Quality Assurance

[Claude Chat](https://claude.ai) and [Claude Code](https://www.claude.com/product/claude-code) were used in the development of this package. The core classes were designed, coded, and commented primarily by the author, with Claude providing substantial assistance with code review, suggesting improvements, debugging, and generating tests and documentation.

All code was thoroughly reviewed by the author, and validated using industry-standard tools including [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer/) and [PHPStan](https://phpstan.org/) (to level 9) to ensure full compliance with [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards.

This collaborative approach has produced a well-designed, production-ready package with thorough test coverage and documentation.

---

## Requirements

- PHP ^8.4
- squizlabs/php_codesniffer ^4.0
- slevomat/coding-standard ^8.25
- dealerdirect/phpcodesniffer-composer-installer ^1.0

---

## Installation

```bash
composer require --dev oceanmoon/coding-standard
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

    <rule ref="OceanMoon"/>
</ruleset>
```

Then run:

```bash
vendor/bin/phpcs        # Check for issues
vendor/bin/phpcbf       # Auto-fix issues
```

---

## Included Sniffs

The OceanMoon Coding Standard extends PSR-12, borrows from existing standards, and adds several custom sniffs.

1. [Inherited Sniffs](docs/InheritedSniffs.md) - From PSR-12 and other standards.
2. [Custom Sniffs](docs/CustomSniffs.md) - Developed for this standard.

---

## Variable and Property Naming Convention

This coding standard is supplied by the **Squiz.NamingConventions.ValidVariableName** sniff, which ensures all
variables, parameters, and properties:

1. Use the `$camelCase` style.
2. Do not have leading underscores.

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

So - why is this included?

PSR-12 and PER 3.0 do not mandate variable naming conventions. Specifically, from
[PSR-1, Section 4.2 "Properties"](https://www.php-fig.org/psr/psr-1/#42-properties):

> This guide intentionally avoids any recommendation regarding the use of \$StudlyCaps, \$camelCase, or \$under_score
> property names. Whatever naming convention is used SHOULD be applied consistently within a reasonable scope. That
> scope may be vendor-level, package-level, class-level, or method-level.

In addition, from
[PER Coding Style 3.0 Section 4.3 "Properties and Constants"](https://www.php-fig.org/per/coding-style/#43-properties-and-constants):

> Property or constant names MUST NOT be prefixed with a single underscore to indicate protected or private visibility.
> That is, an underscore prefix explicitly has no meaning.

Once upon a time, the convention was to use `$snake_case` for variable names and properties; however, as the
object-oriented features of PHP evolved, it became more common to use `$camelCase`, following the coding convention
from Java. AI-generated code typically uses `$camelCase`, which is indicative of the trend. Therefore, given the
requirement to be consistent, this sniff enforces the use of `$camelCase` for all variables, class properties, and
function parameters.

Similarly, using an underscore prefix to indicate protected or private visibility was common practice in until PHP 5.0 when visibility modifiers became available. Now, the use of an underscore prefix is generally discouraged or disallowed.

This sniff is compliant with several PHP coding standards:

1. Symfony requires `$camelCase` ([ref](https://symfony.com/doc/current/contributing/code/standards.html#naming-conventions)).
2. Laravel requires `$camelCase` ([unofficially](https://spatie.be/guidelines/laravel-php#content-general-php-rules)).
3. Drupal variable names may use either `$camelCase` or `$snake_case` ([ref](https://project.pages.drupalcode.org/coding_standards/php/coding/#functions-and-variables)), as long as consistency is maintained. Properties should use `$camelCase`, and protected or private properties should not use an underscore prefix. ([ref](https://project.pages.drupalcode.org/coding_standards/php/coding/#classes-methods-and-properties)).

WordPress is the main variation, requiring `$snake_case` for variables, but permitting either `$snake_case` or `$camelCase` for properties.

---

## Yoda-style Comparisons

A **Yoda condition** puts the constant on the left of a comparison (`true === $x` instead of `$x === true`), so an accidental `=` when `==` was meant becomes a parse error instead of a silent, always-truthy assignment.

Opinion on Yoda conditions is split across the PHP ecosystem:

1. Symfony [requires](https://symfony.com/doc/current/contributing/code/standards.html#naming-conventions) them.
2. WordPress required them too, but is phasing the requirement out. Modern WordPress core updates and sub-ecosystems like WooCommerce have largely voted to remove or forbid Yoda style, in favor of letting tooling catch accidental assignments directly ([ref](https://make.wordpress.org/core/2022/06/14/upcoming-disallow-assignments-in-conditions-and-remove-the-yoda-condition-requirement-for-php/)).
3. Drupal explicitly disallows Yoda conditions, prioritizing left-to-right readability for developers scanning a condition. Its `drupal/coder` PHPCS ruleset enforces this via a `DisallowYodaConditions` sniff ([ref](https://www.drupal.org/project/coding_standards/issues/2372543)).
4. Laravel doesn't use Yoda style anywhere in its ecosystem, favoring expressive, natural-reading syntax over it. [Laravel Pint](https://laravel.com/docs/pint) (Laravel's code-style tool, a wrapper around PHP-CS-Fixer) actively rewrites Yoda expressions back to standard order via PHP-CS-Fixer's `yoda_style` rule, configured with `equal`/`identical`/`less_and_greater` all `false`.
5. The Slevomat coding standard, which this standard borrows many rules from, expressly disallows Yoda-style comparisons as a less readable anti-pattern, via the **DisallowYodaComparison** sniff (not included here).
6. Modern IDEs, such as PhpStorm and VS Code with the Intelephense extension, will flag an assignment inside a boolean expression (e.g. an `if`/`while` condition) directly, regardless of operand order.

Given that some PHP developers will use Yoda conditions and some won't, this coding standard is deliberately silent on the issue. Write comparisons in whichever order reads best to you.

It does, however, include the `Generic.CodeAnalysis.AssignmentInCondition` sniff, which flags assignments in `if`, `elseif`, `while`, `for`, `switch`, `case`, and `match` conditions/expressions. This directly addresses the problem Yoda-style comparisons are intended to solve, without dictating operand order.

---

## License

MIT License - see [LICENSE](LICENSE) for details.

---

## Support

- **Issues**: https://github.com/mossy2100/PHP-Coding-Standard/issues
- **Examples**: See `phpcs.xml` files in other OceanMoon packages

For questions or suggestions, please [open an issue](https://github.com/mossy2100/PHP-Coding-Standard/issues).

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and changes.
