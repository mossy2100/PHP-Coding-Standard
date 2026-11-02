<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\Classes;

use Exception;
use Mossy2100\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractVariableSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Verifies that properties are declared correctly: no leading underscore, no var keyword, and a visibility.
 *
 * This sniff is based on PSR2's PropertyDeclarationSniff, which misreads variables in PHP 8.4 property hook bodies as
 * property declarations. Modifier order and the spacing after the type are left to
 * SlevomatCodingStandard.Classes.PropertyDeclaration, and several properties in one statement to
 * SlevomatCodingStandard.Classes.DisallowMultiPropertyDefinition.
 *
 * Portions of this file are from PHP_CodeSniffer's PSR2.Classes.PropertyDeclaration sniff:
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2023 Squiz Pty Ltd (ABN 77 084 670 600)
 * @copyright 2023 PHPCSStandards and contributors
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/HEAD/licence.txt BSD Licence
 */
class PropertyDeclarationSniff extends AbstractVariableSniff
{
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

        try {
            $propertyInfo = $phpcsFile->getMemberProperties($stackPtr);
        } catch (Exception) {
            // Parse error: property in enum. Ignore.
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $this->checkUnderscore($phpcsFile, $stackPtr, $tokens);
        $this->checkVarKeyword($phpcsFile, $stackPtr, $tokens);
        $this->checkVisibility($phpcsFile, $stackPtr, $tokens, $propertyInfo);
        $this->checkHookOrder($phpcsFile, $stackPtr, $tokens);
    }

    /**
     * Check for underscore-prefixed property names, which PER Coding Style 3.0 disallows.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the property token.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     */
    private function checkUnderscore(File $phpcsFile, int $stackPtr, array $tokens): void
    {
        $content = $tokens[$stackPtr]['content'];
        assert(is_string($content));
        if (isset($content[1]) && $content[1] === '_') {
            $error = 'Property name "%s" must not be prefixed with an underscore';
            $data = [$tokens[$stackPtr]['content']];
            $phpcsFile->addError($error, $stackPtr, 'Underscore', $data);
        }
    }

    /**
     * Check that the var keyword isn't used.
     *
     * Several properties in one statement are left to SlevomatCodingStandard.Classes.DisallowMultiPropertyDefinition,
     * which can fix them.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the property token.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     */
    private function checkVarKeyword(File $phpcsFile, int $stackPtr, array $tokens): void
    {
        $find = Tokens::SCOPE_MODIFIERS;
        $find[] = T_VARIABLE;
        $find[] = T_VAR;
        $find[] = T_READONLY;
        $find[] = T_FINAL;
        $find[] = T_ABSTRACT;
        $find[] = T_SEMICOLON;
        $find[] = T_OPEN_CURLY_BRACKET;

        // A later property in a multi-property statement. The first one was already checked.
        $prev = $phpcsFile->findPrevious($find, ($stackPtr - 1));
        if ($tokens[$prev]['code'] === T_VARIABLE) {
            return;
        }

        if ($tokens[$prev]['code'] === T_VAR) {
            $error = 'The var keyword must not be used to declare a property';
            $phpcsFile->addError($error, $stackPtr, 'VarUsed');
        }
    }

    /**
     * Check that `get` comes before `set` in a list of operations with no bodies, e.g. `{ get; set; }`.
     *
     * PER Coding Style 3.0, section 4.10, requires this order for an abstract or interface property. A list where any
     * hook has a body isn't checked, as PER doesn't set an order for those.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the property token.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     */
    private function checkHookOrder(File $phpcsFile, int $stackPtr, array $tokens): void
    {
        $opener = PropertyHookHelper::findHookListOpener($phpcsFile, $stackPtr);
        $closer = $opener === false ? null : ($tokens[$opener]['bracket_closer'] ?? null);
        if (!is_int($opener) || !is_int($closer)) {
            return;
        }

        // Operation name => [position, content as written].
        $operations = [];
        $simple = true;
        for ($i = ($opener + 1); $i < $closer; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_WHITESPACE || $code === T_SEMICOLON) {
                continue;
            }

            $content = $tokens[$i]['content'];
            assert(is_string($content));
            $name = strtolower($content);
            if ($code === T_STRING && ($name === 'get' || $name === 'set')) {
                // A hook with a body (`{`, `=>` or a parameter list) means PER sets no order.
                $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), $closer, true);
                if ($next === false || $tokens[$next]['code'] !== T_SEMICOLON) {
                    return;
                }

                $operations[$name] = [$i, $content];
                continue;
            }

            // Anything else, such as & or an attribute, isn't safe to move in a fix.
            $simple = false;
        }

        if (!isset($operations['get'], $operations['set']) || $operations['get'][0] < $operations['set'][0]) {
            return;
        }

        [$getPtr, $getContent] = $operations['get'];
        [$setPtr, $setContent] = $operations['set'];
        $error = 'The get operation must be listed before the set operation';
        if (!$simple) {
            $phpcsFile->addError($error, $setPtr, 'HookOrder');
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $setPtr, 'HookOrder');
        if ($fix) {
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($setPtr, $getContent);
            $phpcsFile->fixer->replaceToken($getPtr, $setContent);
            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * Check that visibility is declared.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the property token.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     * @param array<string, mixed> $propertyInfo The property info from getMemberProperties().
     */
    private function checkVisibility(File $phpcsFile, int $stackPtr, array $tokens, array $propertyInfo): void
    {
        if ($propertyInfo['scope_specified'] === false && $propertyInfo['set_scope'] === false) {
            $error = 'Visibility must be declared on property "%s"';
            $data = [$tokens[$stackPtr]['content']];
            $phpcsFile->addError($error, $stackPtr, 'ScopeMissing', $data);
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

        // A promoted constructor parameter is a property, so it gets the underscore check.
        if (
            isset($tokens[$stackPtr]['nested_parenthesis'])
            && PropertyHookHelper::isPropertyVariable($phpcsFile, $stackPtr)
        ) {
            $this->checkUnderscore($phpcsFile, $stackPtr, $tokens);
        }
    }

    /**
     * Processes variables in double quoted strings.
     *
     * @param File $phpcsFile The file where this token was found.
     * @param int $stackPtr The position where the token was found.
     * @return void
     */
    protected function processVariableInString(File $phpcsFile, int $stackPtr): void
    {
        // We don't care about normal variables.
    }
}
