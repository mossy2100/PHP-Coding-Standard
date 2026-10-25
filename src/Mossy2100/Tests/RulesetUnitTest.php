<?php

declare(strict_types=1);

namespace Mossy2100\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests of the full Mossy2100 ruleset, and of sniff properties that can only be set in a ruleset.
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
                <rule ref="Mossy2100.WhiteSpace.ScopeIndent">
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
                ['--standard=Mossy2100', '--sniffs=Mossy2100.WhiteSpace.ScopeIndent', $fixture]
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
