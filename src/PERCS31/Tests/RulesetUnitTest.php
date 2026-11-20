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
     * Test that phpcbf puts two short hooks on one line on lines of their own. DisallowMultipleStatements only moves
     * the second, so PropertyHooks has to move the first.
     */
    public function testPhpcbfSplitsTwoShortHooksOnOneLine(): void
    {
        $source = <<<'PHP'
            <?php

            class Example
            {
                public string $name { get => 'a'; set => trim($value); }
            }

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $this->runTool('phpcbf', ['--standard=PERCS31', '--exclude=PSR1.Classes.ClassDeclaration', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $expected = <<<'PHP'
            <?php

            class Example
            {
                public string $name {
                    get => 'a';
                    set => trim($value);
                }
            }

            PHP;
        $this->assertSame($expected, $fixed);
    }

    /**
     * Test that phpcbf doesn't fail on section 4.9's "not allowed" promoted hook, which only a person can fix, and that
     * it's still reported.
     */
    public function testPhpcbfDoesNotLoopOnTooComplexPromotedHook(): void
    {
        $source = <<<'PHP'
            <?php

            class Example
            {
                public function __construct(
                    public string $name {
                        set {
                            $this->name = ucfirst($value);
                        }
                    },
                ) {}
            }

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $fixer = $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
        $this->assertStringContainsString('PropertyHooks.TooComplex', $result['output']);
        $this->assertStringNotContainsString('MultiLineFunctionDeclaration.Indent', $result['output']);
    }

    /**
     * Test that phpcbf leaves two hooks on a promoted parameter alone, as the error that remains means moving the
     * property to the class body. The fix for two statements on a line split them first.
     */
    public function testPhpcbfLeavesTwoHooksOnPromotedParameter(): void
    {
        $source = <<<'PHP'
            <?php

            class Example
            {
                public function __construct(
                    public string $name { get => 'a'; set => ucfirst($value); },
                ) {}
            }

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertSame($source, $fixed);
        $this->assertStringContainsString('PropertyHooks.TooComplex', $result['output']);
    }

    /**
     * Test that a reference `&` has no space after it, wherever it's used, and that every spacing of a reference
     * assignment is fixed to `$x = &$a`. PERCS31.Operators.OperatorSpacing adds the spaces the `=` needs, and
     * PERCS31.Operators.UnaryOperatorSpacing removes the space after the `&`. A parameter's `&` is reported once, and
     * one with a comment after it isn't fixed. The other unary operators aren't checked.
     */
    public function testReferenceOperatorHasNoSpaceAfterIt(): void
    {
        $source = <<<'PHP'
            <?php

            $b = & $a;
            $c =& $a;
            $d =&$a;
            $e=&$a;
            $f=& $a;
            $g = & /* Why. */ $a;
            foreach ($a as & $item) {
                $item++;
            }
            $h = [& $b, 'c' => & $c];
            $i = function & (int & $j) use (& $b): int {
                return $b;
            };
            $k = $b & $c;
            $l = ! $b;

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $expected = <<<'PHP'
            <?php

            $b = &$a;
            $c = &$a;
            $d = &$a;
            $e = &$a;
            $f = &$a;
            $g = & /* Why. */ $a;
            foreach ($a as &$item) {
                $item++;
            }
            $h = [&$b, 'c' => &$c];
            $i = function &(int &$j) use (&$b): int {
                return $b;
            };
            $k = $b & $c;
            $l = ! $b;

            PHP;

        $reported = substr_count($result['output'], 'UnaryOperatorSpacing.WhitespaceAfter');

        $this->assertSame(10, $reported, $result['output']);
        $this->assertStringNotContainsString('SpacingAfterReference', $result['output']);
        $this->assertSame($expected, $fixed);
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
     * Test that an object operator at the end of a line is moved to the start of the next, with no blank line left
     * behind. PEAR.WhiteSpace.ObjectOperatorIndent's fix, which PERCS31 once used, left one after this sniff's.
     */
    public function testObjectOperatorAtEndOfLineIsMovedToNextLine(): void
    {
        $source = <<<'PHP'
            <?php

            $someInstance->
                create()
                ->prepare();

            $user = $repository->
                find($id);

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $expected = <<<'PHP'
            <?php

            $someInstance
                ->create()
                ->prepare();

            $user = $repository
                ->find($id);

            PHP;

        $this->assertSame(2, substr_count($result['output'], 'MethodChaining.OperatorAtEndOfLine'), $result['output']);
        $this->assertSame(2, substr_count($result['output'], ' error - '), $result['output']);
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
     * Test that a `static|null` return type is fixed to `?static`, with null in either position, and still parses.
     * Slevomat's fix for it left `?` and `?null`, so PERCS31.TypeHints.CompoundType makes the check instead.
     */
    public function testStaticOrNullReturnTypeIsFixedToShortNullable(): void
    {
        $source = <<<'PHP'
            <?php

            namespace Vendor\Package;

            class Fluent
            {
                public function next(): static|null
                {
                    return null;
                }

                public function back(): null|static
                {
                    return null;
                }
            }

            PHP;
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
        $this->assertSame(1, substr_count($result['output'], 'PERCS31.TypeHints.CompoundType.NullNotLast'));
        $this->assertStringNotContainsString('SlevomatCodingStandard.TypeHints.DNFTypeHintFormat', $result['output']);
        $this->assertSame(str_replace(['static|null', 'null|static'], '?static', $source), $fixed);
        $this->assertSame(0, $after['exitCode'], $after['output']);
    }

    /**
     * Test that a default value wrapped at an operator is indented once past its parameter, as section 6.4 says, in a
     * method, a closure and an arrow function. The inherited Indent check wanted it level with the parameter, so no
     * layout passed, and phpcbf failed. A parameter indented wrongly is still reported.
     */
    public function testWrappedDefaultValueIsIndentedOncePastItsParameter(): void
    {
        $source = <<<'PHP'
            <?php

            namespace Vendor\Package;

            class Flags
            {
                public function set(
                    string $name = 'a'
                        . 'b',
                    int $mode = self::READ
                        | self::WRITE,
                    int $size = PHP_INT_SIZE === 8
                        ? 1
                        : 2,
                ): void {
                    $closure = function (
                        string $name = 'a'
                            . 'b',
                    ) {
                        return $name;
                    };
                    $arrow = fn(
                        int $mode = 1
                            + 2,
                    ): int => $mode;
                }
            }

            PHP;
        $level = str_replace("\n            . 'b'", "\n        . 'b'", $source);
        $file = $this->copyToTempFile(null, $source);
        $levelFile = $this->copyToTempFile(null, $level);
        $wrongIndent = <<<'PHP'
            <?php

            function a(
                    int $b,
              int $c,
              ): void {}

            PHP;
        $indented = $this->copyToTempFile(null, $wrongIndent);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $fixer = $this->runTool('phpcbf', ['--standard=PERCS31', $levelFile]);
            $fixed = (string) file_get_contents($levelFile);
            $wrong = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $indented]);
        } finally {
            unlink($file);
            unlink($levelFile);
            unlink($indented);
        }

        $this->assertSame(0, $result['exitCode'], $result['output']);
        $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
        $this->assertSame($source, $fixed);
        $this->assertSame(3, substr_count($wrong['output'], 'MultiLineFunctionDeclaration.ParameterIndent'));
        $this->assertStringNotContainsString('MultiLineFunctionDeclaration.Indent', $wrong['output']);
    }

    /**
     * Test that a first-class callable split over lines is fixed to `foo(...)`, as section 4.8 requires, with no comma
     * added after the `...`, which Slevomat's trailing comma sniff did. With a comment before the `...`, only the side
     * after it is fixed, and phpcbf doesn't fail.
     */
    public function testSplitFirstClassCallableIsJoinedWithNoComma(): void
    {
        $source = <<<'PHP'
            <?php

            $a = strlen(
                ...
            );
            $b = strlen(
                // Why.
                ...
            );

            PHP;
        $expected = <<<'PHP'
            <?php

            $a = strlen(...);
            $b = strlen(
                // Why.
                ...);

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertSame(2, substr_count($result['output'], 'FirstClassCallableSpacing.SpacingBefore'));
        $this->assertSame(2, substr_count($result['output'], 'FirstClassCallableSpacing.SpacingAfter'));
        $this->assertStringNotContainsString('MissingTrailingComma', $result['output']);
        $this->assertStringNotContainsString('FunctionCallLayout', $result['output']);
        $this->assertSame($expected, $fixed);
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
     * Test that an attribute on a readonly anonymous class is fixed to section 8's layout, with `readonly class` on the
     * line after the attributes, and the body indented from it.
     */
    public function testAttributeOnReadonlyAnonClassIsFixedToSectionEightLayout(): void
    {
        $source = <<<'PHP'
            <?php

            $a = new #[A] readonly class {
                public int $b;
            };

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $expected = <<<'PHP'
            <?php

            $a = new
                #[A]
                readonly class {
                    public int $b;
                };

            PHP;
        $this->assertSame($expected, $fixed);
        $this->assertSame(0, $result['exitCode'], $result['output']);
    }

    /**
     * Test that a closure's `use` on the line after its parameter list's closing parenthesis is joined to that line.
     * The two inherited fixes for it undid each other until phpcbf failed.
     */
    public function testClosureUseOnItsOwnLineIsJoined(): void
    {
        $source = <<<'PHP'
            <?php

            $a = function (
                $b,
            )
            use ($c) {
                return 1;
            };

            PHP;
        $expected = <<<'PHP'
            <?php

            $a = function (
                $b,
            ) use ($c) {
                return 1;
            };

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $fixer = $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
        $this->assertSame($expected, $fixed);
    }

    /**
     * Test that a multi-line array may start a split condition, on the line after the opening parenthesis, and that
     * one on the parenthesis's line is moved there. The array sniff's fix and the control structure sniff's undid
     * each other until phpcbf failed.
     */
    public function testArrayStartingSplitConditionGoesOnNextLine(): void
    {
        $valid = <<<'PHP'
            <?php

            if (
                [
                    $first,
                    $second,
                ] === $expected
            ) {
                run();
            }

            while (
                [
                    $first,
                    $second,
                ] === $expected
            ) {
                run();
            }

            PHP;
        $expected = <<<'PHP'
            <?php

            if (
                [
                    $first,
                    $second,
                ] === $expected
            ) {
                run();
            }

            PHP;
        $joined = <<<'PHP'
            <?php

            if ([
                $first,
                $second,
            ] === $expected) {
                run();
            }

            PHP;
        $validFile = $this->copyToTempFile(null, $valid);
        $joinedFile = $this->copyToTempFile(null, $joined);

        try {
            $result = $this->runTool('phpcs', ['--standard=PERCS31', $validFile]);
            $fixer = $this->runTool('phpcbf', ['--standard=PERCS31', $joinedFile]);
            $fixed = (string) file_get_contents($joinedFile);
        } finally {
            unlink($validFile);
            unlink($joinedFile);
        }

        $this->assertSame(0, $result['exitCode'], $result['output']);
        $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
        $this->assertSame($expected, $fixed);
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
     * Test that a one-line block declare is fixed with no stray blank line, which two fixes for its brace would add.
     */
    public function testOneLineBlockDeclareIsFixedWithNoBlankLine(): void
    {
        $expected = <<<'PHP'
            <?php

            declare(ticks=1) {
                echo 1;
            }

            PHP;
        $file = $this->copyToTempFile(null, "<?php\n\ndeclare(ticks=1) { echo 1; }\n");

        try {
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertSame($expected, $fixed);
    }

    /**
     * Test that a one-line block declare with no space inside its braces keeps the `;` before its closing brace, which
     * the fix for the brace's alignment deleted.
     */
    public function testOneLineBlockDeclareWithNoSpacesKeepsItsSemicolon(): void
    {
        $expected = <<<'PHP'
            <?php

            declare(ticks=1) {
                echo 1;
            }

            PHP;
        $file = $this->copyToTempFile(null, "<?php\n\ndeclare(ticks=1) {echo 1;}\n");

        try {
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertSame($expected, $fixed);
    }

    /**
     * Test that a heredoc on the line after `=`, or after an `=` on the next line, is fixed to `$x = <<<'EOT'`, with
     * OperatorPlacement leaving the `=` alone, and the result passes.
     */
    public function testHeredocAfterAssignmentIsJoinedToItsVariable(): void
    {
        $source = <<<'PHP'
            <?php

            $a =
            <<<'EOT'
                Hello
                EOT;

            $b
                = <<<'EOT'
                Hello
                EOT;

            PHP;
        $expected = <<<'PHP'
            <?php

            $a = <<<'EOT'
                Hello
                EOT;

            $b = <<<'EOT'
                Hello
                EOT;

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertSame($expected, $fixed);
        $this->assertStringNotContainsString('HeredocLayout', $result['output']);
        $this->assertStringNotContainsString('OperatorPlacement', $result['output']);
    }

    /**
     * Test that a non-canonical cast is reported once, and fixed to its canonical form. A `(binary)` cast becomes
     * `(string)`, and isn't deleted, as the Slevomat sniff's fix would.
     */
    public function testNonCanonicalCastIsReportedOnce(): void
    {
        $source = <<<'PHP'
            <?php

            $a = (double) $x;
            $b = (integer) $x;
            $c = (boolean) $x;
            $d = (binary) $x;

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        preg_match_all('/:(\d+):\d+: error - .*\.(\w+)\)$/m', $result['output'], $matches, PREG_SET_ORDER);
        $reports = array_map(static fn(array $match): string => "{$match[1]} {$match[2]}", $matches);
        $expected = <<<'PHP'
            <?php

            $a = (float) $x;
            $b = (int) $x;
            $c = (bool) $x;
            $d = (string) $x;

            PHP;

        $reported = ['3 InvalidCastUsed', '4 InvalidCastUsed', '5 InvalidCastUsed', '6 InvalidCastUsed'];

        $this->assertSame($reported, $reports, $result['output']);
        $this->assertStringContainsString('Cast "(binary)" is forbidden, use "(string)" instead.', $result['output']);
        $this->assertSame($expected, $fixed);
    }

    /**
     * Test that an upper-case keyword is reported once, and fixed. Two Squiz sniffs that PSR-12 includes reported a
     * control structure's keyword, and a function's keyword and `static`, a second time.
     */
    public function testUpperCaseKeywordIsReportedOnce(): void
    {
        $source = <<<'PHP'
            <?php

            IF ($a) {
                run();
            } ELSE {
                stop();
            }

            FUNCTION a(): void {}

            $b = STATIC FN() => 1;

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        preg_match_all('/:(\d+):\d+: error - .*\(([\w.]+)\)$/m', $result['output'], $matches, PREG_SET_ORDER);
        $reports = array_map(static fn(array $match): string => "{$match[1]} {$match[2]}", $matches);
        $code = 'PERCS31.PHP.LowerCaseKeyword.Found';
        $expected = ["3 {$code}", "5 {$code}", "9 {$code}", "11 {$code}", "11 {$code}"];

        $this->assertSame($expected, $reports, $result['output']);
        $this->assertSame(strtolower($source), $fixed);
    }

    /**
     * Test that the spacing of a function's declaration, and of an anonymous class's argument list, is reported once.
     * Squiz.Functions.FunctionDeclaration and PSR12.Classes.AnonClassDeclaration, which PSR-12 includes, reported each
     * a second time.
     */
    public function testDeclarationAndAnonClassArgumentSpacingIsReportedOnce(): void
    {
        $source = <<<'PHP'
            <?php

            function  a (): void
            {
                run();
            }

            $b = new class ( $c ) {
                public int $d = 1;
            };

            $e = new class ($c,
              $f, $g) {
                public int $d = 1;
            };

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $after = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        preg_match_all('/:(\d+):\d+: error - .*\.(\w+)\)$/m', $result['output'], $matches, PREG_SET_ORDER);
        $reports = array_map(static fn(array $match): string => "{$match[1]} {$match[2]}", $matches);
        $expected = [
            '3 SpaceAfterFunction',
            '3 SpaceBeforeOpenParen',
            '8 SpaceAfterOpenBracket',
            '8 SpaceBeforeCloseBracket',
            '12 ContentAfterOpenBracket',
            '13 Indent',
            '13 MultipleArguments',
            '13 CloseBracketLine',
        ];

        $this->assertSame($expected, $reports, $result['output']);
        $this->assertStringNotContainsString('Squiz.Functions.FunctionDeclaration', $result['output']);
        $this->assertStringNotContainsString('AnonClassDeclaration', $result['output']);
        $this->assertStringNotContainsString(' error - ', $after['output']);
    }

    /**
     * Test that each problem that two sniffs used to report is reported once, and that phpcbf fixes them all: the space
     * after a class's or a promoted parameter's modifier, between a type and a property or a promoted parameter, a
     * trait's conflict statements on one line, a closing brace after code, or indented wrongly, a type written as a
     * keyword, and code on the opening tag's line.
     */
    public function testProblemsOnceReportedTwiceAreReportedOnce(): void
    {
        $class = <<<'PHP'
            <?php

            namespace Vendor\Package;

            final  class Holder
            {
                use First {
                    First::a as b; First::c as d;
                }
                use Second {
                    Second::e as f; }

                public array  $items = [];

                public function __construct(private int  $count, public  readonly int $size)
                {
                    run();
                  }

                public function next(): SELF
                {
                    return $this;
                }

                public int $last = 1; }

            PHP;
        $declare = <<<'PHP'
            <?php

            declare(ticks=1) {
                run();
              }

            PHP;
        $sources = [$class, "<?php echo 1;\n", $declare];
        $files = array_map(fn(string $source): string => $this->copyToTempFile(null, $source), $sources);
        $reports = [];
        $remaining = '';

        try {
            foreach ($files as $file) {
                $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
                preg_match_all('/:(\d+):\d+: error - .*\(([\w.]+)\)$/m', $result['output'], $matches, PREG_SET_ORDER);
                $reports[] = array_map(static fn(array $match): string => "{$match[1]} {$match[2]}", $matches);
            }

            $this->runTool('phpcbf', ['--standard=PERCS31', ...$files]);
            $remaining = $this->runTool('phpcs', ['--standard=PERCS31', '--report=emacs', ...$files])['output'];
        } finally {
            array_map(unlink(...), $files);
        }

        $expected = [
            [
                '5 Squiz.WhiteSpace.ScopeKeywordSpacing.Incorrect',
                '8 PERCS31.Formatting.DisallowMultipleStatements.SameLine',
                '11 PERCS31.Classes.TraitUseBlock.CloseBraceNotOnOwnLine',
                '13 PERCS31.Classes.PropertyDeclaration.SpacingAfterType',
                '15 Squiz.Functions.FunctionDeclarationArgumentSpacing.SpacingAfterHint',
                '15 Squiz.WhiteSpace.ScopeKeywordSpacing.Incorrect',
                '18 PERCS31.WhiteSpace.ScopeIndent.IncorrectExact',
                '20 PERCS31.PHP.LowerCaseKeyword.Found',
                '25 PERCS31.Classes.ClassDeclaration.CloseBraceAfterBody',
            ],
            ['1 PSR12.Files.OpenTag.NotAlone'],
            ['5 PERCS31.WhiteSpace.ScopeIndent.IncorrectExact'],
        ];

        $this->assertSame($expected, $reports);
        $this->assertStringNotContainsString(' error - ', $remaining);
    }

    /**
     * Test that an upper-case `CASE` or `DEFAULT` in a switch is reported once, by LowerCaseKeyword, and not again by
     * SwitchDeclaration's own codes for the same keywords.
     */
    public function testUpperCaseCaseAndDefaultAreReportedOnce(): void
    {
        $source = <<<'PHP'
            <?php

            switch ($a) {
                CASE 1:
                    run();
                    break;
                DEFAULT:
                    break;
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

        preg_match_all('/:(\d+):\d+: error - .*\(([\w.]+)\)$/m', $result['output'], $matches, PREG_SET_ORDER);
        $reports = array_map(static fn(array $match): string => "{$match[1]} {$match[2]}", $matches);
        $code = 'PERCS31.PHP.LowerCaseKeyword.Found';

        $this->assertSame(["4 {$code}", "7 {$code}"], $reports, $result['output']);
        $this->assertSame(strtolower($source), $fixed);
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
     * Test that a `do` loop with no braces is given them, and that the `while` that ends it isn't taken for a loop with
     * no body. The fix for that replaced its `;` with `{}`, which is a parse error.
     */
    public function testDoLoopWithNoBracesKeepsItsWhile(): void
    {
        $source = <<<'PHP'
            <?php

            do
                $i++;
            while ($i < 10);

            do run(); while ($ready);

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $expected = <<<'PHP'
            <?php

            do {
                $i++;
            } while ($i < 10);

            do {
                run();
            } while ($ready);

            PHP;

        $this->assertStringNotContainsString('ControlStructureEmptyStatement', $result['output']);
        $this->assertSame($expected, $fixed);
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
     * Test that a space or line break inside an empty array's brackets is reported once, and fixed.
     */
    public function testSpaceInEmptyArrayIsReportedOnce(): void
    {
        $source = <<<'PHP'
            <?php

            declare(strict_types=1);

            echo count([ ]), count([   ]), count(foo([ ]));
            echo count([
            ]);

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        $this->assertSame(4, substr_count($result['output'], 'SpaceInEmptyArray'), $result['output']);
        $this->assertSame(str_replace(['[   ]', '[ ]', "[\n]"], '[]', $source), $fixed);
    }

    /**
     * Test that a throw expression in a case isn't reported, that a terminating statement sharing a line is reported
     * once, and that the fix puts it on the next line with no blank line before it.
     */
    public function testTerminatingStatementInCaseIsReportedOnce(): void
    {
        $source = <<<'PHP'
            <?php

            switch ($a) {
                case 1:
                    $b = foo() ?? throw new \Exception();
                    break;
                case 2: break;
                case 3:
                    run(); break;
                case 4:
                    if ($b) {
                        run();
                    } return;
            }

            PHP;
        $expected = <<<'PHP'
            <?php

            switch ($a) {
                case 1:
                    $b = foo() ?? throw new \Exception();
                    break;
                case 2:
                    break;
                case 3:
                    run();
                    break;
                case 4:
                    if ($b) {
                        run();
                    }
                    return;
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

        preg_match_all('/:(\d+):\d+: error - .*\(([\w.]+)\)$/m', $result['output'], $matches, PREG_SET_ORDER);
        $reports = array_map(static fn(array $match): string => "{$match[1]} {$match[2]}", $matches);

        $this->assertSame(
            [
                '7 PERCS31.ControlStructures.SwitchDeclaration.StatementOnLabelLine',
                '9 PERCS31.Formatting.DisallowMultipleStatements.SameLine',
                '13 PERCS31.Formatting.AfterClosingBrace.StatementAfter',
            ],
            $reports,
            $result['output'],
        );
        $this->assertSame($expected, $fixed);
    }

    /**
     * Test that a file's first statement indented too far is reported and fixed, split over lines or not. Nothing
     * reported it, so ScopeIndent wanted the next statement indented to match, ConsistentIndent undid that, and phpcbf
     * failed.
     */
    public function testOverIndentedFirstStatementIsFixed(): void
    {
        $source = <<<'PHP'
            <?php

             $a = [
                 1,
             ];

            $b = 2;

            PHP;
        $expected = <<<'PHP'
            <?php

            $a = [
                1,
            ];

            $b = 2;

            PHP;
        $file = $this->copyToTempFile(null, $source);
        $single = $this->copyToTempFile(null, "<?php\n\n    \$a = 1;\n");

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $fixer = $this->runTool('phpcbf', ['--standard=PERCS31', $file, $single]);
            $fixed = (string) file_get_contents($file);
            $fixedSingle = (string) file_get_contents($single);
        } finally {
            unlink($file);
            unlink($single);
        }

        $this->assertMatchesRegularExpression('/:3:\\d+: error - .*ConsistentIndent\\.Incorrect/', $result['output']);
        $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
        $this->assertSame($expected, $fixed);
        $this->assertSame("<?php\n\n\$a = 1;\n", $fixedSingle);
    }

    /**
     * Test that phpcbf leaves a block in a case alone when statements follow it. ScopeIndent moved its closing brace
     * level with the case, and ConsistentIndent then moved the statement after it, leaving both indented wrongly.
     */
    public function testPhpcbfLeavesBlockInCaseAlone(): void
    {
        $source = <<<'PHP'
            <?php

            switch ($a) {
                case 1:
                    {
                        echo 'a';
                    }
                    echo 'b';
                    break;
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

        $this->assertStringNotContainsString('ScopeIndent', $result['output']);
        $this->assertSame($source, $fixed);
    }

    /**
     * Test that the closing line of a multi-line case condition is reported once, whether it's indented too far or too
     * little, and fixed to line up with the case.
     */
    public function testCaseConditionCloserIsReportedOnceAndFixed(): void
    {
        $tooFarSource = <<<'PHP'
            <?php

            switch (true) {
                case (
                    $a === 10
                    && $b === 20
                    ):
                    break;
            }

            PHP;
        $tooLittleSource = <<<'PHP'
            <?php

            switch (true) {
                case (
                    $a === 10
                    && $b === 20
              ):
                    break;
            }

            PHP;
        $expected = <<<'PHP'
            <?php

            switch (true) {
                case (
                    $a === 10
                    && $b === 20
                ):
                    break;
            }

            PHP;
        $file = $this->copyToTempFile(null, $tooFarSource);
        $little = $this->copyToTempFile(null, $tooLittleSource);
        $args = ['-s', '--standard=PERCS31', '--report=emacs'];

        try {
            $tooFar = $this->runTool('phpcs', [...$args, $file]);
            $tooLittle = $this->runTool('phpcs', [...$args, $little]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file, $little]);
            $fixed = (string) file_get_contents($file);
            $fixedLittle = (string) file_get_contents($little);
        } finally {
            unlink($file);
            unlink($little);
        }

        $this->assertSame(1, substr_count($tooFar['output'], ' error - '), $tooFar['output']);
        $this->assertStringContainsString('MultiLineConditionCloserIndent', $tooFar['output']);
        $this->assertSame(1, substr_count($tooLittle['output'], ' error - '), $tooLittle['output']);
        $this->assertSame($expected, $fixed);
        $this->assertSame($expected, $fixedLittle);
    }

    /**
     * Test that a heredoc argument indented too little is fixed with its body and closing marker moved together, and
     * the result passes. The inherited fix for the argument's indent moved the marker but not the body, which is a
     * parse error.
     */
    public function testUnderIndentedHeredocArgumentIsFixedToValidCode(): void
    {
        $source = <<<'PHP'
            <?php

            function a(string $name): void
            {
              foo(
                $name,
                <<<EOT
                    Hello $name
                    EOT,
              );
            }

            PHP;
        $expected = <<<'PHP'
            <?php

            function a(string $name): void
            {
                foo(
                    $name,
                    <<<EOT
                        Hello $name
                        EOT,
                );
            }

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $fixer = $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
        } finally {
            unlink($file);
        }

        $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
        $this->assertSame($expected, $fixed);
        $this->assertSame(0, $result['exitCode'], $result['output']);
    }

    /**
     * Test that a comma starting a line in a call's arguments is reported once, including after a heredoc's closing
     * marker, as is the spacing around a comma in an array inside the call, and that the fix corrects each one.
     */
    public function testCommaSpacingInCallIsReportedOnceAndFixed(): void
    {
        $source = <<<'PHP'
            <?php

            foo(
                <<<'EOT'
                    x
                    EOT
                ,
                $a
                ,
                [1 , 2],
                [3,4],
                [5,  6],
            );

            PHP;
        $fixedSource = <<<'PHP'
            <?php

            foo(
                <<<'EOT'
                    x
                    EOT,
                $a,
                [1, 2],
                [3, 4],
                [5, 6],
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

        preg_match_all('/:(\d+):\d+: error - .*\.(\w+)\)$/m', $result['output'], $matches, PREG_SET_ORDER);
        $reports = array_map(static fn(array $match): string => "{$match[1]} {$match[2]}", $matches);
        $expected = [
            '7 SpaceBeforeInFunctionCall',
            '9 SpaceBeforeInFunctionCall',
            '10 SpaceBeforeInFunctionCall',
            '11 NoSpaceAfterInFunctionCall',
            '12 TooMuchSpaceAfterInFunctionCall',
        ];

        $this->assertSame($expected, $reports, $result['output']);
        $this->assertSame($fixedSource, $fixed);
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
     * Test that a CRLF in a string, after an LF first line, is an error, with no warning that the file has mixed line
     * endings, and that phpcbf converts it to LF. Generic.Files.LineEndings only looked at the first line.
     */
    public function testCrlfInStringAfterLfFirstLineIsFixed(): void
    {
        $source = "<?php\n\n\$a = 'x\r\ny';\n\$b = <<<'EOT'\n    x\r\n    EOT;\n";
        $file = $this->copyToTempFile(null, $source);

        try {
            $result = $this->runTool('phpcs', ['-s', '--standard=PERCS31', '--report=emacs', $file]);
            $this->runTool('phpcbf', ['--standard=PERCS31', $file]);
            $fixed = (string) file_get_contents($file);
        } finally {
            unlink($file);
        }

        preg_match_all('/:(\d+):\d+: (\w+) - .*\(([\w.]+)\)$/m', $result['output'], $matches, PREG_SET_ORDER);
        $reports = array_map(static fn(array $match): string => "{$match[1]} {$match[2]} {$match[3]}", $matches);
        $code = 'PERCS31.Files.LineEndings.InvalidEOLChar';

        $this->assertSame(["3 error {$code}", "6 error {$code}"], $reports, $result['output']);
        $this->assertSame(str_replace("\r\n", "\n", $source), $fixed);
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
}
