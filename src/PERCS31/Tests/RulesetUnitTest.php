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
     * Test that several traits in one use statement are reported when the statement has a block, which
     * PSR12.Traits.UseDeclaration misses.
     */
    public function testSeveralTraitsInUseWithBlockAreReported(): void
    {
        $source = "<?php\n\nclass Example\n{\n    use A, B {\n        A::a insteadof B;\n    }\n}\n";
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertStringContainsString(
            'SlevomatCodingStandard.Classes.TraitUseDeclaration.MultipleTraitsPerDeclaration',
            $result['output'],
        );
    }

    /**
     * Test that phpcbf puts two short hooks on one line on lines of their own. DisallowMultipleStatements only moves
     * the second, so PropertyHookLayout has to move the first.
     */
    public function testPhpcbfSplitsTwoShortHooksOnOneLine(): void
    {
        $source = "<?php\n\nclass Example\n{\n    public string \$name { get => 'a'; set => trim(\$value); }\n}\n";
        $file = $this->copyToTempFile(null, $source);

        try {
            $this->runTool('phpcbf', ['--standard=PERCS31', '--exclude=PSR1.Classes.ClassDeclaration', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $expected = "<?php\n\nclass Example\n{\n    public string \$name {\n        get => 'a';\n"
            . "        set => trim(\$value);\n    }\n}\n";
        $this->assertSame($expected, $fixed);
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
     * Test that a first-class callable split over lines is fixed to `foo(...)`, as section 4.8 requires, with no comma
     * added after the `...`, which Slevomat's trailing comma sniff did. With a comment in the way, it's left alone.
     */
    public function testSplitFirstClassCallableIsJoinedWithNoComma(): void
    {
        $commented = "\$b = strlen(\n    // Why.\n    ...\n);\n";
        $file = $this->copyToTempFile(null, "<?php\n\n\$a = strlen(\n    ...\n);\n$commented");

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertSame(2, substr_count($result['output'], 'FunctionCallLayout.SplitFirstClassCallable'));
        $this->assertStringNotContainsString('MissingTrailingComma', $result['output']);
        $this->assertSame("<?php\n\n\$a = strlen(...);\n$commented", $fixed);
    }

    /**
     * Test that a template whose only PHP tag shares the first line with a statement passes, and isn't changed, as
     * section 3 lets the tag share its line in a file with markup. A PHP file with no markup is still reported.
     */
    public function testTemplateFirstLineTagMayShareItsLine(): void
    {
        $template = "<?php require 'header.php'; ?>\n<main>\n    <p>Hello</p>\n</main>\n";
        $file = $this->copyToTempFile(null, $template);
        $phpOnly = $this->copyToTempFile(null, "<?php require 'header.php';\n");
        $args = ['-s', '--standard=PERCS31', '--report=emacs'];

        try {
            $result = $this->runTool('phpcs', [...$args, $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
            $phpOnlyResult = $this->runTool('phpcs', [...$args, $phpOnly]);
        } finally {
            unlink($file);
            unlink($phpOnly);
        }

        $this->assertSame(0, $result['exitCode'], $result['output']);
        $this->assertSame($template, $fixed);
        $this->assertStringContainsString('PERCS31.Files.FileHeader.SpacingAfterTagBlock', $phpOnlyResult['output']);
    }

    /**
     * Test that an attribute on a readonly anonymous class is fixed to section 8's layout, with `readonly class` on the
     * line after the attributes, and the body indented from it.
     */
    public function testAttributeOnReadonlyAnonClassIsFixedToSectionEightLayout(): void
    {
        $file = $this->copyToTempFile(null, "<?php\n\n\$a = new #[A] readonly class {\n    public int \$b;\n};\n");

        try {
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $expected = "<?php\n\n\$a = new\n    #[A]\n    readonly class {\n        public int \$b;\n    };\n";
        $this->assertSame($expected, $fixed);
        $this->assertSame(0, $result['exitCode'], $result['output']);
    }

    /**
     * Test that a closure's `use` on the line after its parameter list's closing parenthesis is joined to that line.
     * The two inherited fixes for it undid each other until phpcbf failed.
     */
    public function testClosureUseOnItsOwnLineIsJoined(): void
    {
        $file = $this->copyToTempFile(null, "<?php\n\n\$a = function (\n    \$b,\n)\nuse (\$c) {\n    return 1;\n};\n");

        try {
            $fixer = $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
        $this->assertSame("<?php\n\n\$a = function (\n    \$b,\n) use (\$c) {\n    return 1;\n};\n", $fixed);
    }

    /**
     * Test the blank lines around trait use statements: none between two, exactly one after the last, and none before
     * the class's closing brace, each reported once, and fixed.
     */
    public function testBlankLinesAroundTraitUse(): void
    {
        $header = "<?php\n\nnamespace Vendor\\Package;\n\n";
        $source = $header . "class Between\n{\n    use First;\n\n    use Second;\n\n\n    public int \$a = 1;\n}\n";
        $file = $this->copyToTempFile(null, $source);
        $last = $this->copyToTempFile(null, $header . "class Last\n{\n    use First;\n    public int \$a = 1;\n}\n");
        $args = ['-s', '--standard=PERCS31', '--report=emacs'];

        try {
            $result = $this->runTool('phpcs', [...$args, $file]);
            $lastResult = $this->runTool('phpcs', [...$args, $last]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
            unlink($last);
        }

        $this->assertSame(1, substr_count($result['output'], 'TraitUseSpacing.IncorrectLinesCountBetweenUses'));
        $this->assertSame(1, substr_count($result['output'], 'TraitUseSpacing.IncorrectLinesCountAfterLastUse'));
        $this->assertSame(1, substr_count($lastResult['output'], 'error - '), $lastResult['output']);
        $expected = $header . "class Between\n{\n    use First;\n    use Second;\n\n    public int \$a = 1;\n}\n";
        $this->assertSame($expected, $fixed);
    }

    /**
     * Test that a control structure's empty body passes as `{}`, and with the braces on separate lines, as section 5
     * has no rule for it. A space between the braces, and a body on the brace's line, are still reported.
     */
    public function testEmptyControlStructureBodyMayBeAbbreviated(): void
    {
        $valid = "<?php\n\ntry {\n    run();\n} catch (Exception \$e) {} finally {\n}\n\nwhile (next(\$items)) {}\n";
        $file = $this->copyToTempFile(null, $valid);
        $invalid = $this->copyToTempFile(null, "<?php\n\nwhile (next(\$items)) { }\n\nif (\$first) { run();\n}\n");
        $args = ['-s', '--standard=PERCS31', '--report=emacs'];

        try {
            $result = $this->runTool('phpcs', [...$args, $file]);
            $invalidResult = $this->runTool('phpcs', [...$args, $invalid]);
        } finally {
            unlink($file);
            unlink($invalid);
        }

        $this->assertSame(0, $result['exitCode'], $result['output']);
        $this->assertSame(2, substr_count($invalidResult['output'], 'BodyOnNextLine.NewlineAfterOpenBrace'));
    }

    /**
     * Test that a trailing comma after a single argument split over lines is removed, as section 4.7 says that doesn't
     * split the argument list, and that a split list keeps its comma.
     */
    public function testNoTrailingCommaAfterSingleArgumentSplitOverLines(): void
    {
        $split = "\$b = sum(\n    [1, 2],\n    \$precision,\n);\n";
        $file = $this->copyToTempFile(null, "<?php\n\n\$a = sum([\n    1,\n    2,\n],);\n$split");

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertSame(1, substr_count($result['output'], 'DisallowTrailingCommaInCall.DisallowedTrailingComma'));
        $this->assertSame(1, substr_count($result['output'], 'error - '), $result['output']);
        $this->assertSame("<?php\n\n\$a = sum([\n    1,\n    2,\n]);\n$split", $fixed);
    }

    /**
     * Test that a loop with no body passes, as there's no body for section 5 to require braces around.
     */
    public function testLoopWithNoBodyPasses(): void
    {
        $file = $this->copyToTempFile(null, "<?php\n\nwhile (next(\$items));\n\nfor (\$i = 0; \$i < 10; \$i++);\n");

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertSame(0, $result['exitCode'], $result['output']);
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
     * Test that a non-canonical cast is reported once, and fixed to its canonical form. A `(binary)` cast becomes
     * `(string)`, and isn't deleted, as the Slevomat sniff's fix would.
     */
    public function testNonCanonicalCastIsReportedOnce(): void
    {
        $source = "<?php\n\n\$a = (double) \$x;\n\$b = (integer) \$x;\n\$c = (boolean) \$x;\n\$d = (binary) \$x;\n";
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        preg_match_all('/:(\d+):\d+: error - .*\.(\w+)\)$/m', $result['output'], $matches, PREG_SET_ORDER);
        $reports = array_map(static fn(array $match): string => "$match[1] $match[2]", $matches);
        $expected = "<?php\n\n\$a = (float) \$x;\n\$b = (int) \$x;\n\$c = (bool) \$x;\n\$d = (string) \$x;\n";

        $reported = ['3 InvalidCastUsed', '4 InvalidCastUsed', '5 InvalidCastUsed', '6 InvalidCastUsed'];

        $this->assertSame($reported, $reports, $result['output']);
        $this->assertStringContainsString('Cast "(binary)" is forbidden, use "(string)" instead.', $result['output']);
        $this->assertSame($expected, $fixed);
    }

    /**
     * Test that a comma starting a line in a call's arguments is reported once, including after a heredoc's closing
     * marker, as is the spacing around a comma in an array inside the call, and that the fix corrects each one.
     */
    public function testCommaSpacingInCallIsReportedOnceAndFixed(): void
    {
        $heredoc = "    <<<'EOT'\n        x\n        EOT";
        $arrays = "    [1 , 2],\n    [3,4],\n    [5,  6],\n";
        $source = "<?php\n\nfoo(\n$heredoc\n    ,\n    \$a\n    ,\n$arrays);\n";
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        preg_match_all('/:(\d+):\d+: error - .*\.(\w+)\)$/m', $result['output'], $matches, PREG_SET_ORDER);
        $reports = array_map(static fn(array $match): string => "$match[1] $match[2]", $matches);
        $expected = [
            '7 SpaceBeforeInFunctionCall',
            '9 SpaceBeforeInFunctionCall',
            '10 SpaceBeforeInFunctionCall',
            '11 NoSpaceAfterInFunctionCall',
            '12 TooMuchSpaceAfterInFunctionCall',
        ];
        $fixedArrays = "    [1, 2],\n    [3, 4],\n    [5, 6],\n";

        $this->assertSame($expected, $reports, $result['output']);
        $this->assertSame("<?php\n\nfoo(\n$heredoc,\n    \$a,\n$fixedArrays);\n", $fixed);
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
