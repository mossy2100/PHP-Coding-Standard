<?php

declare(strict_types=1);

namespace PERCS31\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Tests of the full PERCS31 ruleset: what it reports, allows and passes, and sniff properties that can only be
 * set in a ruleset.
 *
 * The per-sniff tests run each sniff alone. These run phpcs and phpcbf as a user would, so they catch conflicts
 * between sniffs. The fixer tests are in RulesetFixerUnitTest, and the tests that a problem two sniffs see is
 * reported once are in RulesetReportedOnceUnitTest. Tests comparing PERCS31 with Jumbo are in Jumbo's
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

    #region Rule tests.

    /**
     * Test that a class constant with no visibility is an error in PERCS31, as PER says MUST, not PSR-12's warning.
     */
    public function testConstantVisibilityIsAnError(): void
    {
        $source = <<<'PHP'
            <?php

            class Holder
            {
                const LIMIT = 1;
            }

            PHP;
        $file = $this->copyToTempFile(null, $source);

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
     * Test that a multi-catch's types split before the `|` pass, as a split union type does. The `|` isn't a binary
     * operator to OperatorPlacement, and the line break before it isn't a space.
     */
    public function testSplitMultiCatchPasses(): void
    {
        $source = <<<'PHP'
            <?php

            try {
                run();
            } catch (
                FirstException
                |SecondException $e
            ) {
                report($e);
            }

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertSame('', trim($result['output']));
    }

    /**
     * Test that several traits in one use statement are reported when the statement has a block, which
     * PSR12.Traits.UseDeclaration misses.
     */
    public function testSeveralTraitsInUseWithBlockAreReported(): void
    {
        $source = <<<'PHP'
            <?php

            class Example
            {
                use A, B {
                    A::a insteadof B;
                }
            }

            PHP;
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
     * Test that a blank line between two interfaces is allowed, as section 2.3 doesn't forbid one, and that two
     * interfaces on a line are still reported and split. The parent's InterfaceSameLine, which reports both, is
     * excluded in the ruleset.
     */
    public function testBlankLineBetweenInterfacesIsAllowed(): void
    {
        $source = <<<'PHP'
            <?php

            namespace Vendor\Package;

            class Foo implements
                Countable,

                Stringable, Traversable
            {
                // ...
            }

            PHP;
        $expected = <<<'PHP'
            <?php

            namespace Vendor\Package;

            class Foo implements
                Countable,

                Stringable,
                Traversable
            {
                // ...
            }

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $reported = substr_count($result['output'], 'ClassDeclaration.InterfaceNotOnOwnLine');

        $this->assertSame(1, $reported, $result['output']);
        $this->assertStringNotContainsString('InterfaceSameLine', $result['output']);
        $this->assertSame($expected, $fixed);
    }

    /**
     * Test that a comma inside parentheses in a constant's value isn't read as a second constant, and a real second
     * constant is fixed with its modifiers and type kept. These need Slevomat 8.31.3. A global const with two constants
     * passes, as section 4.3 is about classes.
     */
    public function testConstantsPerStatement(): void
    {
        $source = <<<'PHP'
            <?php

            const I = new Complex(0, 1);
            const J = 1, K = 2;

            class Holder
            {
                final public const int A = 1, B = 2;
            }

            PHP;
        $file = $this->copyToTempFile(null, $source);
        $args = ['-s', '--standard=PERCS31', '--sniffs=PERCS31.Classes.ConstantDeclaration'];

        try {
            $result = $this->runTool('phpcs', [...$args, '--report=emacs', $file]);
            $this->runTool('phpcbf', [...$args, $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertSame(1, substr_count($result['output'], 'DisallowedMultiConstantDefinition'), $result['output']);
        $pattern = '/:8:\\d+: error - .*DisallowedMultiConstantDefinition/';
        $this->assertMatchesRegularExpression($pattern, $result['output']);
        $this->assertStringContainsString("final public const int A = 1;\n    final public const int B = 2;", $fixed);
    }

    /**
     * Test that a template whose only PHP tag shares the first line with a statement passes, and isn't changed, as
     * section 3 lets the tag share its line in a file with markup. A PHP file with no markup is still reported.
     */
    public function testTemplateFirstLineTagMayShareItsLine(): void
    {
        $template = <<<'PHP'
            <?php require 'header.php'; ?>
            <main>
                <p>Hello</p>
            </main>

            PHP;
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
        $this->assertStringContainsString('PSR12.Files.OpenTag.NotAlone', $phpOnlyResult['output']);
        $this->assertSame(1, substr_count($phpOnlyResult['output'], ' error - '), $phpOnlyResult['output']);
    }

    /**
     * Test the blank lines around trait use statements. Section 4.2 only requires a blank line after the last one when
     * more follows, so one between two use statements, and several after the last, pass. None after the last is
     * reported, and fixed.
     */
    public function testBlankLinesAroundTraitUse(): void
    {
        $source = <<<'PHP'
            <?php

            namespace Vendor\Package;

            class Between
            {
                use First;

                use Second;


                public int $a = 1;
            }

            PHP;
        $lastSource = <<<'PHP'
            <?php

            namespace Vendor\Package;

            class Last
            {
                use First;
                public int $a = 1;
            }

            PHP;
        $expected = <<<'PHP'
            <?php

            namespace Vendor\Package;

            class Last
            {
                use First;

                public int $a = 1;
            }

            PHP;
        $file = $this->copyToTempFile(null, $source);
        $last = $this->copyToTempFile(null, $lastSource);
        $args = ['-s', '--standard=PERCS31', '--report=emacs'];

        try {
            $result = $this->runTool('phpcs', [...$args, $file]);
            $lastResult = $this->runTool('phpcs', [...$args, $last]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $last]);
            $fixed = (string) file_get_contents($last);
        } finally {
            unlink($file);
            unlink($last);
        }

        $this->assertSame(0, $result['exitCode'], $result['output']);
        $this->assertSame(1, substr_count($lastResult['output'], 'error - '), $lastResult['output']);
        $this->assertStringContainsString('TraitUseBlock.NoBlankLineAfterUse', $lastResult['output']);
        $this->assertSame($expected, $fixed);
    }

    /**
     * Test that a comment at the end of the last trait use's line, with the class's closing brace next, passes, and
     * that phpcbf leaves it alone. PSR12.Traits.UseDeclaration asked for a blank line before the brace, and
     * PERCS31.Classes.ClassDeclaration removed it, until phpcbf failed.
     */
    public function testCommentAfterLastTraitUsePasses(): void
    {
        $source = <<<'PHP'
            <?php

            namespace Vendor\Package;

            class Single
            {
                use First; // The first.
            }

            class Pair
            {
                use First;
                use Second; /* The second. */
            }

            class Block
            {
                use First {
                    First::a as b;
                } // The block.
            }

            PHP;
        $file = $this->copyToTempFile(null, $source);
        $args = ['--standard=PERCS31', '--exclude=PSR1.Classes.ClassDeclaration'];

        try {
            $result = $this->runTool('phpcs', [...$args, '-s', '--report=emacs', $file]);
            $fixer = $this->runTool('phpcbf', [...$args, $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertSame(0, $result['exitCode'], $result['output']);
        $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
        $this->assertSame($source, $fixed);
    }

    /**
     * Test that a control structure's empty body passes as `{}`, and with a space between the braces, as PERCS31
     * doesn't check an empty one. A body on the brace's line is reported.
     */
    public function testEmptyControlStructureBodyMayBeAbbreviated(): void
    {
        $valid = <<<'PHP'
            <?php

            try {
                run();
            } catch (Exception $e) {} finally {}

            while (next($items)) {}

            PHP;
        $file = $this->copyToTempFile(null, $valid);
        $invalidSource = <<<'PHP'
            <?php

            while (next($items)) { }

            if ($first) { run();
            }

            PHP;
        $invalid = $this->copyToTempFile(null, $invalidSource);
        $args = ['-s', '--standard=PERCS31', '--report=emacs'];

        try {
            $result = $this->runTool('phpcs', [...$args, $file]);
            $invalidResult = $this->runTool('phpcs', [...$args, $invalid]);
        } finally {
            unlink($file);
            unlink($invalid);
        }

        $this->assertSame(0, $result['exitCode'], $result['output']);
        $this->assertSame(1, substr_count($invalidResult['output'], 'BodyOnNextLine.NewlineAfterOpenBrace'));
        $this->assertSame(0, substr_count($invalidResult['output'], 'EmptyBody.NotAbbreviated'));
    }

    /**
     * Test that a trailing comma after a single argument split over lines is removed, as section 4.7 says that doesn't
     * split the argument list, and that a split list keeps its comma.
     */
    public function testNoTrailingCommaAfterSingleArgumentSplitOverLines(): void
    {
        $source = <<<'PHP'
            <?php

            $a = sum([
                1,
                2,
            ],);
            $b = sum(
                [1, 2],
                $precision,
            );

            PHP;
        $expected = <<<'PHP'
            <?php

            $a = sum([
                1,
                2,
            ]);
            $b = sum(
                [1, 2],
                $precision,
            );

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertSame(1, substr_count($result['output'], 'DisallowTrailingCommaInCall.DisallowedTrailingComma'));
        $this->assertSame(1, substr_count($result['output'], 'error - '), $result['output']);
        $this->assertSame($expected, $fixed);
    }

    /**
     * Test that a control structure with no body needs braces, an `else` included, and that the `while` of a `do` loop
     * isn't taken for one.
     */
    public function testStructureWithNoBodyNeedsBraces(): void
    {
        $source = <<<'PHP'
            <?php

            declare(strict_types=1);

            while (next($items));

            do {
                run();
            } while ($a);

            if ($a) {
                run();
            } else;

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
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
     * Test that PERCS31 doesn't check a comment's indent or spacing.
     */
    public function testCommentsAreNotChecked(): void
    {
        $source = <<<'PHP'
            <?php

            declare(strict_types=1);

            function foo(): int
            {
            // At column 1.
                /*
              Too little.
                */
                $a = 1;// No space.
                //No space.

                return $a;
            }

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertStringNotContainsString(' error - ', $result['output']);
    }

    /**
     * Test that an attribute block never has a trailing comma, as sections 12.1 and 12.3 mean its list is never split.
     * One after a single attribute whose arguments span lines is reported and removed, and one without passes. A block
     * of several attributes split over lines isn't asked for a comma, as 12.3 already rejects it.
     */
    public function testAttributeTrailingComma(): void
    {
        $source = <<<'PHP'
            <?php

            #[Foo, Bar,]
            function a(): void {}

            #[Foo(
                1,
            ),]
            function b(): void {}

            #[Foo(
                1,
            )]
            function c(): void {}

            #[Foo(
                1,
            ), Bar]
            function d(): void {}

            PHP;
        $expected = <<<'PHP'
            <?php

            #[Foo, Bar]
            function a(): void {}

            #[Foo(
                1,
            )]
            function b(): void {}

            #[Foo(
                1,
            )]
            function c(): void {}

            #[Foo(
                1,
            ), Bar]
            function d(): void {}

            PHP;
        $file = $this->copyToTempFile(null, $source);
        $args = ['-s', '--standard=PERCS31', '--sniffs=Universal.Attributes.TrailingComma'];

        try {
            $result = $this->runTool('phpcs', [...$args, '--report=emacs', $file]);
            $this->runTool('phpcbf', [...$args, $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        preg_match_all('/:(\d+):\d+: error - .*\.(\w+)\)$/m', $result['output'], $matches, PREG_SET_ORDER);
        $reports = array_map(static fn(array $match): string => "{$match[1]} {$match[2]}", $matches);
        $reported = ['3 ForbiddenSingleLine', '8 ForbiddenSingleAttributeMultiLine'];

        $this->assertSame($reported, $reports, $result['output']);
        $this->assertSame($expected, $fixed);
    }

    /**
     * Test that section 12.4's whole example has no blank line or attribute errors, with a blank line before `int $d`.
     */
    public function testSpecAttributesExamplePasses(): void
    {
        $fixture = self::FIXTURES . '/AttributesExample.inc';
        $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $fixture]);

        $this->assertStringNotContainsString('MultiLineFunctionDeclaration', $result['output']);
        $this->assertStringNotContainsString('AttributeLayout', $result['output']);
    }

    /**
     * Test that a line over 120 characters is a warning, not an error, and that the warning over 80 characters is
     * hidden by default, and shown at severity 4.
     */
    public function testLineLengthLimitsAreWarnings(): void
    {
        $fixture = __DIR__ . '/Ruleset/LineLength.inc';
        $args = ['-s', '--standard=PERCS31', '--sniffs=Generic.Files.LineLength', '--report=emacs', $fixture];
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
