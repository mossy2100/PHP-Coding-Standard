<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PhpCollective\Sniffs\ControlStructures\ControlStructureEmptyStatementSniff as PhpCollectiveSniff;

/**
 * Requires braces on a control structure with no body, as in `while (next($items)) {}`, not `while (next($items));`.
 *
 * PER Coding Style 3.1, section 5, says the body of each structure MUST be enclosed by braces. It makes no exception
 * for a structure with no body.
 *
 * PhpCollective.ControlStructures.ControlStructureEmptyStatement checks `if`, `elseif`, `for`, `foreach` and `while`.
 * It finds the `;` after a structure's closing parenthesis, so it can't see `else;`, which has no parentheses, and no
 * property changes that. This subclass adds `else`, and leaves everything else to the parent. The fix writes `else {}`.
 */
class ControlStructureEmptyStatementSniff extends PhpCollectiveSniff
{
    public const string CODE_EMPTY_STATEMENT = 'EmptyStatement';

    /**
     * Returns an array of tokens this test wants to listen for: the parent's, and `else`.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [...parent::register(), T_ELSE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    // phpcs:ignore Jumbo.TypeHints.ParameterTypeHint.MissingNativeTypeHint -- The parent has no type, so int is a fatal.
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] !== T_ELSE) {
            parent::process($phpcsFile, $stackPtr);

            return;
        }

        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if ($next === false || $tokens[$next]['code'] !== T_SEMICOLON) {
            return;
        }

        // A comment between `else` and the `;` would be lost, so that's left to a person.
        $error = 'Empty else statement detected: semicolon found after else. Use "else {}"';
        $isFixable = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($stackPtr + 1), $next) === false;
        if (!FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $next, self::CODE_EMPTY_STATEMENT)) {
            return;
        }

        FixerHelper::replaceBetween($phpcsFile, $stackPtr, ($next + 1), ' {}');
    }
}
