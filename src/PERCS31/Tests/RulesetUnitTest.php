<?php

declare(strict_types=1);

namespace PERCS31\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Tests of the full PERCS31 ruleset, and of sniff properties that can only be set in a ruleset.
 *
 * The per-sniff tests run each sniff alone. These run phpcs and phpcbf as a user would, so they catch conflicts
 * between sniffs, such as two fixers undoing each other's changes. Tests comparing PERCS31 with Jumbo are in Jumbo's
 * RulesetUnitTest.
 */
#[CoversNothing]
class RulesetUnitTest extends TestCase
{
    use RunsToolsTrait;

    /**
     * The fixture directory.
     */
    private const string FIXTURES = __DIR__ . '/Ruleset';

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
     * Test that a comma inside parentheses in a constant's value isn't read as a second constant, and a real second
     * constant is fixed with its modifiers and type kept. These need Slevomat 8.31.3.
     */
    public function testConstantsPerStatement(): void
    {
        $source = "<?php\n\nconst I = new Complex(0, 1);\n\n"
            . "class Holder\n{\n    final public const int A = 1, B = 2;\n}\n";
        $file = $this->copyToTempFile(null, $source);
        $args = ['-s', '--standard=PERCS31', '--sniffs=SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition'];

        try {
            $result = $this->runTool('phpcs', [...$args, '--report=emacs', $file]);
            $this->runTool('phpcbf', [...$args, $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertSame(1, substr_count($result['output'], 'DisallowMultiConstantDefinition'), $result['output']);
        $this->assertStringContainsString("final public const int A = 1;\n    final public const int B = 2;", $fixed);
    }

    /**
     * Test that a `static|null` return type is fixed to `?static`, with null in either position, and still parses.
     * Slevomat's fix for it left `?` and `?null`, so PERCS31.TypeHints.NullType makes the check instead.
     */
    public function testStaticOrNullReturnTypeIsFixedToShortNullable(): void
    {
        $source = "<?php\n\nnamespace Vendor\\Package;\n\nclass Fluent\n{\n"
            . "    public function next(): static|null\n    {\n        return null;\n    }\n\n"
            . "    public function back(): null|static\n    {\n        return null;\n    }\n}\n";
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
            $after = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertSame(2, substr_count($result['output'], 'warning - A union of one type and null'));
        $this->assertSame(1, substr_count($result['output'], 'PERCS31.TypeHints.NullType.NullNotLast'));
        $this->assertStringNotContainsString('SlevomatCodingStandard.TypeHints.DNFTypeHintFormat', $result['output']);
        $this->assertSame(str_replace(['static|null', 'null|static'], '?static', $source), $fixed);
        $this->assertSame(0, $after['exitCode'], $after['output']);
    }

    /**
     * Test that a one-line block declare is fixed with no stray blank line, which two fixes for its brace would add.
     */
    public function testOneLineBlockDeclareIsFixedWithNoBlankLine(): void
    {
        $file = $this->copyToTempFile(null, "<?php\n\ndeclare(ticks=1) { echo 1; }\n");

        try {
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertSame("<?php\n\ndeclare(ticks=1) {\n    echo 1;\n}\n", $fixed);
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
     * Test that section 12.4's whole example has no blank line or attribute errors, with a blank line before `int $d`.
     */
    public function testSpecAttributesExamplePasses(): void
    {
        $fixture = self::FIXTURES . '/AttributesExample.inc';
        $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $fixture]);

        $this->assertStringNotContainsString('MultiLineFunctionDeclaration', $result['output']);
        $this->assertStringNotContainsString('AttributePlacement', $result['output']);
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
}
