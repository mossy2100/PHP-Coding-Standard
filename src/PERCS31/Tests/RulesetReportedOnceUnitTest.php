<?php

declare(strict_types=1);

namespace PERCS31\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Tests that a problem two sniffs in the full PERCS31 ruleset can see is reported once, and fixed once.
 */
#[CoversNothing]
class RulesetReportedOnceUnitTest extends TestCase
{
    use RunsToolsTrait;

    #region Reported once tests.

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

    #endregion
}
