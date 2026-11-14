<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a single space after a visibility or `final` in a trait's `as` clause, as in `sayBye as private myBye;`, and
 * no space around the `::` in a trait `use` block, as in `A::smallTalk insteadof B;`.
 *
 * PER Coding Style 3.1, section 4.2, says `insteadof` and `as` MUST be used as its example shows, "taking note of
 * indentation, spacing, and new lines". PSR12.Traits.UseDeclaration checks the space each side of `as`, but not the
 * space after a modifier that follows it. Squiz.WhiteSpace.ScopeKeywordSpacing checks the space after a visibility
 * everywhere else, but returns early for one after `as`, with no property to change that. Neither checks the `::`
 * between a trait and its method, which the example writes with no space.
 *
 * It's a sniff of its own, not a subclass of either. The Squiz sniff's skip is in the middle of its process(), and a
 * subclass of the PSR-12 sniff would rename every one of its codes for the sake of one new check.
 */
class TraitAliasSpacingSniff implements Sniff
{
    public const string CODE_SPACE_AFTER_MODIFIER = 'SpaceAfterModifier';

    public const string CODE_SPACE_AROUND_DOUBLE_COLON = 'SpaceAroundDoubleColon';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_AS, T_DOUBLE_COLON];
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
        if ($tokens[$stackPtr]['code'] === T_DOUBLE_COLON) {
            $this->processDoubleColon($phpcsFile, $stackPtr);

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
     * Report white space on either side of a `::` in a trait `use` block, and fix it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the `::`.
     */
    private function processDoubleColon(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // Only a `::` straight inside the braces of a trait `use` is checked. Any other is left undefined by PER.
        $conditions = $tokens[$stackPtr]['conditions'];
        if ($conditions === [] || end($conditions) !== T_USE) {
            return;
        }

        $before = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        $after = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        $error = 'Expected no space %s "::" in a trait use block';
        $code = self::CODE_SPACE_AROUND_DOUBLE_COLON;

        if (
            $before !== false
            && $before !== ($stackPtr - 1)
            && $phpcsFile->addFixableError($error, $stackPtr, $code, ['before'])
        ) {
            FixerHelper::replaceBetween($phpcsFile, $before, $stackPtr, '');
        }

        if (
            $after !== false
            && $after !== ($stackPtr + 1)
            && $phpcsFile->addFixableError($error, $stackPtr, $code, ['after'])
        ) {
            FixerHelper::replaceBetween($phpcsFile, $stackPtr, $after, '');
        }
    }
}
