# Jumbo Coding Standard

A PHP_CodeSniffer coding standard for PHP 8.4, developed by Shaun Moss.

**[License](LICENSE)** | **[Changelog](CHANGELOG.md)**

![PHP 8.4](docs/logo_php8_4.png)

---

## Description

The Jumbo Coding Standard has two rulesets, each usable as a PHP_CodeSniffer standard:

- **PERCS31**: [PER Coding Style 3.1](https://www.php-fig.org/per/coding-style/), and nothing more, built on PSR-12. No complete PHP_CodeSniffer standard for PER
  Coding Style existed. See [PERCS31](docs/PERCS31.md).
- **Jumbo**: PERCS31 plus extra rules for consistent naming and modern PHP 8.4+ code. See [Jumbo](docs/Jumbo.md).

### Key features of Jumbo

- Follows PER Coding Style 3.1, through PERCS31.
- Enforces `$camelCase` naming for variables, parameters and properties.
- Enforces consistent array formatting: single-line if it fits, otherwise one element per line with a trailing comma. No
  alignment of arrows or assignments, to keep diffs small.
- Enforces correct indentation for property hooks.

### Why the name "Jumbo"?

- Refers to a famous elephant, the elephant being PHP's spirit animal.
- It sounds good.
- Pairs nicely with `Dumbo`, another famous elephant. `Dumbo` will be the `Jumbo` variant for Drupal.

---

## Development and Quality Assurance

This package was developed with [Claude Code](https://claude.com/product/claude-code):

- The author developed the initial concept, designed the rules, and did most of the initial coding.
- Claude Code wrote the tests and most of the documentation.
- Claude Code also assisted with multiple code reviews, which led to bug fixes, performance improvements and other
  refinements.

The code is checked with:

- [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer/), for the Jumbo coding standard
- [PHPStan](https://phpstan.org/), at level 9
- [PHPUnit](https://phpunit.de/index.html), with a unit test for each custom sniff

---

## Requirements

- PHP ^8.4
- squizlabs/php_codesniffer ^4.0
- slevomat/coding-standard ^8.31.3
- phpcsstandards/phpcsextra ^1.5
- phpcsstandards/phpcsutils ^1.2
- php-collective/code-sniffer ^0.6.10
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

    <rule ref="Jumbo"/>
</ruleset>
```

Then run:

```bash
vendor/bin/phpcs        # Check for issues
vendor/bin/phpcbf       # Auto-fix issues
```

To follow PER Coding Style 3.1 without the extra rules, use `<rule ref="PERCS31"/>` instead.

The default maximum line length is 120. To change it, see [Line Length](docs/LineLength.md).

---

## Included Sniffs

Each standard has its own page, listing the sniffs it uses, inherited and custom, with the reason for each. PSR-12's own
sniffs are listed only where PERCS31 changes or replaces them.

- [PERCS31](docs/PERCS31.md): PSR-12, sniffs from Generic, PEAR, Squiz, Slevomat, PHPCSExtra (`Universal`) and PhpCollective, and custom
  sniffs, for PER Coding Style 3.1.
- [Jumbo](docs/Jumbo.md): what Jumbo changes in PERCS31, and the Generic, Squiz, Slevomat and Zend sniffs and custom sniffs it adds as
  extra rules.

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
