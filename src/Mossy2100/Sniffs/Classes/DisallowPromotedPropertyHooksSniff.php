<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\Classes;

use Mossy2100\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Disallows property hooks on promoted constructor parameters.
 *
 * PHP 8.4 allows a promoted constructor parameter to have hooks, e.g. `public string $name { set => trim($value); }`.
 * PER Coding Style 3.0 allows a single short hook on one line. This standard disallows them all, because hook bodies
 * inside a constructor signature are hard to read. Declare the property in the class body instead.
 *
 * Inherited sniffs also misread them. SlevomatCodingStandard.TypeHints.ParameterTypeHint and ParameterTypeHintSpacing
 * treat a variable in the hook body, such as `$value` or `$this`, as another constructor parameter, and report errors
 * that phpcbf can't fix. A multi-line hook also gets Squiz.Functions.MultiLineFunctionDeclaration.Indent.
 *
 * Not fixable, because moving the property and its hooks into the class body is a bigger rewrite than a fixer should
 * risk.
 */
class DisallowPromotedPropertyHooksSniff implements Sniff
{
    public const string CODE_PROMOTED_PROPERTY_HOOK = 'PromotedPropertyHook';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_VARIABLE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack passed in $tokens.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // Only a variable inside parentheses can be a promoted parameter. Check this first, as it's cheap.
        if (!isset($tokens[$stackPtr]['nested_parenthesis'])) {
            return;
        }

        if (
            !PropertyHookHelper::isPropertyVariable($phpcsFile, $stackPtr)
            || PropertyHookHelper::findHookListOpener($phpcsFile, $stackPtr) === false
        ) {
            return;
        }

        $phpcsFile->addError(
            'Property hooks are not allowed on promoted constructor parameters; declare the property "%s" in the '
                . 'class body instead.',
            $stackPtr,
            self::CODE_PROMOTED_PROPERTY_HOOK,
            [$tokens[$stackPtr]['content']]
        );
    }
}
