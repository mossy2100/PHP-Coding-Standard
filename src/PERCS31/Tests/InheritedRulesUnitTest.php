<?php

declare(strict_types=1);

namespace PERCS31\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests that each inherited sniff the PERCS31 ruleset references reports a violation, with the type the ruleset gives
 * it.
 *
 * The per-sniff tests only cover the custom sniffs, and the spec examples only show that valid code passes. So without
 * these, nothing fails if an inherited sniff, one of its properties or its type is removed from the ruleset. Each row
 * is a small file that breaks one rule. phpcs runs once, over all of them.
 */
#[CoversNothing]
class InheritedRulesUnitTest extends TestCase
{
    use RunsToolsTrait;

    /**
     * A file that breaks each rule, the code it must be reported with, and the message's type.
     *
     * @var array<string, array{string, string, string}>
     */
    private const array RULES = [
        'a split condition with && at the end of a line' => [
            "<?php\n\nif (\n    \$a &&\n    \$b\n) {\n    run();\n}\n",
            'PSR12.ControlStructures.BooleanOperatorPlacement.FoundMixed',
            'error',
        ],
        '::CLASS in upper case' => [
            "<?php\n\n\$a = Foo::CLASS;\n",
            'Universal.Constants.LowercaseClassResolutionKeyword.Uppercase',
            'error',
        ],
        'two spaces each side of as in an import' => [
            "<?php\n\nuse Foo\\Bar  as  Baz;\n",
            'Universal.UseStatements.KeywordSpacing.SpaceBeforeAs',
            'error',
        ],
        'use FUNCTION in upper case' => [
            "<?php\n\nuse FUNCTION Foo\\bar;\n",
            'Universal.UseStatements.LowercaseFunctionConst.NotLowercase',
            'error',
        ],
        'a space inside a DNF type\'s parentheses' => [
            "<?php\n\nfunction a(( A&B )|null \$b): void {}\n",
            'SlevomatCodingStandard.TypeHints.DNFTypeHintFormat.DisallowedWhitespaceInsideParentheses',
            'error',
        ],
        'a trailing comma in a closure use list on one line' => [
            "<?php\n\n\$a = function () use (\$b,) {\n    return \$b;\n};\n",
            'SlevomatCodingStandard.Functions.DisallowTrailingCommaInClosureUse.DisallowedTrailingComma',
            'error',
        ],
        'a trailing comma in a parameter list on one line' => [
            "<?php\n\nfunction a(\$b,): void {}\n",
            'SlevomatCodingStandard.Functions.DisallowTrailingCommaInDeclaration.DisallowedTrailingComma',
            'error',
        ],
        'no trailing comma in a split closure use list' => [
            "<?php\n\n\$a = function () use (\n    \$b\n) {\n    return \$b;\n};\n",
            'SlevomatCodingStandard.Functions.RequireTrailingCommaInClosureUse.MissingTrailingComma',
            'error',
        ],
        'no trailing comma in a split parameter list' => [
            "<?php\n\nfunction a(\n    \$b\n): void {}\n",
            'SlevomatCodingStandard.Functions.RequireTrailingCommaInDeclaration.MissingTrailingComma',
            'error',
        ],
        'no trailing comma in a split array' => [
            "<?php\n\n\$a = [\n    1\n];\n",
            'SlevomatCodingStandard.Arrays.TrailingArrayComma.MissingTrailingComma',
            'error',
        ],
        'a trailing comma in an attribute block on one line' => [
            "<?php\n\n#[Foo, Bar,]\nfunction a(): void {}\n",
            'Universal.Attributes.TrailingComma.ForbiddenSingleLine',
            'error',
        ],
        'a space before a comma' => [
            "<?php\n\necho \$a , \$b;\n",
            'Universal.WhiteSpace.CommaSpacing.SpaceBefore',
            'error',
        ],
        'no space after a comma' => [
            "<?php\n\necho \$a,\$b;\n",
            'Universal.WhiteSpace.CommaSpacing.NoSpaceAfter',
            'error',
        ],
        'two spaces after a comma' => [
            "<?php\n\necho \$a,  \$b;\n",
            'Universal.WhiteSpace.CommaSpacing.TooMuchSpaceAfter',
            'error',
        ],
        'a comma on the line after a comment' => [
            "<?php\n\n\$a = [\n    1 // One.\n    ,\n    2,\n];\n",
            'Universal.WhiteSpace.CommaSpacing.CommaAfterComment',
            'error',
        ],
        'a space before a comma in a call' => [
            "<?php\n\nfoo(\$a , \$b);\n",
            'Universal.WhiteSpace.CommaSpacing.SpaceBeforeInFunctionCall',
            'error',
        ],
        'no space after a comma in a call' => [
            "<?php\n\nfoo(\$a,\$b);\n",
            'Universal.WhiteSpace.CommaSpacing.NoSpaceAfterInFunctionCall',
            'error',
        ],
        'two spaces after a comma in a call' => [
            "<?php\n\nfoo(\$a,  \$b);\n",
            'Universal.WhiteSpace.CommaSpacing.TooMuchSpaceAfterInFunctionCall',
            'error',
        ],
        'a space before a comma in an attribute block' => [
            "<?php\n\n#[Foo , Bar]\nfunction a(): void {}\n",
            'Universal.WhiteSpace.CommaSpacing.SpaceBeforeInAttributeBlock',
            'error',
        ],
        'no space after a comma in an attribute block' => [
            "<?php\n\n#[Foo,Bar]\nfunction a(): void {}\n",
            'Universal.WhiteSpace.CommaSpacing.NoSpaceAfterInAttributeBlock',
            'error',
        ],
        'two spaces after a comma in an attribute block' => [
            "<?php\n\n#[Foo,  Bar]\nfunction a(): void {}\n",
            'Universal.WhiteSpace.CommaSpacing.TooMuchSpaceAfterInAttributeBlock',
            'error',
        ],
        'an opening tag in upper case' => ["<?PHP\n\n\$a = 1;\n", 'Universal.PHP.LowercasePHPTag.Uppercase', 'error'],
        'a blank line between trait use statements' => [
            "<?php\n\nclass A\n{\n    use B;\n\n    use C;\n}\n",
            'SlevomatCodingStandard.Classes.TraitUseSpacing.IncorrectLinesCountBetweenUses',
            'error',
        ],
        'two traits in a use statement' => [
            "<?php\n\nclass A\n{\n    use B, C;\n}\n",
            'SlevomatCodingStandard.Classes.TraitUseDeclaration.MultipleTraitsPerDeclaration',
            'error',
        ],
        'readonly before final on a class' => [
            "<?php\n\nreadonly final class A {}\n",
            'SlevomatCodingStandard.Classes.ClassKeywordOrder.WrongClassKeywordOrder',
            'error',
        ],
        'two constants in a statement' => [
            "<?php\n\nclass A\n{\n    public const B = 1, C = 2;\n}\n",
            'SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition.DisallowedMultiConstantDefinition',
            'error',
        ],
        'two properties in a statement' => [
            "<?php\n\nclass A\n{\n    public int \$b, \$c;\n}\n",
            'SlevomatCodingStandard.Classes.DisallowMultiPropertyDefinition.DisallowedMultiPropertyDefinition',
            'error',
        ],
        'readonly before the visibility of a promoted parameter' => [
            "<?php\n\nclass A\n{\n    public function __construct(readonly public int \$b) {}\n}\n",
            'SlevomatCodingStandard.Classes.PropertyDeclaration.IncorrectOrderOfModifiers',
            'error',
        ],
        'a space before a named argument\'s colon' => [
            "<?php\n\nfoo(a : 1);\n",
            'SlevomatCodingStandard.Functions.NamedArgumentSpacing.WhitespaceBeforeColon',
            'error',
        ],
        'exit with no parentheses' => [
            "<?php\n\nexit;\n",
            'Universal.PHP.RequireExitDieParentheses.Missing',
            'warning',
        ],
        'an if with the alternative syntax' => [
            "<?php\n\nif (\$a):\n    run();\nendif;\n",
            'Universal.ControlStructures.DisallowAlternativeSyntax.FoundIf',
            'error',
        ],
        'no space after a cast' => ["<?php\n\n\$a = (int)\$b;\n", 'Generic.Formatting.SpaceAfterCast.NoSpace', 'error'],
        'a concatenation operator at the end of a line' => [
            "<?php\n\n\$a = 'b' .\n    'c';\n",
            'Universal.Operators.ConcatPosition.Incorrect',
            'error',
        ],
        'a space after fn' => [
            "<?php\n\n\$a = fn (\$b) => \$b;\n",
            'SlevomatCodingStandard.Functions.ArrowFunctionDeclaration.IncorrectSpacesAfterKeyword',
            'error',
        ],
        'empty parentheses on an anonymous class' => [
            "<?php\n\n\$a = new class () {};\n",
            'Universal.Classes.DisallowAnonClassParentheses.Found',
            'error',
        ],
        'a space before a backed enum\'s colon' => [
            "<?php\n\nenum A : string\n{\n    case B = 'b';\n}\n",
            'SlevomatCodingStandard.Classes.BackedEnumTypeSpacing.IncorrectSpacesBeforeColon',
            'error',
        ],
        'a heredoc with nothing to interpolate' => [
            "<?php\n\n\$a = <<<EOT\n    b\n    EOT;\n",
            'Generic.Strings.UnnecessaryHeredoc.Found',
            'warning',
        ],
        'the long array syntax' => [
            "<?php\n\n\$a = array(1);\n",
            'Generic.Arrays.DisallowLongArraySyntax.Found',
            'error',
        ],
        'an array element indented by two spaces' => [
            "<?php\n\n\$a = [\n  1,\n];\n",
            'Generic.Arrays.ArrayIndent.KeyIncorrect',
            'error',
        ],
        'a trailing comma in an array on one line' => [
            "<?php\n\n\$a = [1, 2,];\n",
            'Squiz.Arrays.ArrayDeclaration.CommaAfterLast',
            'error',
        ],
        'a space inside an array\'s brackets' => [
            "<?php\n\n\$a = [ 1, 2 ];\n",
            'SlevomatCodingStandard.Arrays.SingleLineArrayWhitespace.SpaceAfterArrayOpen',
            'error',
        ],
        'empty parentheses on an attribute' => [
            "<?php\n\n#[Foo()]\nfunction a(): void {}\n",
            'Universal.Attributes.DisallowAttributeParentheses.Found',
            'error',
        ],
        'a space inside an attribute block\'s brackets' => [
            "<?php\n\n#[ Foo ]\nfunction a(): void {}\n",
            'Universal.Attributes.BracketSpacing.SpaceAfterOpener',
            'error',
        ],
        'an attribute before a doc comment' => [
            "<?php\n\n#[Foo]\n/**\n * Does a thing.\n */\nfunction a(): void {}\n",
            'SlevomatCodingStandard.Attributes.RequireAttributeAfterDocComment.AttributeBeforeDocComment',
            'error',
        ],
        'a constant with no visibility' => [
            "<?php\n\nclass A\n{\n    const B = 1;\n}\n",
            'PSR12.Properties.ConstantVisibility.NotFound',
            'error',
        ],
    ];

    /**
     * The messages phpcs gave for each rule's file, as "type code", or null before phpcs has run.
     *
     * @var array<string, list<string>>|null
     */
    private static ?array $reports = null;

    /**
     * Provide the name of each rule.
     *
     * @return iterable<string, array{string}> The rule's name, keyed by itself.
     */
    public static function inheritedRuleProvider(): iterable
    {
        foreach (array_keys(self::RULES) as $name) {
            yield $name => [$name];
        }
    }

    /**
     * Test that a rule's file is reported with the rule's code and type.
     *
     * @param string $name The rule's name.
     */
    #[DataProvider('inheritedRuleProvider')]
    public function testInheritedRuleIsReported(string $name): void
    {
        [, $code, $type] = self::RULES[$name];
        $reports = $this->findReports()[$name] ?? [];

        $this->assertContains("$type $code", $reports, 'Reported: ' . implode(', ', $reports));
    }

    /**
     * Run phpcs once over a file for each rule, and collect the messages for each.
     *
     * @return array<string, list<string>> The messages for each rule's file, as "type code".
     */
    private function findReports(): array
    {
        if (self::$reports !== null) {
            return self::$reports;
        }

        $files = [];
        foreach (self::RULES as $name => [$source]) {
            $files[basename($this->copyToTempFile(null, $source))] = $name;
        }

        $paths = array_map(static fn(string $file): string => sys_get_temp_dir() . '/' . $file, array_keys($files));

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--no-cache', '--report=emacs', ...$paths]);
        } finally {
            array_map(unlink(...), $paths);
        }

        $pattern = '/^(.+):\d+:\d+: (error|warning) - .*\(([\w.]+)\)$/m';
        preg_match_all($pattern, $result['output'], $matches, PREG_SET_ORDER);

        $reports = array_fill_keys(array_keys(self::RULES), []);
        foreach ($matches as [, $path, $type, $code]) {
            $name = $files[basename($path)] ?? null;
            if ($name !== null) {
                $reports[$name][] = "$type $code";
            }
        }

        self::$reports = $reports;

        return self::$reports;
    }
}
