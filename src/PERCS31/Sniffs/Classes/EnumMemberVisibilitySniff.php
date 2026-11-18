<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Utils\Constants;
use PHPCSUtils\Utils\Scopes;

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
        if (Scopes::validDirectScope($phpcsFile, $stackPtr, T_ENUM) === false) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        [$protectedPtr, $finalPtr] = $tokens[$stackPtr]['code'] === T_CONST
            ? $this->findConstantModifiers($phpcsFile, $stackPtr)
            : $this->findMethodModifiers($phpcsFile, $stackPtr);
        if ($protectedPtr === null) {
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
        if ($finalPtr !== null) {
            $phpcsFile->fixer->replaceToken($finalPtr, '');
            if ($tokens[($finalPtr + 1)]['code'] === T_WHITESPACE) {
                $phpcsFile->fixer->replaceToken(($finalPtr + 1), '');
            }
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Find a constant's `protected` and `final` keywords, with PHPCSUtils.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $constPtr The position of `const`.
     * @return array{int|null, int|null} The positions of `protected` and `final`, each null if it isn't there.
     */
    private function findConstantModifiers(File $phpcsFile, int $constPtr): array
    {
        $constant = Constants::getProperties($phpcsFile, $constPtr);
        $scopePtr = $constant['scope_token'];
        $finalPtr = $constant['final_token'];

        return [
            $constant['scope'] === 'protected' && is_int($scopePtr) ? $scopePtr : null,
            is_int($finalPtr) ? $finalPtr : null,
        ];
    }

    /**
     * Find a method's `protected` and `final` keywords. They're among its modifiers, after the previous member or the
     * enum's opening brace. PHPCSUtils gives a method's visibility, but not where its keyword is.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $functionPtr The position of `function`.
     * @return array{int|null, int|null} The positions of `protected` and `final`, each null if it isn't there.
     */
    private function findMethodModifiers(File $phpcsFile, int $functionPtr): array
    {
        $found = $phpcsFile->findPrevious(
            [T_SEMICOLON, T_OPEN_CURLY_BRACKET, T_CLOSE_CURLY_BRACKET, T_ATTRIBUTE_END],
            ($functionPtr - 1),
        );
        $start = $found === false ? null : $found;
        $protectedPtr = $phpcsFile->findPrevious(T_PROTECTED, ($functionPtr - 1), $start);
        $finalPtr = $phpcsFile->findPrevious(T_FINAL, ($functionPtr - 1), $start);

        return [$protectedPtr === false ? null : $protectedPtr, $finalPtr === false ? null : $finalPtr];
    }
}
