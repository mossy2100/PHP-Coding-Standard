# Jumbo Coding Standard - Variable and Property Names

The [Jumbo Coding Standard](../README.md) requires variables, parameters and properties to use `$camelCase`, with no leading underscore.
Three sniffs check this between them. This page shows which one checks each kind of name.

---

## camelCase

| Name                                         | Sniff                                       | Severity |
| -------------------------------------------- | ------------------------------------------- | -------- |
| Property: any visibility, static or promoted | `Squiz.NamingConventions.ValidVariableName` | Error    |
| Variable or parameter, anywhere              | `Squiz.NamingConventions.ValidVariableName` | Error    |

---

## Leading Underscore

| Name                                                           | Sniff                                       | Severity |
| -------------------------------------------------------------- | ------------------------------------------- | -------- |
| Property: any visibility, static or promoted                   | `Jumbo.Classes.PropertyDeclaration`         | Error    |
| Variable or parameter inside a class, enum, trait or interface | `Jumbo.NamingConventions.ValidVariableName` | Warning  |
| Variable or parameter anywhere else                            | `Squiz.NamingConventions.ValidVariableName` | Error    |
| Superglobal, e.g. `$_GET`                                      | None                                        | Allowed  |

"Variable or parameter" covers function and method parameters, local variables, closure and arrow function parameters,
`foreach` variables, and global variables.

Squiz allows a leading underscore inside a class, because the Squiz convention prefixes private members with one. The
two Jumbo sniffs fill that gap.

For properties, `Jumbo.Classes.PropertyDeclaration` replaces PSR-12's `PSR2.Classes.PropertyDeclaration`, which only
issues a warning for a leading underscore, and doesn't check promoted constructor properties. The Jumbo sniff reports
both as errors, since [PER Coding Style 3.0, Section 4.3](https://www.php-fig.org/per/coding-style/#43-properties-and-constants) says property names MUST NOT start with an underscore.

---

## Examples

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

---

## Why camelCase

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
- WordPress is the exception. It requires `$snake_case` for variables, and either style for properties.
