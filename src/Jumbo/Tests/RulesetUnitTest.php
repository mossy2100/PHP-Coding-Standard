<?php

declare(strict_types=1);

namespace Jumbo\Tests;

use PERCS31\Tests\RunsToolsTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests of the full Jumbo and PERCS31 rulesets, and of sniff properties that can only be set in a ruleset.
 *
 * The per-sniff tests run each sniff alone. These run phpcs and phpcbf as a user would, so they catch conflicts
 * between sniffs, such as two fixers undoing each other's changes.
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
     * The option that turns off Squiz.Classes.ClassFileName, for a test that runs Jumbo on a fixture or a temporary
     * file, whose name never matches the class in it.
     */
    private const string EXCLUDE_CLASS_FILE_NAME = '--exclude=Squiz.Classes.ClassFileName';

    #region Full ruleset tests.

    /**
     * Test that code following the standard has no violations.
     */
    public function testGoodFixtureHasNoViolations(): void
    {
        $good = self::FIXTURES . '/Good.inc';
        $result = $this->runTool('phpcs', ['--standard=Jumbo', '--report=json', self::EXCLUDE_CLASS_FILE_NAME, $good]);

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

    /**
     * Test that Jumbo.Formatting.ExpressionFormat and the sniffs that check the same lines agree: phpcbf fixes the bad
     * fixture to exactly the good one, without failing to finish, and the good one has no violations.
     *
     * ExpressionFormat works out each new line's indent for the layout it writes, and
     * PERCS31.Operators.OperatorPlacement checks the indent of the layout as it's written. A difference between their
     * rules would leave them undoing each other's fixes. The fixture covers the places where those rules differ:
     * conditions, a foreach header, groups, an argument, an arrow function, a match arm and a ternary.
     */
    public function testExpressionFormatAgreesWithTheOtherSniffs(): void
    {
        $good = self::FIXTURES . '/ExpressionFormatGood.inc';
        $file = $this->copyToTempFile(self::FIXTURES . '/ExpressionFormatBad.inc');

        try {
            $fixer = $this->runTool('phpcbf', ['--standard=Jumbo', $file]);
            $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
            $this->assertSame(file_get_contents($good), file_get_contents($file));
        } finally {
            unlink($file);
        }

        $result = $this->runTool('phpcs', ['--standard=Jumbo', '--report=emacs', self::EXCLUDE_CLASS_FILE_NAME, $good]);
        $this->assertSame(0, $result['exitCode'], $result['output']);
    }

    #endregion

    #region Tests comparing PERCS31 and Jumbo.

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
     * Test that PERCS31 only reports whitespace after a reference `&` and a spread `...`, as PER leaves the other unary
     * operators undefined, and Jumbo reports it after `+`, `-`, `!`, `~` and `@` too. A spread is reported by
     * Generic.WhiteSpace.SpreadOperatorSpacingAfter. A variadic parameter's `...` is reported once, and a first-class
     * callable's isn't a spread.
     */
    public function testUnaryOperatorSpacingChecksOnlyReferenceAndSpreadInPercs31(): void
    {
        $source = "<?php\n\n\$a = & \$b;\n\$c = + \$b;\n\$d = - \$b;\n\$e = ! \$b;\n\$f = ~ \$b;\n\$g = @ foo();\n\$h "
            . "= foo(... \$b);\n\$i = fn(int ... \$j): array => \$j;\n\$k = foo(...);\n";
        $file = $this->copyToTempFile(null, $source);
        $sniffs = 'PERCS31.Operators.UnaryOperatorSpacing,Generic.WhiteSpace.SpreadOperatorSpacingAfter,Squiz.Functions'
            . '.FunctionDeclarationArgumentSpacing';
        $args = ['-s', "--sniffs={$sniffs}", '--report=emacs', $file];

        try {
            $percs31 = $this->runTool('phpcs', ['--standard=PERCS31', ...$args]);
            $jumbo = $this->runTool('phpcs', ['--standard=Jumbo', ...$args]);
        } finally {
            unlink($file);
        }

        $code = 'PERCS31.Operators.UnaryOperatorSpacing.WhitespaceAfter';

        $spreadCode = 'Generic.WhiteSpace.SpreadOperatorSpacingAfter.TooMuchSpace';

        $this->assertSame(1, substr_count($percs31['output'], $code), $percs31['output']);
        $this->assertSame(2, substr_count($percs31['output'], $spreadCode), $percs31['output']);
        $this->assertStringContainsString(':3:', $percs31['output']);
        $this->assertStringContainsString(':9:', $percs31['output']);
        $this->assertStringContainsString(':10:', $percs31['output']);
        $this->assertStringNotContainsString('SpacingAfterVariadic', $percs31['output']);
        $this->assertSame(6, substr_count($jumbo['output'], $code), $jumbo['output']);
        $this->assertSame(2, substr_count($jumbo['output'], $spreadCode), $jumbo['output']);
        $this->assertStringNotContainsString('SpacingAfterVariadic', $jumbo['output']);
    }

    /**
     * Test that a foreach or match header split over lines takes the split layout in Jumbo, with a foreach's `as`
     * starting a line, and that PERCS31 leaves both alone, as PER has no rule for either. An array that starts the
     * header goes on a line of its own, and phpcbf gets there without the array sniff moving it back.
     */
    public function testSplitForeachAndMatchHeadersTakeTheSplitLayoutInJumboOnly(): void
    {
        $item = "'" . str_repeat('alpha ', 9) . "alpha'";
        $source = "<?php\n\ndeclare(strict_types=1);\n\nforeach ([\n    {$item},\n    {$item},\n] as \$key => \$item) "
            . "{\n    run();\n}\n\n\$result = match (\$this->lookup([\n    {$item},\n    {$item},\n])) {\n    1 => "
            . "'one',\n    default => 'other',\n};\n";
        $expected = "<?php\n\ndeclare(strict_types=1);\n\nforeach (\n    [\n        {$item},\n        {$item},\n    "
            . "]\n    as \$key => \$item\n) {\n    run();\n}\n\n\$result = match (\n    \$this->lookup([\n        "
            . "{$item},\n        {$item},\n    ])\n) {\n    1 => 'one',\n    default => 'other',\n};\n";
        $file = $this->copyToTempFile(null, $source);
        $ignored = '--exclude=PSR1.Files.SideEffects';

        try {
            $percs31 = $this->runTool('phpcs', ['--standard=PERCS31', $ignored, '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=Jumbo', $ignored, $file]);
            $fixed = (string) file_get_contents($file);
            $jumbo = $this->runTool('phpcs', ['--standard=Jumbo', $ignored, '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertSame(0, $percs31['exitCode'], $percs31['output']);
        $this->assertSame($expected, $fixed);
        $this->assertSame(0, $jumbo['exitCode'], $jumbo['output']);
    }

    /**
     * Test that Jumbo allows no blank line at the start of a function's, method's or closure's body, or at either end
     * of a block declare, a property's hook list or a hook's body, and that PERCS31 allows them all, as PER has no rule
     * for any of them.
     */
    public function testBlankLinesAtTheEndsOfABlockAreReportedInJumboOnly(): void
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Vendor\\Package;\n\n";
        $source = "{$header}class Example\n{\n    public string \$name {\n\n        get {\n\n            return "
            . "\$this->name;\n\n        }\n\n    }\n\n    public function run(): int\n    {\n\n        \$double = "
            . "static function (int \$x): int {\n\n            return \$x * 2;\n        };\n\n        return "
            . "\$double(1);\n    }\n}\n";
        $expected = "{$header}class Example\n{\n    public string \$name {\n        get {\n            return "
            . "\$this->name;\n        }\n    }\n\n    public function run(): int\n    {\n        \$double = static "
            . "function (int \$x): int {\n            return \$x * 2;\n        };\n\n        return \$double(1);\n    "
            . "}\n}\n";
        $declare = "<?php\n\ndeclare(strict_types=1);\ndeclare(ticks=1) {\n\n    run();\n\n}\n";
        $file = $this->copyToTempFile(null, $source);
        $declareFile = $this->copyToTempFile(null, $declare);
        $ignored = '--exclude=PSR1.Files.SideEffects';

        try {
            $percs31 = $this->runTool('phpcs', ['--standard=PERCS31', $ignored, '--report=emacs', $file, $declareFile]);
            $jumbo = $this->runTool('phpcs', ['-s', '--standard=Jumbo', $ignored, '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=Jumbo', $ignored, $file, $declareFile]);
            $fixed = (string) file_get_contents($file);
            $fixedDeclare = (string) file_get_contents($declareFile);
        } finally {
            unlink($file);
            unlink($declareFile);
        }

        $this->assertSame(0, $percs31['exitCode'], $percs31['output']);
        $this->assertSame(2, substr_count($jumbo['output'], 'FunctionOpeningBraceSpace.SpacingAfter'));
        $this->assertSame(2, substr_count($jumbo['output'], 'BlockBlankLines.AfterOpeningBrace'), $jumbo['output']);
        $this->assertSame(2, substr_count($jumbo['output'], 'BlockBlankLines.BeforeClosingBrace'), $jumbo['output']);
        $this->assertSame($expected, $fixed);
        $this->assertSame(str_replace(["{\n\n", "\n\n}"], ["{\n", "\n}"], $declare), $fixedDeclare);
    }

    /**
     * Test that Jumbo allows no more than one blank line in a row, between statements and between a list's items, and
     * PERCS31 any number. A blank line in a nowdoc or a block comment is part of it, so it's left alone.
     */
    public function testConsecutiveBlankLinesAreReportedInJumboOnly(): void
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Vendor\\Package;\n\n";
        $kept = "\$text = <<<'EOT'\n    one\n\n\n    two\n    EOT;\n\n/*\n * One.\n\n\n * Two.\n */\n";
        $argument = "    'an argument long enough that the call cannot be joined onto a single line by Jumbo',\n";
        $call = "send(\n    \$text,\n%s{$argument}{$argument});\n";
        $file = $this->copyToTempFile(null, "{$header}{$kept}\n\n" . sprintf($call, "\n\n\n"));
        $ignored = '--exclude=PSR1.Files.SideEffects';

        try {
            $percs31 = $this->runTool('phpcs', ['--standard=PERCS31', $ignored, '--report=emacs', $file]);
            $jumbo = $this->runTool('phpcs', ['-s', '--standard=Jumbo', $ignored, '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=Jumbo', $ignored, $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $reported = substr_count($jumbo['output'], 'PhpCollective.WhiteSpace.EmptyLines');

        $this->assertSame(0, $percs31['exitCode'], $percs31['output']);
        $this->assertSame(3, $reported, $jumbo['output']);
        $this->assertSame("{$header}{$kept}\n" . sprintf($call, "\n"), $fixed);
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
     * Test that a control structure's empty body with the braces on separate lines passes in PERCS31, and is an error
     * in Jumbo, whose fix writes it as `{}`.
     */
    public function testEmptyControlStructureBodyMustBeAbbreviatedInJumbo(): void
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Vendor\\Package;\n\n";
        $file = $this->copyToTempFile(null, "{$header}while (next(\$items)) {\n}\n");
        $sniff = '--sniffs=PERCS31.Formatting.EmptyBody';

        try {
            $per = $this->runTool('phpcs', ['--standard=PERCS31', $sniff, '-s', '--report=emacs', $file])['output'];
            $jumbo = $this->runTool('phpcs', ['--standard=Jumbo', $sniff, '-s', '--report=emacs', $file])['output'];
            $this->runTool('phpcbf', ['--standard=Jumbo', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertStringNotContainsString('EmptyBody', $per);
        $this->assertStringContainsString('error - An empty while body must be written as {}', $jumbo);
        $this->assertSame("{$header}while (next(\$items)) {}\n", $fixed);
    }

    /**
     * Test that a blank line after an anonymous class's opening brace passes in PERCS31, which gives it the closure
     * rules, and is an error in Jumbo. One after a named class's brace is an error in both.
     */
    public function testBlankLineAfterAnonClassBraceIsReportedInJumboOnly(): void
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Vendor\\Package;\n\n";
        $anon = "\$a = new class {\n\n    public int \$b = 1;\n};\n";
        $named = "final class Named\n{\n\n    public int \$b = 1;\n}\n";
        $file = $this->copyToTempFile(null, "{$header}{$anon}\n{$named}");

        try {
            $per = $this->runTool('phpcs', ['--standard=PERCS31', '-s', '--report=emacs', $file])['output'];
            $jumbo = $this->runTool('phpcs', ['--standard=Jumbo', '-s', '--report=emacs', $file])['output'];
        } finally {
            unlink($file);
        }

        $this->assertStringNotContainsString(':7:', $per);
        $this->assertStringContainsString(':13:1: error', $per);
        $this->assertStringContainsString(':7:16: error', $jumbo);
    }

    /**
     * Test that a blank line before a split control structure header's closing parenthesis passes in PERCS31, and is
     * an error in Jumbo, whose fix removes it.
     */
    public function testBlankLineBeforeCloseParenthesisIsReportedInJumboOnly(): void
    {
        $source = "<?php\n\nwhile (\n    next(\$items)\n\n) {\n    run();\n}\n";
        $file = $this->copyToTempFile(null, $source);
        $sniff = '--sniffs=PERCS31.ControlStructures.ControlStructureSpacing';

        try {
            $per = $this->runTool('phpcs', ['--standard=PERCS31', $sniff, '-s', '--report=emacs', $file])['output'];
            $jumbo = $this->runTool('phpcs', ['--standard=Jumbo', $sniff, '-s', '--report=emacs', $file])['output'];
            $this->runTool('phpcbf', ['--standard=Jumbo', $sniff, $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertStringNotContainsString('ControlStructureSpacing', $per);
        $this->assertStringContainsString('ControlStructureSpacing.BlankLineBeforeCloseParenthesis', $jumbo);
        $this->assertSame("<?php\n\nwhile (\n    next(\$items)\n) {\n    run();\n}\n", $fixed);
    }

    /**
     * Test that a comment indented too far after a statement passes in PERCS31, which has no rule for a comment's
     * indent, and is an error in Jumbo.
     */
    public function testOverIndentedCommentIsReportedInJumboOnly(): void
    {
        $source = "<?php\n\nfunction total(): int\n{\n    \$a = 1;\n        // Too far.\n    return \$a;\n}\n";
        $file = $this->copyToTempFile(null, $source);
        $sniff = '--sniffs=PERCS31.WhiteSpace.ConsistentIndent';

        try {
            $per = $this->runTool('phpcs', ['--standard=PERCS31', $sniff, '-s', '--report=emacs', $file])['output'];
            $jumbo = $this->runTool('phpcs', ['--standard=Jumbo', $sniff, '-s', '--report=emacs', $file])['output'];
        } finally {
            unlink($file);
        }

        $this->assertStringNotContainsString('ConsistentIndent', $per);
        $this->assertStringContainsString('PERCS31.WhiteSpace.ConsistentIndent.Comment', $jumbo);
    }

    /**
     * Test the blank lines around trait use statements in Jumbo: none between two, exactly one after the last, each
     * reported once, and fixed. PERCS31 allows one between two, and several after the last. A comment at the end of
     * the last use statement's line, before the class's closing brace, passes and is kept, which
     * SlevomatCodingStandard.Classes.TraitUseSpacing's fix deleted.
     */
    public function testBlankLinesAroundTraitUseAreExactInJumbo(): void
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Vendor\\Package;\n\n";
        $source = "{$header}class Between\n{\n    use First;\n\n    use Second;\n\n\n    public int \$a = 1;\n}\n";
        $file = $this->copyToTempFile(null, $source);
        $last = $this->copyToTempFile(null, "{$header}class Last\n{\n    use First;\n    public int \$a = 1;\n}\n");
        $commented = "{$header}class Commented\n{\n    use First; // The first.\n}\n";
        $commentedFile = $this->copyToTempFile(null, $commented);
        $args = ['-s', '--standard=Jumbo', '--report=emacs', self::EXCLUDE_CLASS_FILE_NAME];

        try {
            $result = $this->runTool('phpcs', [...$args, $file]);
            $lastResult = $this->runTool('phpcs', [...$args, $last]);
            $commentedResult = $this->runTool('phpcs', [...$args, $commentedFile]);
            $this->runTool('phpcbf', ['--standard=Jumbo', $file, $commentedFile]);
            $fixed = (string) file_get_contents($file);
            $fixedCommented = (string) file_get_contents($commentedFile);
        } finally {
            unlink($file);
            unlink($last);
            unlink($commentedFile);
        }

        $this->assertSame(1, substr_count($result['output'], 'TraitUseBlock.BlankLineBetweenUses'), $result['output']);
        $this->assertSame(1, substr_count($result['output'], 'WhiteSpace.EmptyLines.EmptyLines'), $result['output']);
        $this->assertSame(1, substr_count($lastResult['output'], 'TraitUseBlock.'), $lastResult['output']);
        $this->assertStringContainsString('TraitUseBlock.NoBlankLineAfterUse', $lastResult['output']);
        $this->assertStringNotContainsString('PSR12.Traits.UseDeclaration', $lastResult['output']);
        $this->assertSame(0, $commentedResult['exitCode'], $commentedResult['output']);
        $expected = "{$header}class Between\n{\n    use First;\n    use Second;\n\n    public int \$a = 1;\n}\n";
        $this->assertSame($expected, $fixed);
        $this->assertSame($commented, $fixedCommented);
    }

    /**
     * Test that a global const statement with two constants, or two spaces after const, is reported and fixed in
     * Jumbo, and passes in PERCS31, as section 4 is about classes. A class's constants are reported in both.
     */
    public function testGlobalConstantsPerStatementInJumboOnly(): void
    {
        $source = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Vendor\\Package;\n\nconst A = 1, B = 2;\nconst  C = "
            . "3;\n";
        $file = $this->copyToTempFile(null, $source);
        $args = ['-s', '--sniffs=PERCS31.Classes.ConstantDeclaration', '--report=emacs'];

        try {
            $percs31 = $this->runTool('phpcs', ['--standard=PERCS31', ...$args, $file]);
            $jumbo = $this->runTool('phpcs', ['--standard=Jumbo', ...$args, $file]);
            $this->runTool('phpcbf', ['--standard=Jumbo', '--sniffs=PERCS31.Classes.ConstantDeclaration', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertSame(0, $percs31['exitCode'], $percs31['output']);
        $this->assertStringContainsString('ConstantDeclaration.DisallowedMultiConstantDefinition', $jumbo['output']);
        $this->assertStringContainsString('ConstantDeclaration.SpaceAfterConst', $jumbo['output']);
        $this->assertStringContainsString("const A = 1;\nconst B = 2;\nconst C = 3;\n", $fixed);
    }

    /**
     * Test that a blank line after a case label is reported in Jumbo only, as PER doesn't forbid one, that a statement
     * on the label's line is reported once in both standards, and that a comment there isn't reported in either.
     */
    public function testBlankLineAfterLabelIsReportedInJumboOnly(): void
    {
        $blank = $this->copyToTempFile(null, "<?php\n\nswitch (\$a) {\n    case 1:\n\n        break;\n}\n");
        $comment = $this->copyToTempFile(null, "<?php\n\nswitch (\$a) {\n    case 1: // Why.\n        break;\n}\n");
        $statement = $this->copyToTempFile(null, "<?php\n\nswitch (\$a) {\n    case 1: break;\n}\n");
        $sniff = '--sniffs=PERCS31.ControlStructures.SwitchDeclaration';

        try {
            $perBlank = $this->runTool('phpcs', ['--standard=PERCS31', $sniff, '-s', $blank])['output'];
            $jumboBlank = $this->runTool('phpcs', ['--standard=Jumbo', $sniff, '-s', $blank])['output'];
            $perComment = $this->runTool('phpcs', ['--standard=PERCS31', $sniff, '-s', $comment])['output'];
            $jumboComment = $this->runTool('phpcs', ['--standard=Jumbo', $sniff, '-s', $comment])['output'];
            $perStatement = $this->runTool('phpcs', ['--standard=PERCS31', $sniff, '-s', $statement])['output'];
            $jumboStatement = $this->runTool('phpcs', ['--standard=Jumbo', $sniff, '-s', $statement])['output'];
        } finally {
            unlink($blank);
            unlink($comment);
            unlink($statement);
        }

        $this->assertStringNotContainsString('SwitchDeclaration', $perBlank);
        $this->assertStringContainsString('SwitchDeclaration.BodyOnNextLineCASE', $jumboBlank);
        $this->assertStringNotContainsString('SwitchDeclaration', $perComment);
        $this->assertStringNotContainsString('SwitchDeclaration', $jumboComment);
        $this->assertSame(1, substr_count($perStatement, 'SwitchDeclaration.'), $perStatement);
        $this->assertStringContainsString('SwitchDeclaration.StatementOnLabelLine', $perStatement);
        $this->assertSame(1, substr_count($jumboStatement, 'SwitchDeclaration.'), $jumboStatement);
        $this->assertStringContainsString('SwitchDeclaration.BodyOnNextLineCASE', $jumboStatement);
    }

    /**
     * Test that a statement after a block declare in the header is one spacing error in both standards, as section 3
     * wants a blank line, and that one after any other control structure is an error in both too.
     */
    public function testStatementAfterClosingBrace(): void
    {
        $block = "<?php\n\ndeclare(strict_types=1);\ndeclare(ticks=1) {\n    echo 1;\n} echo 2;\n";
        $structure = "<?php\n\ndeclare(strict_types=1);\n\nif (\$a) {\n    echo 1;\n} echo 2;\n";
        foreach ([$block, $structure] as $source) {
            $file = $this->copyToTempFile(null, $source);

            try {
                $perCs = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
                $jumbo = $this->runTool('phpcs', ['-s', '--standard=Jumbo', '--report=emacs', $file]);
            } finally {
                unlink($file);
            }

            $isBlock = $source === $block;
            $this->assertSame($isBlock, str_contains($perCs['output'], 'SpacingAfterDeclareBlock'), $perCs['output']);
            $this->assertSame($isBlock, str_contains($jumbo['output'], 'SpacingAfterDeclareBlock'), $jumbo['output']);
            $this->assertSame(!$isBlock, str_contains($perCs['output'], 'StatementAfter'), $perCs['output']);
            $this->assertSame(!$isBlock, str_contains($jumbo['output'], 'StatementAfter'), $jumbo['output']);
        }
    }

    /**
     * Test that a `match` as a call's argument doesn't stop phpcbf finishing, as RequireSingleLineCall would collapse
     * it and other sniffs would expand it, and that a multi-line one is left as it is.
     */
    public function testMatchArgumentDoesNotLoopInPhpcbf(): void
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace A;\n\n";
        $multiLine = "\$d = foo(match (\$x) {\n    1 => 2,\n    default => 3,\n});\n";
        foreach ([$multiLine, "\$d = foo(match (\$x) { 1 => 2, default => 3 });\n"] as $code) {
            $file = $this->copyToTempFile(null, "{$header}{$code}");

            try {
                $fixer = $this->runTool('phpcbf', ['--standard=Jumbo', $file]);
                $fixed = (string) file_get_contents($file);
            } finally {
                unlink($file);
            }

            $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
            if ($code === $multiLine) {
                $this->assertSame("{$header}{$multiLine}", $fixed);
            }
        }
    }

    /**
     * Test that a match arm split before its value is brought onto one line if it fits, in Jumbo. PERCS31 leaves it
     * split, and adds the trailing comma a split list of conditions needs.
     */
    public function testWrappedMatchArmIsJoinedInJumboOnly(): void
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace A;\n\n\$a = match (\$x) {\n";
        $footer = "    default => 9,\n};\n";
        $expectations = ['Jumbo' => "    1, 2 => 3,\n", 'PERCS31' => "    1,\n    2,\n    => 3,\n"];
        foreach ($expectations as $standard => $expected) {
            $file = $this->copyToTempFile(null, "{$header}    1,\n    2\n    => 3,\n{$footer}");

            try {
                $fixer = $this->runTool('phpcbf', ["--standard={$standard}", $file]);
                $fixed = (string) file_get_contents($file);
            } finally {
                unlink($file);
            }

            $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
            $this->assertSame("{$header}{$expected}{$footer}", $fixed);
        }
    }

    /**
     * Test that commas between a match arm's conditions are spaced as in section 5.2's example, in both standards.
     */
    public function testMatchConditionCommaSpacing(): void
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace A;\n\n\$a = match (\$x) {\n";
        $footer = "    default => 9,\n};\n";
        $arms = "    1,2 => 3,\n    4 , 5 => 6,\n";
        foreach (['PERCS31', 'Jumbo'] as $standard) {
            $file = $this->copyToTempFile(null, "{$header}{$arms}{$footer}");

            try {
                $result = $this->runTool('phpcs', ['-s', "--standard={$standard}", '--report=emacs', $file]);
                $fixer = $this->runTool('phpcbf', ["--standard={$standard}", $file]);
                $fixed = (string) file_get_contents($file);
            } finally {
                unlink($file);
            }

            $pattern = '/:(\\d+):\\d+: error - .*Universal\\.WhiteSpace\\.CommaSpacing\\.(\\w+)\\)/';
            preg_match_all($pattern, $result['output'], $matches, PREG_SET_ORDER);
            $found = array_map(static fn(array $match): string => "{$match[1]}:{$match[2]}", $matches);
            $this->assertSame(['8:NoSpaceAfter', '9:SpaceBefore'], $found, $result['output']);
            $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
            $this->assertSame("{$header}    1, 2 => 3,\n    4, 5 => 6,\n{$footer}", $fixed);
        }
    }

    /**
     * Test that `final` must come before a constant's visibility in both standards, and that the fix reorders them.
     */
    public function testConstantModifierOrderIsFixedInBothStandards(): void
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace A;\n\nclass B\n{\n";
        $footer = "}\n";
        $code = "    public final const int C = 1;\n";
        $expected = "    final public const int C = 1;\n";
        foreach (['Jumbo', 'PERCS31'] as $standard) {
            $file = $this->copyToTempFile(null, "{$header}{$code}{$footer}");

            try {
                $result = $this->runTool('phpcs', ['-s', "--standard={$standard}", '--report=emacs', $file]);
                $fixer = $this->runTool('phpcbf', ["--standard={$standard}", $file]);
                $fixed = (string) file_get_contents($file);
            } finally {
                unlink($file);
            }

            $errorCode = 'Universal.Constants.ModifierKeywordOrder.Incorrect';
            $this->assertStringContainsString($errorCode, $result['output']);
            $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
            $this->assertSame("{$header}{$expected}{$footer}", $fixed);
        }
    }

    /**
     * Test that a default value's `=` can be aligned in PERCS31, which only requires a space, and not in Jumbo.
     */
    public function testDefaultParameterSpacing(): void
    {
        $fixture = dirname(__DIR__, 2) . '/Jumbo/Tests/Ruleset/DefaultParameterSpacing.inc';
        $expected = [
            'PERCS31' => ['7:NoSpaceBefore', '7:NoSpaceAfter'],
            'Jumbo' => [
                '7:SpaceBeforeEquals',
                '7:SpaceAfterEquals',
                '13:SpaceBeforeEquals',
                '14:SpaceAfterEquals',
                '19:SpaceBeforeEquals',
            ],
        ];
        foreach ($expected as $standard => $codes) {
            $result = $this->runTool('phpcs', ['-s', "--standard={$standard}", '--report=emacs', $fixture]);

            $pattern = '/:(\\d+):\\d+: error - .*\\.(NoSpace(?:Before|After)|Space(?:Before|After)Equals)\\)/';
            preg_match_all($pattern, $result['output'], $matches, PREG_SET_ORDER);
            $found = array_map(static fn(array $match): string => "{$match[1]}:{$match[2]}", $matches);
            $this->assertSame($codes, $found, $result['output']);
        }
    }

    /**
     * Test that a hook's own opening brace is moved to one space after its name, in both standards.
     */
    public function testHookBraceSpacingIsFixed(): void
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace A;\n\nfinal class B\n{\n    public int \$c {\n";
        $body = "            return 1;\n        }\n    }\n}\n";
        foreach (['Jumbo' => "        get {\n", 'PERCS31' => "        get {\n"] as $standard => $expected) {
            $file = $this->copyToTempFile(null, "{$header}        get\n        {\n{$body}");

            try {
                $fixer = $this->runTool('phpcbf', ["--standard={$standard}", $file]);
                $fixed = (string) file_get_contents($file);
            } finally {
                unlink($file);
            }

            $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
            $this->assertSame("{$header}{$expected}{$body}", $fixed);
        }
    }

    /**
     * Test that a class member on a file's last line doesn't crash the run, so another file's report isn't lost.
     */
    public function testMemberOnLastLineDoesNotCrashTheRun(): void
    {
        $members = [
            'class A { public int $a = 1;',
            "class A { public int \$a {\n    get => 1;\n}",
            'class A { const B = 1;',
            'enum A { case B;',
        ];
        foreach ($members as $code) {
            $file = $this->copyToTempFile(null, "<?php\n\n{$code} }\n");

            try {
                $result = $this->runTool('phpcs', ['--standard=Jumbo', '--report=emacs', $file]);
                $fixer = $this->runTool('phpcbf', ['--standard=Jumbo', $file]);
            } finally {
                unlink($file);
            }

            $this->assertNotSame(255, $result['exitCode'], $result['output']);
            $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
            $this->assertNotSame(255, $fixer['exitCode'], $fixer['output']);
        }
    }

    /**
     * Test that both standards allow a blank line between two parameters or two `use` variables, as section 2.3 does,
     * and report one after an attribute or after an opening parenthesis. One before a closing parenthesis is reported
     * in Jumbo only, as PER doesn't forbid it. The parent's EmptyLine, which reports them all, is excluded in the
     * ruleset.
     */
    public function testBlankLinesInDeclaration(): void
    {
        $fixture = dirname(__DIR__, 2) . '/Jumbo/Tests/Ruleset/BlankLinesInDeclaration.inc';
        $sniff = '--sniffs=PERCS31.Functions.MultiLineFunctionDeclaration';
        foreach (['PERCS31', 'Jumbo'] as $standard) {
            $result = $this->runTool('phpcs', ['-s', "--standard={$standard}", $sniff, '--report=emacs', $fixture]);

            preg_match_all('/:(\\d+):\\d+: error - .*\\.BlankLine\\)/', $result['output'], $matches);
            preg_match_all('/:(\\d+):\\d+: error - .*\\.BlankLineBeforeCloseBracket\\)/', $result['output'], $atEnd);
            $this->assertSame(['20', '47'], $matches[1], $result['output']);
            $this->assertSame($standard === 'Jumbo' ? ['41'] : [], $atEnd[1], $result['output']);
            $this->assertStringNotContainsString('EmptyLine', $result['output']);
        }
    }

    /**
     * Test that both standards allow a blank line between two of a call's arguments, as section 2.3 does, and report
     * one after its opening parenthesis, as BlankLine. One before its closing parenthesis is reported in Jumbo only, as
     * PER doesn't forbid it. The parent's EmptyLine, which reports them all, is excluded in the ruleset.
     */
    public function testBlankLinesInCall(): void
    {
        $fixture = dirname(__DIR__, 2) . '/Jumbo/Tests/Ruleset/BlankLinesInCall.inc';
        $sniff = '--sniffs=PERCS31.Functions.FunctionCallLayout';
        foreach (['PERCS31', 'Jumbo'] as $standard) {
            $result = $this->runTool('phpcs', ['-s', "--standard={$standard}", $sniff, '--report=emacs', $fixture]);

            preg_match_all('/:(\\d+):\\d+: error - .*\\.BlankLine\\)/', $result['output'], $matches);
            preg_match_all('/:(\\d+):\\d+: error - .*\\.BlankLineBeforeCloseBracket\\)/', $result['output'], $atEnd);
            $this->assertSame(['25'], $matches[1], $result['output']);
            $this->assertSame($standard === 'Jumbo' ? ['15'] : [], $atEnd[1], $result['output']);
            $this->assertStringNotContainsString('EmptyLine', $result['output']);
        }
    }

    /**
     * Test that Jumbo hides an UPPER_CASE enum constant's warning by default, and shows it at severity 4.
     */
    public function testEnumConstantCaseWarningNeedsWarningSeverity4InJumbo(): void
    {
        $fixture = dirname(__DIR__, 2) . '/PERCS31/Tests/NamingConventions/UpperCaseConstantNameUnitTest.inc';
        $args = ['-s', '--standard=Jumbo', '--sniffs=PERCS31.NamingConventions.UpperCaseConstantName', $fixture];
        $default = $this->runTool('phpcs', ['--report=emacs', ...$args]);
        $severity4 = $this->runTool('phpcs', ['--report=emacs', '--warning-severity=4', ...$args]);

        $this->assertStringNotContainsString('EnumConstantNotPascalCase', $default['output']);
        $this->assertMatchesRegularExpression(
            '/:21:\\d+: warning - .*EnumConstantNotPascalCase/',
            $severity4['output'],
        );
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
     * Test the naming rules, in both standards: an acronym is a warning unless the name is excluded, and a name with
     * no lower-case letter isn't in PascalCase.
     */
    #[DataProvider('standardProvider')]
    public function testNamingRules(string $standard): void
    {
        $source = <<<'PHP'
            <?php

            // phpcs:set PERCS31.NamingConventions.AcronymCasing excludedNames HTTPClient,getHTTP
            class HTTPClient {}

            enum foo {}

            enum HTTPStatus {}

            enum XMLStatus
            {
                case HTTP;
                case Hearts;
                const HTTPOk = 1;
                const OK = 2;
            }

            use Foo\Bar as XMLBar;

            function getHTTP(): void {}

            function getXML(): void {}

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $args = ['-s', "--standard={$standard}", '--warning-severity=4', '--report=emacs', $file];
            $result = $this->runTool('phpcs', $args);
        } finally {
            unlink($file);
        }

        $pattern = '/:(\d+):\d+: (\w+) - .*\.(NotPascalCase|AcronymNotLikeWord|EnumConstant\w+)\)$/m';
        preg_match_all($pattern, $result['output'], $matches, PREG_SET_ORDER);
        $reports = array_map(static fn(array $match): string => "{$match[1]} {$match[2]} {$match[3]}", $matches);
        $expected = [
            '6 error NotPascalCase',
            '8 warning AcronymNotLikeWord',
            '10 warning AcronymNotLikeWord',
            '12 warning AcronymNotLikeWord',
            '12 error NotPascalCase',
            '14 warning AcronymNotLikeWord',
            '15 warning EnumConstantNotPascalCase',
            '18 warning AcronymNotLikeWord',
            '22 warning AcronymNotLikeWord',
        ];

        $this->assertSame($expected, $reports, $result['output']);
    }

    /**
     * Test that global constants must be in UPPER_CASE in Jumbo only, and that an acronym is only reported in a name
     * with a lower-case letter.
     */
    public function testGlobalConstantCaseIsCheckedInJumboOnly(): void
    {
        $source = "<?php\n\nconst userId = 1;\n\ndefine('userId', 2);\n\nconst HTTP_OK = 3;\n\nconst userID = "
            . "4;\n\nuse const Foo\\BAR as userId;\n";
        $file = $this->copyToTempFile(null, $source);
        $reports = [];

        try {
            foreach (['PERCS31', 'Jumbo'] as $standard) {
                $args = ['-s', "--standard={$standard}", '--report=emacs', $file];
                $output = $this->runTool('phpcs', $args)['output'];
                $pattern = '/:(\\d+):\\d+: \\w+ - .*\\.(ConstantNotUpperCase|AcronymNotLikeWord)\\)$/m';
                preg_match_all($pattern, $output, $matches, PREG_SET_ORDER);
                $reports[$standard] = array_map(static fn(array $match): string => "{$match[1]} {$match[2]}", $matches);
            }
        } finally {
            unlink($file);
        }

        $jumbo = [
            '3 ConstantNotUpperCase',
            '5 ConstantNotUpperCase',
            '9 ConstantNotUpperCase',
            '9 AcronymNotLikeWord',
            '11 ConstantNotUpperCase',
        ];
        $this->assertSame(['9 AcronymNotLikeWord'], $reports['PERCS31']);
        $this->assertEqualsCanonicalizing($jumbo, $reports['Jumbo']);
    }

    /**
     * Test that a constant with a double underscore passes in PERCS31, and is an error in Jumbo, which requires strict
     * UPPER_SNAKE_CASE of a class constant, a global constant and a constant from define().
     */
    public function testStrictUpperSnakeCaseIsRequiredInJumboOnly(): void
    {
        $source = "<?php\n\nconst TWO__WORDS = 1;\n\ndefine('_LEADING', 2);\n\nclass Holder\n{\n    public const "
            . "__HIDDEN = 3;\n    public const VALID_NAME = 4;\n}\n";
        $file = $this->copyToTempFile(null, $source);
        $sniff = '--sniffs=PERCS31.NamingConventions.UpperCaseConstantName';
        $reports = [];

        try {
            foreach (['PERCS31', 'Jumbo'] as $standard) {
                $args = ["--standard={$standard}", $sniff, '-s', '--report=emacs', $file];
                $output = $this->runTool('phpcs', $args)['output'];
                preg_match_all('/:(\\d+):\\d+: error - .*ConstantNotUpperSnakeCase\\)$/m', $output, $matches);
                $reports[$standard] = $matches[1];
            }
        } finally {
            unlink($file);
        }

        $this->assertSame([], $reports['PERCS31']);
        $this->assertSame(['3', '5', '9'], $reports['Jumbo']);
    }

    /**
     * Test that a leading underscore on a global constant passes in PERCS31, as PER's rule is about visibility, and is
     * an error in Jumbo.
     */
    public function testGlobalConstantUnderscoreIsCheckedInJumboOnly(): void
    {
        $file = $this->copyToTempFile(null, "<?php\n\nconst _HIDDEN = 1;\n");
        $sniff = '--sniffs=PERCS31.NamingConventions.ValidConstantName';

        try {
            $per = $this->runTool('phpcs', ['--standard=PERCS31', $sniff, '-s', '--report=emacs', $file])['output'];
            $jumbo = $this->runTool('phpcs', ['--standard=Jumbo', $sniff, '-s', '--report=emacs', $file])['output'];
        } finally {
            unlink($file);
        }

        $this->assertStringNotContainsString('ValidConstantName', $per);
        $this->assertStringContainsString('PERCS31.NamingConventions.ValidConstantName.Underscore', $jumbo);
    }

    /**
     * Test that two or more attribute blocks go on separate lines in both standards, with the target on the next
     * line, except in a single-line list, where they stay inline, and that phpcbf gives the same result in each.
     */
    #[DataProvider('standardProvider')]
    public function testSeparateAttributeBlocksAreSplit(string $standard): void
    {
        $source = <<<'PHP'
            <?php

            declare(strict_types=1);

            enum E
            {
                #[A]  #[B] case X;

                #[A, B] case Z;
            }

            function f(#[A] #[B] int $c): void {}

            usort($items, #[A] #[B] static fn($x) => $x);

            $pure = #[A] #[B] static fn($x) => $x;

            PHP;
        $expected = <<<'PHP'
            <?php

            declare(strict_types=1);

            enum E
            {
                #[A]
                #[B]
                case X;

                #[A, B]
                case Z;
            }

            function f(#[A] #[B] int $c): void {}

            usort($items, #[A] #[B] static fn($x) => $x);

            $pure = #[A]
                #[B]
                static fn($x) => $x;

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $this->runTool('phpcbf', ["--standard={$standard}", $file]);
            $this->assertSame($expected, file_get_contents($file));
        } finally {
            unlink($file);
        }
    }

    /**
     * Test that a leading underscore on a property is an error in both standards, as LeadingUnderscore, not the
     * warning from PSR-2's sniff, which PERCS31.Classes.PropertyDeclaration extends, and that its excluded order check
     * isn't reported.
     */
    #[DataProvider('standardProvider')]
    public function testPropertyUnderscoreIsAnError(string $standard): void
    {
        $source = "<?php\n\nnamespace App;\n\nclass A\n{\n    public string \$_a = 'x';\n\n    static public int \$b = "
            . "1;\n}\n";
        $file = $this->copyToTempFile(null, $source);

        try {
            $args = ['-s', "--standard={$standard}", '--report=emacs', $file];
            $output = $this->runTool('phpcs', $args)['output'];
        } finally {
            unlink($file);
        }

        $this->assertMatchesRegularExpression('/:7:\\d+: error - .*PropertyDeclaration\\.LeadingUnderscore/', $output);
        $this->assertStringNotContainsString('(PERCS31.Classes.PropertyDeclaration.Underscore)', $output);
        $this->assertStringNotContainsString('PERCS31.Classes.PropertyDeclaration.StaticBeforeVisibility', $output);
    }

    /**
     * The two standards.
     *
     * @return array<string, array{string}>
     */
    public static function standardProvider(): array
    {
        return ['PERCS31' => ['PERCS31'], 'Jumbo' => ['Jumbo']];
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
            $twoSpaces = $this->runTool('phpcs', ["--standard={$ruleset}", $fixture]);
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

    /**
     * Test that Jumbo reports an unused parameter, but not in a class that extends a class or implements an interface,
     * where a parent or the interface may fix the signature.
     */
    public function testUnusedParameterIsSkippedInExtendingClass(): void
    {
        $plain = $this->copyToTempFile(null, <<<'PHP'
            <?php

            final class Plain
            {
                public function handle(int $used, int $unused): int
                {
                    return $used;
                }
            }
            PHP);
        $extending = $this->copyToTempFile(null, <<<'PHP'
            <?php

            final class Extending extends Base
            {
                public function handle(int $used, int $unused): int
                {
                    return $used;
                }
            }
            PHP);
        $args = ['-s', '--standard=Jumbo', '--sniffs=Generic.CodeAnalysis.UnusedFunctionParameter', '--report=emacs'];

        try {
            $plainResult = $this->runTool('phpcs', [...$args, $plain]);
            $extendingResult = $this->runTool('phpcs', [...$args, $extending]);
        } finally {
            unlink($plain);
            unlink($extending);
        }

        $this->assertStringContainsString('UnusedFunctionParameter.FoundAfterLastUsed', $plainResult['output']);
        $this->assertSame(0, $extendingResult['exitCode'], $extendingResult['output']);
    }

    /**
     * Test that Jumbo requires self, not static, in a final class, except as a return type. On PHP 8.4, self can't
     * replace a static return type that a parent declares.
     */
    public function testStaticReturnTypeInFinalClassIsAllowed(): void
    {
        $file = $this->copyToTempFile(null, <<<'PHP'
            <?php

            abstract class Base
            {
                abstract public function make(): static;
            }

            final class Child extends Base
            {
                public function make(): static
                {
                    return new static();
                }
            }
            PHP);
        $sniffs = '--sniffs=Universal.CodeAnalysis.StaticInFinalClass';

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=Jumbo', $sniffs, '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertStringContainsString('StaticInFinalClass.NewInstance', $result['output']);
        $this->assertStringNotContainsString('StaticInFinalClass.ReturnType', $result['output']);
    }

    /**
     * Test that Jumbo reports a space inside grouping parentheses on one line, but not a line break inside them, which
     * Jumbo.Formatting.ExpressionFormat and PERCS31.Operators.OperatorPlacement check.
     */
    public function testLineBreakInGroupingParenthesesIsAllowed(): void
    {
        $file = $this->copyToTempFile(null, <<<'PHP'
            <?php

            $total = ( $a + $b ) * 2;
            $split = (
                $a
                + $b
            ) * 2;
            PHP);
        $sniffs = '--sniffs=Generic.WhiteSpace.ArbitraryParenthesesSpacing';

        try {
            $result = $this->runTool('phpcs', ['--standard=Jumbo', $sniffs, '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertSame(2, substr_count($result['output'], "{$file}:3:"), $result['output']);
        $this->assertStringNotContainsString("{$file}:4:", $result['output']);
        $this->assertStringNotContainsString("{$file}:7:", $result['output']);
    }

    /**
     * Test that Jumbo reports a run of spaces in code, but not aligned text in a comment or a doc comment.
     */
    public function testDuplicateSpacesInCommentsAreAllowed(): void
    {
        $file = $this->copyToTempFile(null, <<<'PHP'
            <?php

            // Aligned  text  in  a  comment.
            /**
             * @param int    $a  The value.
             */
            function double(int $a): int
            {
                return  $a * 2;
            }
            PHP);
        $sniffs = '--sniffs=SlevomatCodingStandard.Whitespaces.DuplicateSpaces';

        try {
            $result = $this->runTool('phpcs', ['--standard=Jumbo', $sniffs, '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertSame(1, substr_count($result['output'], 'Duplicate spaces'), $result['output']);
        $this->assertStringContainsString("{$file}:9:", $result['output']);
    }

    /**
     * Test that Jumbo allows a class of up to 1000 lines of code, where SlevomatCodingStandard.Classes.ClassLength's
     * default is 250.
     */
    public function testClassLengthLimitIs1000Lines(): void
    {
        $class = static fn(int $lines): string => "<?php\n\nfinal class Long\n{\n"
            . implode('', array_map(static fn(int $i): string => "    public int \$p{$i} = 0;\n", range(1, $lines)))
            . "}\n";
        $under = $this->copyToTempFile(null, $class(990));
        $over = $this->copyToTempFile(null, $class(1001));
        $args = ['-s', '--standard=Jumbo', '--sniffs=SlevomatCodingStandard.Classes.ClassLength', '--report=emacs'];

        try {
            $underResult = $this->runTool('phpcs', [...$args, $under]);
            $overResult = $this->runTool('phpcs', [...$args, $over]);
        } finally {
            unlink($under);
            unlink($over);
        }

        $this->assertSame(0, $underResult['exitCode'], $underResult['output']);
        $this->assertStringContainsString('ClassLength.ClassTooLong', $overResult['output']);
    }

    #endregion

    #region PERCS31 rules in Jumbo tests.

    /**
     * Test that a control structure with no body needs braces in Jumbo too, an `else` included, and that the `while`
     * of a `do` loop isn't taken for one.
     */
    public function testStructureWithNoBodyNeedsBraces(): void
    {
        $source = "<?php\n\ndeclare(strict_types=1);\n\nwhile (next(\$items));\n\ndo {\n    run();\n} while "
            . "(\$a);\n\nif (\$a) {\n    run();\n} else;\n";
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=Jumbo', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=Jumbo', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertSame(2, substr_count($result['output'], 'ControlStructureEmptyStatement'), $result['output']);
        $this->assertStringContainsString("} else {}\n", $fixed);
        $this->assertMatchesRegularExpression('/while \(next\(\$items\)\) \{\s*\}\n\ndo \{/', $fixed);
        $this->assertStringContainsString("} while (\$a);\n", $fixed);
    }

    /**
     * Test that Jumbo's comment rules report a comment's indent and spacing, which PERCS31 doesn't check.
     */
    public function testCommentsAreCheckedInJumbo(): void
    {
        $source = "<?php\n\ndeclare(strict_types=1);\n\nfunction foo(): int\n{\n// At column 1.\n    /*\n  Too "
            . "little.\n    */\n    \$a = 1;// No space.\n    //No space.\n\n    return \$a;\n}\n";
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=Jumbo', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $expected = [
            ':7:' => 'PERCS31.WhiteSpace.ScopeIndent',
            ':9:' => 'Jumbo.Commenting.BlockComment.ContentIndent',
            ':11:' => 'Jumbo.Commenting.TrailingComment.SpacingBefore',
            ':12:' => 'Squiz.Commenting.InlineComment.NoSpaceBefore',
        ];
        foreach ($expected as $line => $code) {
            $pattern = "/{$line}\\d+: error - .*" . preg_quote($code, '/') . '/';
            $this->assertMatchesRegularExpression($pattern, $result['output']);
        }
    }

    /**
     * Test that a space or line break inside an empty array's brackets is reported once in Jumbo too.
     */
    public function testSpaceInEmptyArrayIsReportedOnce(): void
    {
        $source = "<?php\n\ndeclare(strict_types=1);\n\necho count([ ]), count([   ]), count(foo([ ]));\necho "
            . "count([\n]);\n";
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=Jumbo', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertSame(4, substr_count($result['output'], 'SpaceInEmptyArray'), $result['output']);
    }

    /**
     * Test that a line over 120 characters is a warning, not an error, in Jumbo too, and that the warning over 80
     * characters is hidden by default, and shown at severity 4.
     */
    public function testLineLengthLimitsAreWarnings(): void
    {
        $fixture = __DIR__ . '/../../PERCS31/Tests/Ruleset/LineLength.inc';
        $args = ['-s', '--standard=Jumbo', '--sniffs=Generic.Files.LineLength', '--report=emacs', $fixture];
        $default = $this->runTool('phpcs', $args);
        $severity4 = $this->runTool('phpcs', ['--warning-severity=4', ...$args]);

        $this->assertMatchesRegularExpression('/:16:\d+: warning - .*LineLength\.MaxExceeded/', $default['output']);
        $this->assertStringNotContainsString(' error - ', $default['output']);
        $this->assertStringNotContainsString('LineLength.TooLong', $default['output']);
        foreach ([9, 10, 11] as $line) {
            $pattern = "/:{$line}:\\d+: warning - .*LineLength\\.TooLong/";
            $this->assertMatchesRegularExpression($pattern, $severity4['output']);
        }

        $this->assertStringNotContainsString(':12:', $severity4['output'], 'An unbreakable comment is exempt.');
    }

    #endregion
}
