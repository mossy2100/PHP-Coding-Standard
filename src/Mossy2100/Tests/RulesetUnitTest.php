<?php

declare(strict_types=1);

namespace Mossy2100\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests of the full Mossy2100 and PERCS31 rulesets, and of sniff properties that can only be set in a ruleset.
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
        $result = $this->runTool('phpcs', ['--standard=Mossy2100', '--report=json', self::FIXTURES . '/Good.inc']);

        $this->assertSame(0, $result['exitCode'], $result['output']);
    }

    /**
     * Test that the bad fixture has violations, so the phpcbf test below has something to fix.
     */
    public function testBadFixtureHasViolations(): void
    {
        $result = $this->runTool('phpcs', ['--standard=Mossy2100', '--report=json', self::FIXTURES . '/Bad.inc']);

        $this->assertNotSame(0, $result['exitCode']);
    }

    /**
     * Test that phpcbf fixes every violation in the bad fixture, giving exactly the good fixture.
     */
    public function testPhpcbfFixesBadFixtureToGood(): void
    {
        $file = $this->copyToTempFile(self::FIXTURES . '/Bad.inc');

        try {
            $this->runTool('phpcbf', ['--standard=Mossy2100', $file]);
            $this->assertSame(file_get_contents(self::FIXTURES . '/Good.inc'), file_get_contents($file));
        } finally {
            unlink($file);
        }
    }

    #endregion

    #region PERCS31 ruleset tests.

    /**
     * Test that code following the Mossy2100 standard also follows PERCS31, which Mossy2100 builds on.
     */
    public function testGoodFixturePassesPercs31(): void
    {
        $result = $this->runTool('phpcs', ['--standard=PERCS31', '--report=json', self::FIXTURES . '/Good.inc']);

        $this->assertSame(0, $result['exitCode'], $result['output']);
    }

    /**
     * Test that code following PER Coding Style, but not the house rules, passes PERCS31 and fails Mossy2100.
     */
    public function testHouseStyleFixturePassesPercs31Only(): void
    {
        $fixture = dirname(__DIR__, 2) . '/PERCS31/Tests/Ruleset/HouseStyle.inc';
        $percs31 = $this->runTool('phpcs', ['--standard=PERCS31', '--report=json', $fixture]);
        $mossy2100 = $this->runTool('phpcs', ['--standard=Mossy2100', '--report=json', $fixture]);

        $this->assertSame(0, $percs31['exitCode'], $percs31['output']);
        $this->assertNotSame(0, $mossy2100['exitCode']);
    }

    /**
     * Test that PERCS31 allows more than one space around an operator, as PER does, and Mossy2100 doesn't.
     */
    public function testOperatorSpacingAllowsSeveralSpacesInPercs31Only(): void
    {
        $file = $this->copyToTempFile(null, "<?php\n\n\$total = \$price  +  \$tax;\n\$valid = \$a  &&  \$b;\n");
        $args = ['-s', '--sniffs=PERCS31.Operators.OperatorSpacing', '--report=emacs', $file];

        try {
            $percs31 = $this->runTool('phpcs', ['--standard=PERCS31', ...$args]);
            $mossy2100 = $this->runTool('phpcs', ['--standard=Mossy2100', ...$args]);
        } finally {
            unlink($file);
        }

        $this->assertSame(0, $percs31['exitCode'], $percs31['output']);
        $this->assertSame(4, substr_count($mossy2100['output'], 'PERCS31.Operators.OperatorSpacing.Spacing'));
    }

    /**
     * Test that PERCS31 allows one blank line before a parameter's attributes, and reports every other blank line in a
     * multi-line declaration. The parent's EmptyLine, which reports them all, is excluded in the ruleset.
     */
    public function testBlankLineAllowedOnlyBeforeParameterAttributes(): void
    {
        $fixture = dirname(__DIR__, 2) . '/PERCS31/Tests/Ruleset/BlankLinesInDeclaration.inc';
        $sniff = '--sniffs=PERCS31.Functions.MultiLineFunctionDeclaration';
        $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', $sniff, '--report=emacs', $fixture]);

        preg_match_all('/:(\\d+):\\d+: error - .*\\.BlankLine\\)/', $result['output'], $matches);
        $this->assertSame(['12', '15', '19', '31'], $matches[1], $result['output']);
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
                ['--standard=Mossy2100', '--sniffs=PERCS31.WhiteSpace.ScopeIndent', $fixture],
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
