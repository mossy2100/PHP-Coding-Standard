<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Allows a property hook on a promoted constructor parameter only if it's a single short hook on one line.
 *
 * PER Coding Style 3.1, section 4.9, says hooks on a constructor-promoted property MUST be only a single hook, with a
 * short-syntax body, defined on a single line, e.g. `public string $name { set => ucfirst($value); }`. Otherwise the
 * promoted property MUST NOT have any hooks, and the property should be declared in the class body.
 *
 * Not fixable, because moving the property and its hooks into the class body is a bigger rewrite than a fixer should
 * risk.
 */
class PromotedPropertyHooksSniff implements Sniff
{
    public const string CODE_TOO_COMPLEX = 'TooComplex';

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

        if (!PropertyHookHelper::isPropertyVariable($phpcsFile, $stackPtr)) {
            return;
        }

        $opener = PropertyHookHelper::findHookListOpener($phpcsFile, $stackPtr);
        $closer = $opener === false ? null : ($tokens[$opener]['bracket_closer'] ?? null);
        if ($opener === false || !is_int($closer)) {
            return;
        }

        if ($this->isSingleShortHookOnOneLine($phpcsFile, $opener, $closer)) {
            return;
        }

        $phpcsFile->addError(
            'A hook on a promoted constructor parameter must be a single hook with a short body, on one line; '
                . 'declare the property "%s" in the class body instead.',
            $stackPtr,
            self::CODE_TOO_COMPLEX,
            [$tokens[$stackPtr]['content']],
        );
    }

    /**
     * Check whether a hook list holds a single hook with a `=>` body, all on one line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the hook list's opening brace.
     * @param int $closer The position of the hook list's closing brace.
     * @return bool True if the hook list is that simple.
     */
    private function isSingleShortHookOnOneLine(File $phpcsFile, int $opener, int $closer): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$opener]['line'] !== $tokens[$closer]['line']) {
            return false;
        }

        // Count the hooks: each one ends with a `;` after its `=>`, or with a block body.
        $hooks = 0;
        $afterArrow = false;
        for ($i = ($opener + 1); $i < $closer; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_DOUBLE_ARROW) {
                $afterArrow = true;
            } elseif ($code === T_SEMICOLON) {
                $hooks++;
                $afterArrow = false;
            } elseif ($code === T_OPEN_CURLY_BRACKET && !$afterArrow) {
                return false;
            }

            // Skip over parameter lists, attributes, and brackets inside an expression.
            $jumpTo = $tokens[$i]['parenthesis_closer'] ?? $tokens[$i]['attribute_closer']
                ?? $tokens[$i]['bracket_closer'] ?? null;
            if (is_int($jumpTo) && $jumpTo > $i && $jumpTo < $closer) {
                $i = $jumpTo;
            }
        }

        return $hooks === 1;
    }
}
