<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires enum case names to be in PascalCase.
 *
 * PER Coding Style 3.0, section 9, says enum case declarations MUST use PascalCase. A valid name starts with an upper
 * case letter, has only letters and digits, and isn't all upper case, apart from a single letter. A run of capitals
 * inside a name, such as `HTTPStatus`, is allowed, as PER section 2.7 only recommends treating acronyms as words.
 *
 * Based on PhpCollective.Classes.EnumCaseCasing (php-collective/code-sniffer), which skips a name with no capitals,
 * such as `hearts`. This sniff doesn't fix the name, as references to the case elsewhere wouldn't change with it.
 */
class ValidEnumCaseNameSniff implements Sniff
{
    public const string CODE_NOT_PASCAL_CASE = 'NotPascalCase';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_ENUM_CASE];
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
        $namePtr = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if ($namePtr === false || $tokens[$namePtr]['code'] !== T_STRING) {
            return;
        }

        $name = $tokens[$namePtr]['content'];
        assert(is_string($name));
        if ($this->isPascalCase($name)) {
            return;
        }

        $phpcsFile->addError(
            'Enum case "%s" must be in PascalCase',
            $namePtr,
            self::CODE_NOT_PASCAL_CASE,
            [$name],
        );
    }

    /**
     * Check whether a name is in PascalCase.
     *
     * @param string $name The enum case name.
     * @return bool True if it starts with a capital, has only letters and digits, and isn't all capitals.
     */
    private function isPascalCase(string $name): bool
    {
        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $name) !== 1) {
            return false;
        }

        return strlen($name) === 1 || preg_match('/[a-z]/', $name) === 1;
    }
}
