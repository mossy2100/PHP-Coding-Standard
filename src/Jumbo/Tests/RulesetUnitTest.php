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
     * Test that a blank line after a case label is only reported in Jumbo, and that a comment on its line is in both.
     */
    public function testLabelLineInBothStandards(): void
    {
        $blank = $this->copyToTempFile(null, "<?php\n\nswitch (\$a) {\n    case 1:\n\n        break;\n}\n");
        $comment = $this->copyToTempFile(null, "<?php\n\nswitch (\$a) {\n    case 1: // Why.\n        break;\n}\n");
        $sniff = '--sniffs=PERCS31.ControlStructures.SwitchDeclaration';

        try {
            $perBlank = $this->runTool('phpcs', ['--standard=PERCS31', $sniff, '-s', $blank])['output'];
            $jumboBlank = $this->runTool('phpcs', ['--standard=Jumbo', $sniff, '-s', $blank])['output'];
            $perComment = $this->runTool('phpcs', ['--standard=PERCS31', $sniff, '-s', $comment])['output'];
            $jumboComment = $this->runTool('phpcs', ['--standard=Jumbo', $sniff, '-s', $comment])['output'];
        } finally {
            unlink($blank);
            unlink($comment);
        }

        $this->assertStringNotContainsString('SwitchDeclaration', $perBlank);
        $this->assertStringContainsString('SwitchDeclaration.BlankLineAfterLabel', $jumboBlank);
        $this->assertStringContainsString('SwitchDeclaration.CommentOnCaseLine', $perComment);
        $this->assertStringContainsString('SwitchDeclaration.CommentOnCaseLine', $jumboComment);
    }

    /**
     * Test that a statement after a block declare in the header is one spacing error in both standards, as section 3
     * wants a blank line, and that one after any other control structure is an error in Jumbo only.
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
            $this->assertStringNotContainsString('StatementAfter', $perCs['output']);
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
            $file = $this->copyToTempFile(null, $header . $code);

            try {
                $fixer = $this->runTool('phpcbf', ['--standard=Jumbo', $file]);
                $fixed = (string) file_get_contents($file);
            } finally {
                unlink($file);
            }

            $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
            if ($code === $multiLine) {
                $this->assertSame($header . $multiLine, $fixed);
            }
        }
    }

    /**
     * Test that a match arm split before its value is brought onto one line if it fits, in Jumbo, and left in PERCS31.
     */
    public function testWrappedMatchArmIsJoinedInJumboOnly(): void
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace A;\n\n\$a = match (\$x) {\n";
        $footer = "    default => 9,\n};\n";
        foreach (['Jumbo' => "    1, 2 => 3,\n", 'PERCS31' => "    1,\n    2\n    => 3,\n"] as $standard => $expected) {
            $file = $this->copyToTempFile(null, $header . "    1,\n    2\n    => 3,\n" . $footer);

            try {
                $fixer = $this->runTool('phpcbf', ["--standard=$standard", $file]);
                $fixed = (string) file_get_contents($file);
            } finally {
                unlink($file);
            }

            $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
            $this->assertSame($header . $expected . $footer, $fixed);
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
            $file = $this->copyToTempFile(null, $header . $arms . $footer);

            try {
                $result = $this->runTool('phpcs', ['-s', "--standard=$standard", '--report=emacs', $file]);
                $fixer = $this->runTool('phpcbf', ["--standard=$standard", $file]);
                $fixed = (string) file_get_contents($file);
            } finally {
                unlink($file);
            }

            $pattern = '/:(\\d+):\\d+: error - .*Universal\\.WhiteSpace\\.CommaSpacing\\.(\\w+)\\)/';
            preg_match_all($pattern, $result['output'], $matches, PREG_SET_ORDER);
            $found = array_map(static fn(array $match): string => $match[1] . ':' . $match[2], $matches);
            $this->assertSame(['8:NoSpaceAfter', '9:SpaceBefore'], $found, $result['output']);
            $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
            $this->assertSame($header . "    1, 2 => 3,\n    4, 5 => 6,\n" . $footer, $fixed);
        }
    }

    /**
     * Test that `final` must come before a constant's visibility in Jumbo, and that either order passes in PERCS31.
     */
    public function testConstantModifierOrderIsFixedInJumboOnly(): void
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace A;\n\nclass B\n{\n";
        $footer = "}\n";
        $code = "    public final const int C = 1;\n";
        foreach (['Jumbo' => "    final public const int C = 1;\n", 'PERCS31' => $code] as $standard => $expected) {
            $file = $this->copyToTempFile(null, $header . $code . $footer);

            try {
                $result = $this->runTool('phpcs', ['-s', "--standard=$standard", '--report=emacs', $file]);
                $fixer = $this->runTool('phpcbf', ["--standard=$standard", $file]);
                $fixed = (string) file_get_contents($file);
            } finally {
                unlink($file);
            }

            $reported = str_contains($result['output'], 'Universal.Constants.ModifierKeywordOrder.Incorrect');
            $this->assertSame($standard === 'Jumbo', $reported, $result['output']);
            $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
            $this->assertSame($header . $expected . $footer, $fixed);
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
            $result = $this->runTool('phpcs', ['-s', "--standard=$standard", '--report=emacs', $fixture]);

            $pattern = '/:(\\d+):\\d+: error - .*\\.(NoSpace(?:Before|After)|Space(?:Before|After)Equals)\\)/';
            preg_match_all($pattern, $result['output'], $matches, PREG_SET_ORDER);
            $found = array_map(static fn(array $match): string => $match[1] . ':' . $match[2], $matches);
            $this->assertSame($codes, $found, $result['output']);
        }
    }

    /**
     * Test that a hook's own opening brace is moved to one space after its name, in Jumbo, and left in PERCS31.
     */
    public function testHookBraceSpacingIsFixedInJumboOnly(): void
    {
        $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace A;\n\nfinal class B\n{\n    public int \$c {\n";
        $body = "            return 1;\n        }\n    }\n}\n";
        foreach (['Jumbo' => "        get {\n", 'PERCS31' => "        get\n        {\n"] as $standard => $expected) {
            $file = $this->copyToTempFile(null, $header . "        get\n        {\n" . $body);

            try {
                $fixer = $this->runTool('phpcbf', ["--standard=$standard", $file]);
                $fixed = (string) file_get_contents($file);
            } finally {
                unlink($file);
            }

            $this->assertStringNotContainsString('FAILED TO FIX', $fixer['output']);
            $this->assertSame($header . $expected . $body, $fixed);
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
            $file = $this->copyToTempFile(null, "<?php\n\n$code }\n");

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
     * Test that both standards allow one blank line between parameters, and that Jumbo also reports more than one, or
     * one before a closing parenthesis or between `use` items, which PER doesn't forbid. The parent's EmptyLine, which
     * reports them all, is excluded in the ruleset.
     */
    public function testBlankLinesInDeclaration(): void
    {
        $fixture = dirname(__DIR__, 2) . '/Jumbo/Tests/Ruleset/BlankLinesInDeclaration.inc';
        $sniff = '--sniffs=PERCS31.Functions.MultiLineFunctionDeclaration';
        foreach (['PERCS31' => ['20'], 'Jumbo' => ['16', '20', '32', '41']] as $standard => $expected) {
            $result = $this->runTool('phpcs', ['-s', "--standard=$standard", $sniff, '--report=emacs', $fixture]);

            preg_match_all('/:(\\d+):\\d+: error - .*\\.BlankLine\\)/', $result['output'], $matches);
            $this->assertSame($expected, $matches[1], $result['output']);
            $this->assertStringNotContainsString('EmptyLine', $result['output']);
        }
    }

    /**
     * Test that only Jumbo reports a blank line between a call's arguments or before its closing parenthesis, and that
     * both standards report one before the first argument.
     */
    public function testBlankLinesInCall(): void
    {
        $fixture = dirname(__DIR__, 2) . '/Jumbo/Tests/Ruleset/BlankLinesInCall.inc';
        $sniff = '--sniffs=PSR2.Methods.FunctionCallSignature';
        foreach (['PERCS31' => [], 'Jumbo' => ['9', '15', '20']] as $standard => $expected) {
            $result = $this->runTool('phpcs', ['-s', "--standard=$standard", $sniff, '--report=emacs', $fixture]);

            preg_match_all('/:(\\d+):\\d+: error - .*\\.EmptyLine\\)/', $result['output'], $matches);
            $this->assertSame($expected, $matches[1], $result['output']);
            $this->assertStringContainsString('FirstArgumentPosition', $result['output']);
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
     * Test the naming rules, in both standards: approved acronyms only apply to the names AcronymCasing checks.
     */
    #[DataProvider('standardProvider')]
    public function testNamingRules(string $standard): void
    {
        $source = <<<'PHP'
            <?php

            // phpcs:set PERCS31.NamingConventions.AcronymCasing excludedAcronyms HTTP
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
            $args = ['-s', "--standard=$standard", '--warning-severity=4', '--report=emacs', $file];
            $result = $this->runTool('phpcs', $args);
        } finally {
            unlink($file);
        }

        $pattern = '/:(\d+):\d+: (\w+) - .*\.(NotPascalCase|AcronymNotLikeWord|EnumConstant\w+)\)$/m';
        preg_match_all($pattern, $result['output'], $matches, PREG_SET_ORDER);
        $reports = array_map(static fn(array $match): string => "$match[1] $match[2] $match[3]", $matches);
        $expected = [
            '4 error NotPascalCase',
            '6 error NotPascalCase',
            '8 error NotPascalCase',
            '10 error NotPascalCase',
            '12 error NotPascalCase',
            '14 error EnumConstantInvalidCase',
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
        $source = "<?php\n\nconst userId = 1;\n\ndefine('userId', 2);\n\nconst HTTP_OK = 3;\n\n"
            . "const userID = 4;\n\nuse const Foo\\BAR as userId;\n";
        $file = $this->copyToTempFile(null, $source);
        $reports = [];

        try {
            foreach (['PERCS31', 'Jumbo'] as $standard) {
                $args = ['-s', "--standard=$standard", '--report=emacs', $file];
                $output = $this->runTool('phpcs', $args)['output'];
                $pattern = '/:(\\d+):\\d+: \\w+ - .*\\.(ConstantNotUpperCase|AcronymNotLikeWord)\\)$/m';
                preg_match_all($pattern, $output, $matches, PREG_SET_ORDER);
                $reports[$standard] = array_map(static fn(array $match): string => "$match[1] $match[2]", $matches);
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
     * Test that two or more attribute blocks go on separate lines in both standards, splitting a single-line list,
     * with the target on the next line, and that phpcbf gives the same result in each.
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

                #[A, B] case Z;
            }

            function f(
                #[A]
                #[B]
                int $c,
            ): void {}

            usort(
                $items,
                #[A]
                #[B]
                static fn($x) => $x,
            );

            $pure = #[A]
                #[B]
                static fn($x) => $x;

            PHP;
        $file = $this->copyToTempFile(null, $source);

        try {
            $this->runTool('phpcbf', ["--standard=$standard", $file]);
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
        $source = "<?php\n\nnamespace App;\n\nclass A\n{\n    public string \$_a = 'x';\n\n"
            . "    static public int \$b = 1;\n}\n";
        $file = $this->copyToTempFile(null, $source);

        try {
            $args = ['-s', "--standard=$standard", '--report=emacs', $file];
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
}
