<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PHP_CodeSniffer\Standards\Generic\Sniffs\Classes\OpeningBraceSameLineSniff as GenericSniff;

/**
 * Requires one space before an anonymous class's opening brace when it's on the declaration's line, and nothing after
 * the brace on its line.
 *
 * PER Coding Style 3.1, section 8, says anonymous classes MUST follow the same guidelines as closures, and its examples
 * show one space before the brace, and the body on the next line. PSR12.Classes.AnonClassDeclaration only checks which
 * line the brace is on. Generic.Classes.OpeningBraceSameLine makes both checks, but only for a class, interface, trait
 * or enum, and it has no property to add to those. So this extends it, and changes one thing: it registers an anonymous
 * class instead.
 *
 * The parent also requires the brace on the declaration's line, as BraceOnNewLine. Section 8 allows the next line, and
 * requires it after a wrapped list of interfaces, so the PERCS31 ruleset excludes that code.
 */
class AnonClassOpeningBraceSniff extends GenericSniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_ANON_CLASS];
    }
}
