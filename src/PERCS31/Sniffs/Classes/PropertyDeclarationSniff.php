<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\PropertyDeclarationSniff as Psr2Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Verifies that properties are declared correctly: no leading underscore, no var keyword, and a visibility.
 *
 * It extends PSR2.Classes.PropertyDeclaration, and changes three things:
 * - It skips a variable in a PHP 8.4 property hook's body, such as `$value`, which the parent reads as a property.
 * - It checks a promoted constructor parameter for a visibility, which the parent doesn't.
 * - It reports a leading underscore as LeadingUnderscore, an error, as section 4.3 says MUST NOT, on every property,
 *   including a promoted one. A double underscore, as in `$__a`, is allowed, as the rule is about a single one, and
 *   PSR2.Methods.MethodDeclaration reads the same rule that way for methods. The parent's Underscore check can't skip
 *   it, so the ruleset excludes that code.
 *
 * `get` before `set` in a property's hooks is checked by PERCS31.Classes.PropertyHookLayout.
 *
 * The ruleset excludes the parent's checks that Slevomat sniffs make: modifier order, and the spacing after the type,
 * by SlevomatCodingStandard.Classes.PropertyDeclaration, and several properties in one statement, by
 * SlevomatCodingStandard.Classes.DisallowMultiPropertyDefinition.
 *
 * The last of those, the parent's Multiple code, must stay excluded for a second reason. The parent reads a hook list
 * as a second property, so it reports every property with hooks, and this sniff inherits that. The check is in the
 * middle of the parent's processMemberVar(), so it can't be turned off from here.
 */
class PropertyDeclarationSniff extends Psr2Sniff
{
    public const string CODE_LEADING_UNDERSCORE = 'LeadingUnderscore';

    /**
     * Processes the function tokens within the class.
     *
     * @param File $phpcsFile The file where this token was found.
     * @param int $stackPtr The position where the token was found.
     * @return void
     */
    protected function processMemberVar(File $phpcsFile, int $stackPtr): void
    {
        // If this variable is inside a property hook body, skip it.
        if (PropertyHookHelper::isInsidePropertyHook($phpcsFile, $stackPtr)) {
            return;
        }

        parent::processMemberVar($phpcsFile, $stackPtr);
        $this->checkUnderscore($phpcsFile, $stackPtr, $phpcsFile->getTokens());
    }

    /**
     * Check a property's name for a single leading underscore, which PER Coding Style 3.1 disallows.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the property token.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     */
    private function checkUnderscore(File $phpcsFile, int $stackPtr, array $tokens): void
    {
        $content = $tokens[$stackPtr]['content'];
        assert(is_string($content));
        if (str_starts_with($content, '$_') && !str_starts_with($content, '$__')) {
            $error = 'Property name "%s" must not be prefixed with an underscore to indicate visibility';
            $phpcsFile->addError($error, $stackPtr, self::CODE_LEADING_UNDERSCORE, [$content]);
        }
    }

    /**
     * Processes normal variables, which include promoted constructor parameters.
     *
     * @param File $phpcsFile The file where this token was found.
     * @param int $stackPtr The position where the token was found.
     * @return void
     */
    protected function processVariable(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // A promoted constructor parameter is a property, so it gets the underscore and visibility checks.
        if (
            isset($tokens[$stackPtr]['nested_parenthesis'])
            && PropertyHookHelper::isPropertyVariable($phpcsFile, $stackPtr)
        ) {
            $this->checkUnderscore($phpcsFile, $stackPtr, $tokens);
            $this->checkPromotedVisibility($phpcsFile, $stackPtr);
        }
    }

    /**
     * Report a promoted constructor parameter with no visibility, such as `readonly int $a` or `final int $a`, which
     * PHP makes public. Section 4.3 says visibility MUST be declared on all properties. A set-visibility alone, as in
     * `private(set) int $a`, is allowed, as section 4.3 says the public get-visibility MAY then be left out.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the parameter's variable.
     */
    private function checkPromotedVisibility(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = ($stackPtr - 1); $i > 0; $i--) {
            $code = $tokens[$i]['code'];
            if (in_array($code, [T_OPEN_PARENTHESIS, T_COMMA, T_ATTRIBUTE_END], true)) {
                break;
            }

            if (isset(Tokens::SCOPE_MODIFIERS[$code])) {
                return;
            }
        }

        $phpcsFile->addError(
            'Visibility must be declared on property "%s"',
            $stackPtr,
            'ScopeMissing',
            [$tokens[$stackPtr]['content']],
        );
    }
}
