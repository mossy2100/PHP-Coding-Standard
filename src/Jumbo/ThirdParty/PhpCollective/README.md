# PhpCollective (third-party copy)

Copied from [php-collective/code-sniffer](https://github.com/php-collective/code-sniffer) 0.6.10, under its MIT licence
(see `LICENSE`), as the package isn't at 1.0 yet.

- `WhiteSpace/ConsistentIndentSniff.php`: the parent of `Jumbo.WhiteSpace.ConsistentIndent`.
- `AbstractSniffs/AbstractSniff.php` and `Traits/BasicsTrait.php`: its parent class and trait.

Only the namespaces are changed, from `PhpCollective\Sniffs\...` and `PhpCollective\Traits` to
`Jumbo\ThirdParty\PhpCollective\...`, so a later release can be compared with a diff. These files aren't in a `Sniffs`
directory, so PHP_CodeSniffer doesn't register them as sniffs.
