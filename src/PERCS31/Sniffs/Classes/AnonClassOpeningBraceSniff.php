<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Classes\OpeningBraceSameLineSniff as GenericSniff;

/**
 * Requires one space before an anonymous class's opening brace when it's on the declaration's line, and nothing after
 * the brace on its line.
 *
 * PER Coding Style 3.1, section 8, says anonymous classes MUST follow the same guidelines as closures, and its examples
 * show one space before the brace, and the body on the next line. PSR12.Classes.AnonClassDeclaration only checks which
 * line the brace is on. Generic.Classes.OpeningBraceSameLine makes both checks, but only for a class, interface, trait
 * or enum, and it has no property to add to those. So this extends it, and registers an anonymous class instead.
 *
 * The parent also requires the brace on the declaration's line, as BraceOnNewLine. Section 8 allows the next line, and
 * requires it after a wrapped list of interfaces, so the PERCS31 ruleset excludes that code.
 *
 * It also requires a space between an anonymous class's arguments and `extends` or `implements`, as in
 * `new class ($a) extends Foo`, which is how section 8's example writes it. PSR12.Classes.AnonClassDeclaration reports
 * more than one space there, but not none, as in `new class ($a)extends Foo`. Only that case is reported here, as
 * SpaceAfterArguments.
 */
class AnonClassOpeningBraceSniff extends GenericSniff
{
    public const string CODE_SPACE_AFTER_ARGUMENTS = 'SpaceAfterArguments';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_ANON_CLASS];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $this->checkSpaceAfterArguments($phpcsFile, $stackPtr);
        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * Report `extends` or `implements` straight after the closing parenthesis of an anonymous class's arguments, and
     * fix it by adding a space.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the anonymous class's `class` keyword.
     */
    private function checkSpaceAfterArguments(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
        $next = is_int($closer) ? ($tokens[($closer + 1)] ?? null) : null;
        if (!is_int($closer) || $next === null || !in_array($next['code'], [T_EXTENDS, T_IMPLEMENTS], true)) {
            return;
        }

        FixerHelper::requireSingleSpace(
            $phpcsFile,
            $closer,
            ($closer + 1),
            'Expected 1 space before the %s keyword of an anonymous class; 0 found',
            self::CODE_SPACE_AFTER_ARGUMENTS,
            data: [strtolower($next['content'])],
        );
    }
}
