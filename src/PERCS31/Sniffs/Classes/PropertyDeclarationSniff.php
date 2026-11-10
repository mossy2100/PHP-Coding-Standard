<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\PropertyDeclarationSniff as Psr2Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Verifies that properties are declared correctly: no leading underscore, no var keyword, a visibility, and `get`
 * before `set` in a hook list.
 *
 * It extends PSR2.Classes.PropertyDeclaration, and changes four things:
 * - It skips a variable in a PHP 8.4 property hook's body, such as `$value`, which the parent reads as a property.
 * - It checks a promoted constructor parameter for a visibility, which the parent doesn't.
 * - It reports a leading underscore as LeadingUnderscore, an error, as section 4.3 says MUST NOT, on every property,
 *   including a promoted one. A double underscore, as in `$__a`, is allowed, as the rule is about a single one, and
 *   PSR2.Methods.MethodDeclaration reads the same rule that way for methods. The parent's Underscore check can't skip
 *   it, so the ruleset excludes that code.
 * - It checks that `get` comes before `set` in an abstract or interface property's hooks, as PER Coding Style 3.1,
 *   section 4.10, requires. Set orderHooksWithBodies to check every hook list, as the Jumbo standard does.
 *
 * The ruleset excludes the parent's checks that Slevomat sniffs make: modifier order, and the spacing after the type,
 * by SlevomatCodingStandard.Classes.PropertyDeclaration, and several properties in one statement, by
 * SlevomatCodingStandard.Classes.DisallowMultiPropertyDefinition.
 */
class PropertyDeclarationSniff extends Psr2Sniff
{
    public const string CODE_LEADING_UNDERSCORE = 'LeadingUnderscore';

    /**
     * Whether HookOrder also checks a hook list with bodies, not just one like `{ set; get; }`.
     *
     * PER Coding Style 3.1, section 4.10, only sets the order of an abstract or interface property's hooks, which have
     * no bodies. The Jumbo standard turns this on.
     */
    public bool $orderHooksWithBodies = false;

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
        $this->checkHookOrder($phpcsFile, $stackPtr, $phpcsFile->getTokens());
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
     * Check that the `get` hook comes before the `set` hook, when a property has both.
     *
     * PER Coding Style 3.1, section 4.10, requires this order for an abstract or interface property with no hook
     * bodies, so only a list with no bodies is checked, unless $orderHooksWithBodies is on. Then every hook list is
     * checked, including ones with bodies, `&` or attributes. The fix swaps the two hooks, with their attributes,
     * modifiers and bodies. It isn't offered if there's a comment between the hooks.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the property token.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     */
    private function checkHookOrder(File $phpcsFile, int $stackPtr, array $tokens): void
    {
        $hookList = PropertyHookHelper::findHookList($phpcsFile, $stackPtr);
        if ($hookList === null) {
            return;
        }

        [$opener, $closer] = $hookList;

        $hooks = PropertyHookHelper::parseHooks($phpcsFile, $opener, $closer);
        if ($hooks === null) {
            return;
        }

        if (!$this->orderHooksWithBodies && in_array(true, array_column($hooks, 'hasBody'), true)) {
            return;
        }

        $getIndex = null;
        $setIndex = null;
        foreach ($hooks as $index => $hook) {
            if ($hook['name'] === 'get') {
                $getIndex = $index;
            } elseif ($hook['name'] === 'set') {
                $setIndex = $index;
            }
        }

        if ($getIndex === null || $setIndex === null || $getIndex < $setIndex) {
            return;
        }

        $error = 'The get hook must come before the set hook';
        $setHook = $hooks[$setIndex];
        $getHook = $hooks[$getIndex];
        if ($this->hasCommentBetweenHooks($phpcsFile, $opener, $closer, $hooks)) {
            $phpcsFile->addError($error, $setHook['namePtr'], 'HookOrder');
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $setHook['namePtr'], 'HookOrder');
        if (!$fix) {
            return;
        }

        $setText = $phpcsFile->getTokensAsString($setHook['start'], ($setHook['end'] - $setHook['start'] + 1));
        $getText = $phpcsFile->getTokensAsString($getHook['start'], ($getHook['end'] - $getHook['start'] + 1));
        $phpcsFile->fixer->beginChangeset();
        foreach ([[$setHook, $getText], [$getHook, $setText]] as [$hook, $text]) {
            $phpcsFile->fixer->replaceToken($hook['start'], $text);
            for ($i = ($hook['start'] + 1); $i <= $hook['end']; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check whether there's a comment in the hook list outside the hooks themselves.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the hook list's opening brace.
     * @param int $closer The position of the hook list's closing brace.
     * @param list<array{name: string, start: int, end: int, namePtr: int, hasBody: bool, arrowPtr: int|null,
     *     bodyOpener: int|null}> $hooks The hooks.
     * @return bool True if there's a comment between, before or after the hooks.
     */
    private function hasCommentBetweenHooks(File $phpcsFile, int $opener, int $closer, array $hooks): bool
    {
        $from = $opener + 1;
        foreach ([...$hooks, ['start' => $closer, 'end' => $closer]] as $hook) {
            if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, $from, $hook['start']) !== false) {
                return true;
            }

            $from = $hook['end'] + 1;
        }

        return false;
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
        $visibilities = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_PUBLIC_SET, T_PROTECTED_SET, T_PRIVATE_SET];
        for ($i = ($stackPtr - 1); $i > 0; $i--) {
            $code = $tokens[$i]['code'];
            if (in_array($code, [T_OPEN_PARENTHESIS, T_COMMA, T_ATTRIBUTE_END], true)) {
                break;
            }

            if (in_array($code, $visibilities, true)) {
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
