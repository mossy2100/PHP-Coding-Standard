<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\NamingConventions;

use PERCS31\Helpers\NamingHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires enum case names to be in PascalCase.
 *
 * PER Coding Style 3.1, section 9, says enum case declarations MUST use PascalCase, and section 2.1 says the first
 * letter of every word is capitalized. If a word could be one letter, any name in capitals would qualify, so a run of
 * capitals isn't PascalCase, unless it's an acronym approved in PERCS31.NamingConventions.AcronymCasing. So `Hearts`,
 * `C2`, `B2B` and a lone `A` pass, and `FOO`, `XMLParser` and `AClass` don't.
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
        if (NamingHelper::isPascalCaseInFile($phpcsFile, $name)) {
            return;
        }

        $phpcsFile->addError(
            'Enum case "%s" must be in PascalCase',
            $namePtr,
            self::CODE_NOT_PASCAL_CASE,
            [$name],
        );
    }
}
