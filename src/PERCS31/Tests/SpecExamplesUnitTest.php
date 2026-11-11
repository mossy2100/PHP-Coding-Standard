<?php

declare(strict_types=1);

namespace PERCS31\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests that the PERCS31 standard passes the code examples in the PER Coding Style 3.1 spec, and reports only the ones
 * that break it.
 *
 * Each file in SpecExamples is one `php` block from the spec (tag 3.1.0), numbered in order and named after its
 * section, with `<?php` added where the block has none, and invisible trailing whitespace removed. The attribute
 * example in section 8 is left out, as its `...` body isn't valid PHP. PSR-1's checks for a namespace and side effects
 * are excluded, as the examples are fragments.
 *
 * An example is expected to pass unless it's marked "not allowed" in the spec, or breaks one of the spec's own rules,
 * as the rule text beats an example. Messages below the default warning severity, such as a line over 80 characters,
 * aren't shown.
 */
#[CoversNothing]
class SpecExamplesUnitTest extends TestCase
{
    use RunsToolsTrait;

    /**
     * The fixture directory.
     */
    private const string FIXTURES = __DIR__ . '/SpecExamples';

    /**
     * The messages expected for each example that has any, as "line source".
     *
     * @var array<string, list<string>>
     */
    private const array EXPECTED = [
        // somethingWithReflection has no trailing comma (2.6), and returns object|null, not ?object (2.5). veryComplex
        // has no trailing comma, and its ) and { on different lines (4.5).
        '02-2.5.inc' => [
            '13 SlevomatCodingStandard.Functions.RequireTrailingCommaInDeclaration.MissingTrailingComma',
            '14 PERCS31.TypeHints.NullType.ShortNullable',
            '26 SlevomatCodingStandard.Functions.RequireTrailingCommaInDeclaration.MissingTrailingComma',
            '26 PERCS31.Functions.MultiLineFunctionDeclaration.CloseBracketLine',
            '27 PERCS31.Functions.MultiLineFunctionDeclaration.NewlineBeforeOpenBrace',
        ],
        // Not allowed: too many namespace segments in a group.
        '06-3.inc' => ['5 PSR12.Namespaces.CompoundNamespaceDepth.TooDeep'],
        // SHOULD be avoided: (new Foo())->someMethod().
        '12-4.inc' => ['3 PERCS31.Classes.ClassInstantiationNoBrackets.NewWithUnnecessaryParentheses'],
        // A promoted hook with no trailing comma (2.6).
        '41-4.9.inc' => ['6 SlevomatCodingStandard.Functions.RequireTrailingCommaInDeclaration.MissingTrailingComma'],
        // Not allowed: a promoted hook that's too complex. It has no trailing comma either (2.6).
        '42-4.9.inc' => [
            '6 PERCS31.Classes.PromotedPropertyHooks.TooComplex',
            '13 SlevomatCodingStandard.Functions.RequireTrailingCommaInDeclaration.MissingTrailingComma',
        ],
        // abstract class Example { has its brace on the class's line (4.1).
        '43-4.10.inc' => ['3 PERCS31.Classes.ClassDeclaration.OpenBraceNewLine'],
        // An empty constructor body not written as {} (4.4).
        '72-8.inc' => ['17 PERCS31.Formatting.EmptyBody.NotAbbreviated'],
        // Not allowed: a nowdoc on the line after its context.
        '75-10.inc' => ['4 PERCS31.Strings.HeredocIndentation.NotOnContextLine'],
        // Not allowed: a nowdoc not indented past its scope.
        '76-10.inc' => ['10 PERCS31.Strings.HeredocIndentation.Indent'],
        // A heredoc with nothing to interpolate, which SHOULD be a nowdoc (10).
        '77-10.inc' => ['5 Generic.Strings.UnnecessaryHeredoc.Found'],
    ];

    /**
     * Provides each spec example's file name.
     *
     * @return iterable<string, array{string}>
     */
    public static function exampleProvider(): iterable
    {
        foreach (glob(self::FIXTURES . '/*.inc') ?: [] as $path) {
            $name = basename($path);
            yield $name => [$name];
        }
    }

    /**
     * Test that a spec example gets exactly the messages expected for it.
     *
     * @param string $name The example's file name.
     */
    #[DataProvider('exampleProvider')]
    public function testExampleGetsExpectedMessages(string $name): void
    {
        $result = $this->runTool('phpcs', [
            '--standard=PERCS31',
            '--exclude=PSR1.Classes.ClassDeclaration,PSR1.Files.SideEffects',
            '--extensions=inc',
            '--no-cache',
            '--report=json',
            self::FIXTURES . '/' . $name,
        ]);

        $this->assertSame(self::EXPECTED[$name] ?? [], $this->messages($result['output']));
    }

    /**
     * List the messages in a phpcs JSON report, as "line source".
     *
     * @param string $json The report.
     * @return list<string> The messages, in order.
     */
    private function messages(string $json): array
    {
        $report = json_decode($json, true);
        $this->assertIsArray($report, $json);
        $files = $report['files'] ?? [];
        $this->assertIsArray($files);
        $messages = [];
        foreach ($files as $file) {
            $this->assertIsArray($file);
            $this->assertIsArray($file['messages']);
            foreach ($file['messages'] as $message) {
                $this->assertIsArray($message);
                $this->assertIsInt($message['line']);
                $this->assertIsString($message['source']);
                $messages[] = $message['line'] . ' ' . $message['source'];
            }
        }

        return $messages;
    }
}
