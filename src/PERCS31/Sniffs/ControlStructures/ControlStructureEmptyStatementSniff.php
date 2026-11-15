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
 *
 * It also works around a bug in the parent (php-collective/code-sniffer 0.6.10). The parent only knows the `while` that
 * ends a `do` loop by the closing brace before it. A `do` loop with no braces, as in `do $i++; while ($i < 10);`, has
 * none, so the parent reports that `while`, and its fix replaces the `;` with `{}`, a parse error. This subclass skips
 * that `while`.
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
            if ($tokens[$stackPtr]['code'] !== T_WHILE || !$this->endsDoWithoutBraces($phpcsFile, $stackPtr)) {
                parent::process($phpcsFile, $stackPtr);
            }

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

    /**
     * Check if a `while` ends a `do` loop that has no braces, as in `do $i++; while ($i < 10);`.
     *
     * It looks back through the code at the same level for a `do` with no braces that no earlier `while` has ended. The
     * loop's body may itself be a structure, as in `do if ($a) { run(); } while ($b);`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $whilePtr The position of the `while`.
     * @return bool True if the `while` ends a `do` loop with no braces.
     */
    private function endsDoWithoutBraces(File $phpcsFile, int $whilePtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        // Straight after the closing brace of a `do` loop, it ends that loop.
        $prev = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($whilePtr - 1), null, true);
        $owner = $prev === false ? null : ($tokens[$prev]['scope_condition'] ?? null);
        if ($owner !== null && $tokens[$owner]['code'] === T_DO) {
            return false;
        }

        $parentheses = $tokens[$whilePtr]['nested_parenthesis'] ?? [];

        // The number of `while` tokens passed that end a `do` with no braces, each of which pairs with a `do` further
        // back.
        $pending = 0;
        for ($i = ($whilePtr - 1); $i > 0; $i--) {
            $code = $tokens[$i]['code'];

            // Skip a block at this level.
            if ($code === T_CLOSE_CURLY_BRACKET && isset($tokens[$i]['bracket_opener'])) {
                $i = $tokens[$i]['bracket_opener'];
                continue;
            }

            // Stop at the start of the block the `while` is in.
            if ($code === T_OPEN_CURLY_BRACKET) {
                return false;
            }

            if (($tokens[$i]['nested_parenthesis'] ?? []) !== $parentheses) {
                continue;
            }

            if ($code === T_WHILE && $this->endsDoWithoutBraces($phpcsFile, $i)) {
                $pending++;
                continue;
            }

            if ($code !== T_DO || isset($tokens[$i]['scope_opener'])) {
                continue;
            }

            if ($pending === 0) {
                return true;
            }

            $pending--;
        }

        return false;
    }
}
