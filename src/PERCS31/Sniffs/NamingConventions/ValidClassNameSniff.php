<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\NamingConventions;

use PERCS31\Helpers\NamingHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Classes\ValidClassNameSniff as SquizSniff;
use PHP_CodeSniffer\Util\Common;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires class, interface, trait and enum names to be in PascalCase.
 *
 * PSR-1 says class names MUST be in StudlyCaps, which PER Coding Style 3.1, section 2.1, reads as PascalCase, with the
 * first letter of every word capitalized. Section 4 says the term "class" refers to all classes, interfaces, traits and
 * enums. If a word could be one letter, any name in capitals would qualify, so a run of capitals isn't PascalCase, even
 * if it's an acronym.
 *
 * It extends Squiz.Classes.ValidClassName, which allows a run of capitals, and also reports one, such as `HTTPClient`,
 * `AClass` or `FOO`.
 */
class ValidClassNameSniff extends SquizSniff
{
    public const string CODE_NOT_PASCAL_CASE = 'NotPascalCase';

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        parent::process($phpcsFile, $stackPtr);

        $tokens = $phpcsFile->getTokens();
        $namePtr = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if ($namePtr === false || $tokens[$namePtr]['code'] !== T_STRING) {
            return;
        }

        // The parent reports any other problem with the name, so only report a run of capitals here.
        $name = $tokens[$namePtr]['content'];
        if (!Common::isCamelCaps($name, true, true, false) || NamingHelper::isPascalCase($name)) {
            return;
        }

        $phpcsFile->addError(
            '%s name "%s" is not in PascalCase format',
            $namePtr,
            self::CODE_NOT_PASCAL_CASE,
            [ucfirst($tokens[$stackPtr]['content']), $name],
        );
    }
}
