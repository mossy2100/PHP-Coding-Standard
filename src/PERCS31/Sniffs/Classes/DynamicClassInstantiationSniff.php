<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires an argument list after `new (expression)`, as in `new (className())()`.
 *
 * PER Coding Style 3.1, section 4, says that when instantiating a new class, parentheses MUST always be present, even
 * when there are no arguments passed to the constructor. PSR12.Classes.ClassInstantiation checks that, but it stops at
 * the first `(` after `new`, and takes it for the argument list. In `new (className())`, the parentheses hold the
 * expression that gives the class, and there's no argument list, so it passes.
 *
 * This sniff only looks at a `new` followed by `(`, and leaves every other form to the PSR-12 sniff. It's a sniff of
 * its own, not a subclass, so the PSR-12 sniff stays as it is.
 */
class DynamicClassInstantiationSniff implements Sniff
{
    public const string CODE_MISSING_PARENTHESES = 'MissingParentheses';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_NEW];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if ($opener === false || $tokens[$opener]['code'] !== T_OPEN_PARENTHESIS) {
            return;
        }

        // The parentheses hold the class, and the argument list follows them.
        $closer = $tokens[$opener]['parenthesis_closer'] ?? null;
        if (!is_int($closer)) {
            return;
        }

        $after = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closer + 1), null, true);
        if ($after !== false && $tokens[$after]['code'] === T_OPEN_PARENTHESIS) {
            return;
        }

        $error = 'Parentheses must be used when instantiating a new class';
        if ($phpcsFile->addFixableError($error, $stackPtr, self::CODE_MISSING_PARENTHESES)) {
            $phpcsFile->fixer->addContent($closer, '()');
        }
    }
}
