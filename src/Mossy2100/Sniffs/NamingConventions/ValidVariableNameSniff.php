<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\NamingConventions;

use Mossy2100\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractVariableSniff;
use PHP_CodeSniffer\Util\Common;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Warns about a variable or parameter whose name starts with an underscore, inside a class, enum, trait or interface.
 *
 * Squiz.NamingConventions.ValidVariableName reports a leading underscore as an error outside these scopes. Inside them,
 * it strips the underscore before checking camel caps, because the Squiz convention prefixes private members with one.
 * This sniff fills that gap, and only that gap:
 *
 * - Outside a class, Squiz already reports it.
 * - If the rest of the name isn't camel caps either, Squiz already reports it.
 * - Properties, including promoted constructor parameters, are left to Mossy2100.Classes.PropertyDeclaration.
 * - Static property access (`self::$_x`) is a property, not a variable.
 */
class ValidVariableNameSniff extends AbstractVariableSniff
{
    public const string CODE_UNDERSCORE = 'Underscore';

    /**
     * Processes normal variables.
     *
     * @param File $phpcsFile The file where this token was found.
     * @param int $stackPtr The position where the token was found.
     * @return void
     */
    protected function processVariable(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $content = $tokens[$stackPtr]['content'];
        assert(is_string($content));
        $varName = ltrim($content, '$');

        if (!str_starts_with($varName, '_') || isset(static::PHP_RESERVED_VARS[$varName])) {
            return;
        }

        // Squiz reports a leading underscore outside a class, and any name whose remainder isn't camel caps.
        if (
            !$phpcsFile->hasCondition($stackPtr, Tokens::OO_SCOPE_TOKENS)
            || !Common::isCamelCaps(substr($varName, 1), false, true, false)
        ) {
            return;
        }

        // A static property access, such as self::$_x.
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($prev !== false && $tokens[$prev]['code'] === T_DOUBLE_COLON) {
            return;
        }

        // A promoted constructor parameter is a property.
        if (
            isset($tokens[$stackPtr]['nested_parenthesis'])
            && PropertyHookHelper::isPropertyVariable($phpcsFile, $stackPtr)
        ) {
            return;
        }

        $phpcsFile->addWarning(
            'Variable "%s" should not be prefixed with an underscore',
            $stackPtr,
            self::CODE_UNDERSCORE,
            [$content]
        );
    }

    /**
     * Processes class member variables.
     *
     * @param File $phpcsFile The file where this token was found.
     * @param int $stackPtr The position where the token was found.
     * @return void
     */
    protected function processMemberVar(File $phpcsFile, int $stackPtr): void
    {
        // Properties are checked by Mossy2100.Classes.PropertyDeclaration.
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
        // Squiz checks these without stripping the underscore.
    }
}
