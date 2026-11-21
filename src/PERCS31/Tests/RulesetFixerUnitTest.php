<?php

declare(strict_types=1);

namespace PERCS31\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Tests of phpcbf with the full PERCS31 ruleset: that the sniffs' fixes work together, giving valid code,
 * without two fixers undoing each other's changes or looping.
 */
#[CoversNothing]
class RulesetFixerUnitTest extends TestCase
{
    use RunsToolsTrait;

    #region Fixer tests.

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

    #endregion
}
