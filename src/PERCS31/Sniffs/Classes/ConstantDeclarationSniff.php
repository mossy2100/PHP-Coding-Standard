<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Utils\Constants;
use PHPCSUtils\Utils\Scopes;

/**
 * Requires a single space after a class constant's `const` keyword, and between its type and its name.
 *
 * PER Coding Style 3.1, section 4.6, says all keywords MUST be on a single line, separated by a single space, and
 * section 4.3 says there MUST be a space between a property's type and its name. Neither names a constant's type or
 * name, but a constant is declared like a property, so `public const  int  A = 1;` is read as breaking both.
 * Squiz.WhiteSpace.ScopeKeywordSpacing checks the space after a visibility or `final`, but nothing checks the space
 * after `const` or after the type. SlevomatCodingStandard.Classes.PropertyDeclaration makes these checks for a
 * property only, and SlevomatCodingStandard.Whitespaces.DuplicateSpaces reports every run of spaces, which PER allows
 * around operators. No sniff in PHP_CodeSniffer, PHPCSExtra, Slevomat or PhpCollective checks a constant alone.
 *
 * A line break counts as more than a single space. A global constant isn't checked, as section 4 is about classes.
 */
class ConstantDeclarationSniff implements Sniff
{
    public const string CODE_SPACE_AFTER_CONST = 'SpaceAfterConst';

    public const string CODE_SPACE_AFTER_TYPE = 'SpaceAfterType';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_CONST];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        if (!Scopes::isOOConstant($phpcsFile, $stackPtr)) {
            return;
        }

        $first = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($first !== false) {
            FixerHelper::requireSingleSpace(
                $phpcsFile,
                $stackPtr,
                $first,
                'Expected 1 space after the const keyword',
                self::CODE_SPACE_AFTER_CONST,
            );
        }

        $constant = Constants::getProperties($phpcsFile, $stackPtr);
        $typeEnd = $constant['type_end_token'];
        $name = $constant['name_token'];
        if (is_int($typeEnd) && is_int($name)) {
            FixerHelper::requireSingleSpace(
                $phpcsFile,
                $typeEnd,
                $name,
                'Expected 1 space between a constant\'s type and its name',
                self::CODE_SPACE_AFTER_TYPE,
            );
        }
    }
}
