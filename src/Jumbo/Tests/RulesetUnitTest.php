<?php

declare(strict_types=1);

namespace Jumbo\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests of the full Jumbo and PERCS31 rulesets, and of sniff properties that can only be set in a ruleset.
 *
 * The per-sniff tests run each sniff alone. These run phpcs and phpcbf as a user would, so they catch conflicts
 * between sniffs, such as two fixers undoing each other's changes.
 */
#[CoversNothing]
class RulesetUnitTest extends TestCase
{
    /**
     * The fixture directory.
     */
    private const string FIXTURES = __DIR__ . '/Ruleset';

    #region Full ruleset tests.

    /**
     * Test that code following the standard has no violations.
     */
    public function testGoodFixtureHasNoViolations(): void
    {
        $result = $this->runTool('phpcs', ['--standard=Jumbo', '--report=json', self::FIXTURES . '/Good.inc']);

        $this->assertSame(0, $result['exitCode'], $result['output']);
    }

    /**
     * Test that the bad fixture has violations, so the phpcbf test below has something to fix.
     */
    public function testBadFixtureHasViolations(): void
    {
        $result = $this->runTool('phpcs', ['--standard=Jumbo', '--report=json', self::FIXTURES . '/Bad.inc']);

        $this->assertNotSame(0, $result['exitCode']);
    }

    /**
     * Test that phpcbf fixes every violation in the bad fixture, giving exactly the good fixture.
     */
    public function testPhpcbfFixesBadFixtureToGood(): void
    {
        $file = $this->copyToTempFile(self::FIXTURES . '/Bad.inc');

        try {
            $this->runTool('phpcbf', ['--standard=Jumbo', $file]);
            $this->assertSame(file_get_contents(self::FIXTURES . '/Good.inc'), file_get_contents($file));
        } finally {
            unlink($file);
        }
    }

    #endregion

    #region PERCS31 ruleset tests.

    /**
     * Test that code following the Jumbo standard also follows PERCS31, which Jumbo builds on.
     */
    public function testGoodFixturePassesPercs31(): void
    {
        $result = $this->runTool('phpcs', ['--standard=PERCS31', '--report=json', self::FIXTURES . '/Good.inc']);

        $this->assertSame(0, $result['exitCode'], $result['output']);
    }

    /**
     * Test that code following PER Coding Style, but not the extra rules, passes PERCS31 and fails Jumbo.
     */
    public function testExtraRulesFixturePassesPercs31Only(): void
    {
        $fixture = dirname(__DIR__, 2) . '/Jumbo/Tests/Ruleset/ExtraRules.inc';
        $percs31 = $this->runTool('phpcs', ['--standard=PERCS31', '--report=json', $fixture]);
        $jumbo = $this->runTool('phpcs', ['--standard=Jumbo', '--report=json', $fixture]);

        $this->assertSame(0, $percs31['exitCode'], $percs31['output']);
        $this->assertNotSame(0, $jumbo['exitCode']);
    }

    /**
     * Test that PERCS31 allows more than one space around an operator, as PER does, and Jumbo doesn't.
     */
    public function testOperatorSpacingAllowsSeveralSpacesInPercs31Only(): void
    {
        $file = $this->copyToTempFile(null, "<?php\n\n\$total = \$price  +  \$tax;\n\$valid = \$a  &&  \$b;\n");
        $args = ['-s', '--sniffs=PERCS31.Operators.OperatorSpacing', '--report=emacs', $file];

        try {
            $percs31 = $this->runTool('phpcs', ['--standard=PERCS31', ...$args]);
            $jumbo = $this->runTool('phpcs', ['--standard=Jumbo', ...$args]);
        } finally {
            unlink($file);
        }

        $this->assertSame(0, $percs31['exitCode'], $percs31['output']);
        $this->assertSame(4, substr_count($jumbo['output'], 'PERCS31.Operators.OperatorSpacing.Spacing'));
    }

    /**
     * Test that a closing tag at the end of a file mixing PHP and HTML is an error in Jumbo only, as PER only requires
     * omitting it from a file containing only PHP.
     */
    public function testClosingTagInMixedFileIsAnErrorInJumboOnly(): void
    {
        $file = $this->copyToTempFile(null, "<?php\n\n\$a = 1;\n\n?>\n<p>Hi</p>\n<?php\n\n\$b = 2;\n\n?>\n");
        $args = ['-s', '--sniffs=PSR2.Files.ClosingTag,Zend.Files.ClosingTag', '--report=emacs', $file];

        try {
            $percs31 = $this->runTool('phpcs', ['--standard=PERCS31', ...$args]);
            $jumbo = $this->runTool('phpcs', ['--standard=Jumbo', ...$args]);
        } finally {
            unlink($file);
        }

        $this->assertSame(0, $percs31['exitCode'], $percs31['output']);
        $this->assertStringContainsString('error - A closing tag', $jumbo['output']);
        $this->assertStringContainsString('Zend.Files.ClosingTag.NotAllowed', $jumbo['output']);
    }

    /**
     * Test that a closing tag at the end of a PHP-only file is reported once in each standard, as Jumbo excludes
     * PSR2.Files.ClosingTag in favour of Zend.Files.ClosingTag, which would otherwise report it a second time.
     */
    public function testClosingTagInPhpOnlyFileIsReportedOnce(): void
    {
        $file = $this->copyToTempFile(null, "<?php\n\n\$a = 1;\n\n?>\n");
        $args = ['-s', '--sniffs=PSR2.Files.ClosingTag,Zend.Files.ClosingTag', '--report=emacs', $file];

        try {
            $percs31 = $this->runTool('phpcs', ['--standard=PERCS31', ...$args]);
            $jumbo = $this->runTool('phpcs', ['--standard=Jumbo', ...$args]);
        } finally {
            unlink($file);
        }

        $this->assertSame(1, substr_count($percs31['output'], 'PSR2.Files.ClosingTag.NotAllowed'));
        $this->assertStringNotContainsString('Zend.Files.ClosingTag', $percs31['output']);
        $this->assertSame(1, substr_count($jumbo['output'], 'Zend.Files.ClosingTag.NotAllowed'));
        $this->assertStringNotContainsString('PSR2.Files.ClosingTag', $jumbo['output']);
    }

    /**
     * Test that a class constant with no visibility is an error in PERCS31, as PER says MUST, not PSR-12's warning.
     */
    public function testConstantVisibilityIsAnError(): void
    {
        $file = $this->copyToTempFile(null, "<?php\n\nclass Holder\n{\n    const LIMIT = 1;\n}\n");

        try {
            $result = $this->runTool(
                'phpcs',
                ['-s', '--standard=PERCS31', '--sniffs=PSR12.Properties.ConstantVisibility', '--report=emacs', $file],
            );
        } finally {
            unlink($file);
        }

        $this->assertStringContainsString('error - Visibility must be declared', $result['output']);
    }

    /**
     * Test that phpcbf doesn't fail on section 4.9's "not allowed" promoted hook, which only a person can fix, and that
     * it's still reported.
     */
    public function testPhpcbfDoesNotLoopOnTooComplexPromotedHook(): void
    {
        $source = "<?php\n\nclass Example\n{\n    public function __construct(\n        public string \$name {\n"
            . "            set {\n                \$this->name = ucfirst(\$value);\n            }\n        },\n"
            . "    ) {}\n}\n";
        $file = $this->copyToTempFile(null, $source);

        try {
            $fixer = $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
        $this->assertStringContainsString('PromotedPropertyHooks.TooComplex', $result['output']);
        $this->assertStringNotContainsString('MultiLineFunctionDeclaration.Indent', $result['output']);
    }

    /**
     * Test that a heredoc on the line after `=`, or after an `=` on the next line, is fixed to `$x = <<<'EOT'`, with
     * OperatorPlacement leaving the `=` alone, and the result passes.
     */
    public function testHeredocAfterAssignmentIsJoinedToItsVariable(): void
    {
        $body = "<<<'EOT'\n    Hello\n    EOT;\n";
        $file = $this->copyToTempFile(null, "<?php\n\n\$a =\n$body\n\$b\n    = $body");

        try {
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertSame("<?php\n\n\$a = $body\n\$b = $body", $fixed);
        $this->assertStringNotContainsString('HeredocIndentation', $result['output']);
        $this->assertStringNotContainsString('OperatorPlacement', $result['output']);
    }

    /**
     * Test that a trailing comma in an attribute block on one line is an error, and that one in a split block of one
     * attribute isn't, as section 2.6 of PER requires it. Universal.Attributes.TrailingComma forbids it by default.
     */
    public function testAttributeTrailingComma(): void
    {
        $source = "<?php\n\n#[Foo, Bar,]\nfunction a(): void {}\n\n#[Foo(\n    1,\n),]\nfunction b(): void {}\n";
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool(
                'phpcs',
                ['-s', '--standard=PERCS31', '--sniffs=Universal.Attributes.TrailingComma', '--report=emacs', $file],
            );
        } finally {
            unlink($file);
        }

        $this->assertMatchesRegularExpression('/:3:\\d+: error - .*ForbiddenSingleLine/', $result['output']);
        $this->assertStringNotContainsString('ForbiddenSingleAttributeMultiLine', $result['output']);
    }

    /**
     * Test that PERCS31 allows one blank line between parameters, and reports every other blank line in a multi-line
     * declaration. The parent's EmptyLine, which reports them all, is excluded in the ruleset.
     */
    public function testBlankLineAllowedOnlyBetweenParameters(): void
    {
        $fixture = dirname(__DIR__, 2) . '/Jumbo/Tests/Ruleset/BlankLinesInDeclaration.inc';
        $sniff = '--sniffs=PERCS31.Functions.MultiLineFunctionDeclaration';
        $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', $sniff, '--report=emacs', $fixture]);

        preg_match_all('/:(\\d+):\\d+: error - .*\\.BlankLine\\)/', $result['output'], $matches);
        $this->assertSame(['16', '20', '32', '41'], $matches[1], $result['output']);
        $this->assertStringNotContainsString('EmptyLine', $result['output']);
    }

    /**
     * Test that an UPPER_CASE enum constant's warning is hidden by default, and shown at severity 4.
     */
    public function testEnumConstantCaseWarningNeedsWarningSeverity4(): void
    {
        $fixture = dirname(__DIR__, 2) . '/PERCS31/Tests/NamingConventions/UpperCaseConstantNameUnitTest.inc';
        $args = ['-s', '--standard=PERCS31', '--sniffs=PERCS31.NamingConventions.UpperCaseConstantName', $fixture];
        $default = $this->runTool('phpcs', ['--report=emacs', ...$args]);
        $severity4 = $this->runTool('phpcs', ['--report=emacs', '--warning-severity=4', ...$args]);

        $this->assertStringNotContainsString('EnumConstantNotPascalCase', $default['output']);
        $this->assertMatchesRegularExpression(
            '/:21:\\d+: warning - .*EnumConstantNotPascalCase/',
            $severity4['output'],
        );
        $this->assertSame(1, substr_count($severity4['output'], 'EnumConstantNotPascalCase'), 'PascalCase passes.');
    }

    /**
     * Test that an acronym's warning is hidden by default in PERCS31, and shown at severity 4, only on such names.
     */
    public function testAcronymWarningNeedsWarningSeverity4(): void
    {
        $fixture = dirname(__DIR__, 2) . '/PERCS31/Tests/NamingConventions/AcronymCasingUnitTest.inc';
        $args = ['-s', '--standard=PERCS31', '--sniffs=PERCS31.NamingConventions.AcronymCasing', $fixture];
        $default = $this->runTool('phpcs', ['--report=emacs', ...$args]);
        $severity4 = $this->runTool('phpcs', ['--report=emacs', '--warning-severity=4', ...$args]);

        $this->assertStringNotContainsString('AcronymNotLikeWord', $default['output']);
        preg_match_all('/:(\\d+):\\d+: warning - .*AcronymNotLikeWord/', $severity4['output'], $matches);
        $expected = [
            '50',
            '52',
            '54',
            '56',
            '58',
            '61',
            '63',
            '65',
            '67',
            '69',
            '71',
            '74',
            '109',
            '111',
            '115',
            '116',
            '121',
            '123',
            '125',
            '125',
            '127',
            '127',
            '129',
            '129',
            '130',
            '135',
            '147',
            '149',
            '164',
            '166',
            '168',
        ];
        $this->assertSame($expected, $matches[1], $severity4['output']);
    }

    /**
     * Test that Jumbo shows an acronym's warning by default, still as a warning.
     */
    public function testAcronymWarningIsShownInJumbo(): void
    {
        $file = $this->copyToTempFile(null, "<?php\n\n\$userID = 1;\n");

        try {
            $result = $this->runTool(
                'phpcs',
                ['-s', '--standard=Jumbo', '--sniffs=PERCS31.NamingConventions.AcronymCasing', '--report=emacs', $file],
            );
        } finally {
            unlink($file);
        }

        $this->assertMatchesRegularExpression('/:3:\\d+: warning - .*AcronymNotLikeWord/', $result['output']);
    }

    /**
     * Test that PERCS31.Files.LineLength's warning over 80 characters is hidden by default, and shown at severity 4.
     */
    public function testLineLengthRecommendedLimitNeedsWarningSeverity4(): void
    {
        $fixture = dirname(__DIR__, 2) . '/PERCS31/Tests/Files/LineLengthUnitTest.inc';
        $args = ['-s', '--standard=PERCS31', '--sniffs=PERCS31.Files.LineLength', '--report=emacs', $fixture];
        $default = $this->runTool('phpcs', $args);
        $severity4 = $this->runTool('phpcs', ['--warning-severity=4', ...$args]);

        $this->assertStringNotContainsString('OverRecommended', $default['output']);
        foreach ([9, 10, 11] as $line) {
            $this->assertMatchesRegularExpression("/:$line:\\d+: warning - .*OverRecommended/", $severity4['output']);
        }

        $this->assertStringNotContainsString(':12:', $severity4['output'], 'An unbreakable comment is exempt.');
    }

    #endregion

    #region Configurable property tests.

    /**
     * Test that ScopeIndent's indent property, set in a ruleset, changes the expected indent.
     *
     * ScopeIndent checks the whole file when it reaches the open tag, before any `phpcs:set` comment, so this
     * property can't be set in a sniff test's `.inc` file.
     */
    public function testScopeIndentIndentProperty(): void
    {
        $fixture = self::FIXTURES . '/ScopeIndentTwoSpaces.inc';
        $ruleset = $this->copyToTempFile(null, <<<'XML'
            <?xml version="1.0"?>
            <ruleset name="ScopeIndentTwoSpaces">
                <rule ref="PERCS31.WhiteSpace.ScopeIndent">
                    <properties>
                        <property name="indent" value="2"/>
                    </properties>
                </rule>
            </ruleset>
            XML, '.xml');

        try {
            $twoSpaces = $this->runTool('phpcs', ["--standard=$ruleset", $fixture]);
            $default = $this->runTool(
                'phpcs',
                ['--standard=Jumbo', '--sniffs=PERCS31.WhiteSpace.ScopeIndent', $fixture],
            );
        } finally {
            unlink($ruleset);
        }

        $this->assertSame(0, $twoSpaces['exitCode'], $twoSpaces['output']);
        $this->assertNotSame(0, $default['exitCode']);
    }

    #endregion

    #region Helper methods.

    /**
     * Run phpcs or phpcbf from vendor/bin with the current PHP binary.
     *
     * @param string $tool The tool name, 'phpcs' or 'phpcbf'.
     * @param list<string> $args The command-line arguments.
     * @return array{exitCode: int, output: string} The exit code and standard output.
     */
    private function runTool(string $tool, array $args): array
    {
        $command = [PHP_BINARY, dirname(__DIR__, 3) . "/vendor/bin/$tool", '-q', ...$args];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if ($process === false) {
            throw new RuntimeException("Couldn't run $tool.");
        }

        $output = (string) stream_get_contents($pipes[1]) . (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['exitCode' => proc_close($process), 'output' => $output];
    }

    /**
     * Write a file's contents, or the given contents, to a new temporary file.
     *
     * @param ?string $source The file to copy, or null to use $contents.
     * @param string $contents The contents to write when $source is null.
     * @param string $extension The temporary file's extension.
     * @return string The temporary file's path.
     */
    private function copyToTempFile(?string $source, string $contents = '', string $extension = '.php'): string
    {
        $path = sys_get_temp_dir() . '/mossy2100-cs-' . bin2hex(random_bytes(8)) . $extension;
        file_put_contents($path, $source !== null ? file_get_contents($source) : $contents);

        return $path;
    }

    #endregion
}
