<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires a non-public method or constant in an enum to be `private`, not `protected`.
 *
 * PER Coding Style 3.1, section 9, says non-public methods and constants in an enum MUST use `private` instead of
 * `protected`, as enums don't support inheritance. The fix swaps the keyword, which doesn't change behaviour, since
 * nothing can extend an enum. It also removes `final` if present, as PHP warns that a private method can't be final,
 * and rejects a final private constant.
 */
class EnumMemberVisibilitySniff implements Sniff
{
    public const string CODE_PROTECTED = 'Protected';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_FUNCTION, T_CONST];
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
        $conditions = $tokens[$stackPtr]['conditions'];
        if ($conditions === [] || end($conditions) !== T_ENUM) {
            return;
        }

        // The keyword is among the modifiers, after the previous member or the enum's opening brace.
        $start = $phpcsFile->findPrevious(
            [T_SEMICOLON, T_OPEN_CURLY_BRACKET, T_CLOSE_CURLY_BRACKET, T_ATTRIBUTE_END],
            ($stackPtr - 1),
        );
        $protectedPtr = $phpcsFile->findPrevious(T_PROTECTED, ($stackPtr - 1), ($start === false ? null : $start));
        if ($protectedPtr === false) {
            return;
        }

        $member = $tokens[$stackPtr]['code'] === T_CONST ? 'constant' : 'method';
        $fix = $phpcsFile->addFixableError(
            'A non-public %s in an enum must be private, not protected',
            $protectedPtr,
            self::CODE_PROTECTED,
            [$member],
        );
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($protectedPtr, 'private');
        $finalPtr = $phpcsFile->findPrevious(T_FINAL, ($stackPtr - 1), ($start === false ? null : $start));
        if ($finalPtr !== false) {
            $phpcsFile->fixer->replaceToken($finalPtr, '');
            if ($tokens[($finalPtr + 1)]['code'] === T_WHITESPACE) {
                $phpcsFile->fixer->replaceToken(($finalPtr + 1), '');
            }
        }

        $phpcsFile->fixer->endChangeset();
    }
}
