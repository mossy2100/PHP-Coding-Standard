<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks the `{ ... }` block of a trait `use`, where its conflicts are resolved: a single space after a visibility or
 * `final` in an `as` clause, as in `sayBye as private myBye;`, and each statement and the closing brace on a line of
 * its own.
 *
 * PER Coding Style 3.1, section 4.2, says `insteadof` and `as` MUST be used as its example shows, "taking note of
 * indentation, spacing, and new lines". PSR12.Traits.UseDeclaration checks the space each side of `as`, but not the
 * space after a modifier that follows it. Squiz.WhiteSpace.ScopeKeywordSpacing checks the space after a visibility
 * everywhere else, but returns early for one after `as`, with no property to change that.
 *
 * It's a sniff of its own, not a subclass of either. The Squiz sniff's skip is in the middle of its process(), and a
 * subclass of the PSR-12 sniff would rename every one of its codes for the sake of one new check.
 *
 * Section 4.2's example puts each statement of a conflict block on a line of its own, between the braces. So a
 * statement on the opening brace's line is reported, as StatementOnOpenBraceLine, and a closing brace on a statement's
 * line, as CloseBraceNotOnOwnLine. A blank line in the block isn't forbidden, as section 2.3 allows one wherever
 * nothing forbids it, and an example can't set a ban on one. So it's reported separately, as BlankLine, which PERCS31
 * excludes and Jumbo turns on. These replace PSR12.Traits.UseDeclaration's SpaceAfterOpeningBrace and
 * SpaceBeforeClosingBrace codes, which report a blank line along with a statement on a brace's line.
 */
class TraitUseBlockSniff implements Sniff
{
    public const string CODE_SPACE_AFTER_MODIFIER = 'SpaceAfterModifier';

    public const string CODE_STATEMENT_ON_OPEN_BRACE_LINE = 'StatementOnOpenBraceLine';

    public const string CODE_CLOSE_BRACE_NOT_ON_OWN_LINE = 'CloseBraceNotOnOwnLine';

    public const string CODE_BLANK_LINE = 'BlankLine';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_AS, T_USE];
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
        if ($tokens[$stackPtr]['code'] === T_USE) {
            $this->checkConflictBlock($phpcsFile, $stackPtr);
            return;
        }

        $modifiers = Tokens::SCOPE_MODIFIERS + [T_FINAL => T_FINAL];

        // Only a trait's `as` can be followed by a modifier. A `foreach`'s or an import's can't.
        $modifier = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if ($modifier === false || !isset($modifiers[$tokens[$modifier]['code']])) {
            return;
        }

        // A modifier with no alias, as in `sayHello as protected;`, has nothing after it to space.
        $alias = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($modifier + 1), null, true);
        if ($alias === false || $tokens[$alias]['code'] === T_SEMICOLON) {
            return;
        }

        FixerHelper::requireSingleSpace(
            $phpcsFile,
            $modifier,
            $alias,
            'Expected 1 space after "%s" in a trait\'s as clause',
            self::CODE_SPACE_AFTER_MODIFIER,
            data: [strtolower($tokens[$modifier]['content'])],
        );
    }

    /**
     * Check the lines of a trait's conflict block: no statement on the opening brace's line, the closing brace on a
     * line of its own, and no blank line in it. A `use` with no block, such as an import or a closure's, is skipped.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $use The position of the `use` keyword.
     */
    private function checkConflictBlock(File $phpcsFile, int $use): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$use]['scope_opener'] ?? null;
        $closer = $tokens[$use]['scope_closer'] ?? null;
        if (!is_int($opener) || !is_int($closer) || $tokens[$opener]['code'] !== T_OPEN_CURLY_BRACKET) {
            return;
        }

        $indent = LayoutHelper::lineIndent($phpcsFile, $use);
        $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($opener + 1), $closer, true);
        if ($first !== false && $tokens[$first]['line'] === $tokens[$opener]['line']) {
            $error = 'A trait conflict resolution statement must not be on the opening brace\'s line';
            $isFixable = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($opener + 1), $first) === false;
            $code = self::CODE_STATEMENT_ON_OPEN_BRACE_LINE;
            if (FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $first, $code)) {
                FixerHelper::breakLineBefore($phpcsFile, $first, ($indent + LayoutHelper::INDENT));
            }
        }

        $last = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($closer - 1), $opener, true);
        if ($last !== false && $last !== $opener && $tokens[$last]['line'] === $tokens[$closer]['line']) {
            $error = 'The closing brace of a trait conflict block must be on a line of its own';
            $isFixable = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($last + 1), $closer) === false;
            $code = self::CODE_CLOSE_BRACE_NOT_ON_OWN_LINE;
            if (FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $closer, $code)) {
                FixerHelper::breakLineBefore($phpcsFile, $closer, $indent);
            }
        }

        $error = 'There must be no blank line in a trait conflict block';
        for ($i = ($opener + 1); $i < $closer; $i++) {
            if (!LayoutHelper::isBlankLine($phpcsFile, $i)) {
                continue;
            }

            if ($phpcsFile->addFixableError($error, $i, self::CODE_BLANK_LINE)) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }
    }
}
