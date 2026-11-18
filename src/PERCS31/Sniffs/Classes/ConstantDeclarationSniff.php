<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Utils\Constants;
use PHPCSUtils\Utils\Scopes;
use SlevomatCodingStandard\Sniffs\Classes\DisallowMultiConstantDefinitionSniff;

/**
 * Requires one constant per class constant statement, a single space after its `const` keyword and between its type
 * and its name, and a single space after an enum case's `case` keyword.
 *
 * Section 4.3 says "There MUST NOT be more than one property or constant declared per statement". That's checked by
 * SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition, called from here, so its fix splits the statement and
 * keeps its modifiers and type. The Slevomat sniff checks every `const` statement, and has no setting to skip a global
 * one, which section 4 doesn't cover, as it's about classes. So it isn't in the ruleset, and this sniff only gives it a
 * class's constants, reported as DisallowedMultiConstantDefinition. A `define()` call can only declare one constant.
 *
 * PER Coding Style 3.1, section 4.6, says all keywords MUST be on a single line, separated by a single space, and
 * section 4.3 says there MUST be a space between a property's type and its name. Neither names a constant's type or
 * name, but a constant is declared like a property, so `public const  int  A = 1;` is read as breaking both.
 * Squiz.WhiteSpace.ScopeKeywordSpacing checks the space after a visibility or `final`, but nothing checks the space
 * after `const` or after the type. SlevomatCodingStandard.Classes.PropertyDeclaration makes these checks for a
 * property only, and SlevomatCodingStandard.Whitespaces.DuplicateSpaces reports every run of spaces, which PER allows
 * around operators. No sniff in PHP_CodeSniffer, PHPCSExtra, Slevomat or PhpCollective checks a constant alone.
 *
 * An enum case is declared like a constant, and section 9 says enums follow the guidelines for class constants. Its
 * example writes `case Hearts = 'H';`, so one space after `case` is read as the pattern, reported as SpaceAfterCase.
 *
 * A line break counts as more than a single space. A global constant isn't checked, as section 4 is about classes. Set
 * checkGlobalConstants to check it for one constant per statement and one space after `const`, as Jumbo does.
 */
class ConstantDeclarationSniff implements Sniff
{
    public const string CODE_SPACE_AFTER_CONST = 'SpaceAfterConst';

    public const string CODE_SPACE_AFTER_TYPE = 'SpaceAfterType';

    public const string CODE_SPACE_AFTER_CASE = 'SpaceAfterCase';

    public const string CODE_MULTI_CONSTANT = 'DisallowedMultiConstantDefinition';

    /**
     * Whether to check a global `const` statement too: one constant per statement, and one space after `const`. Section
     * 4 is about classes, so it's off by default. The Jumbo standard turns it on.
     */
    public bool $checkGlobalConstants = false;

    /**
     * The Slevomat sniff that makes the check for one constant per statement, and its fix.
     */
    private ?DisallowMultiConstantDefinitionSniff $multiConstantSniff = null;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_CONST, T_ENUM_CASE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $first = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($phpcsFile->getTokens()[$stackPtr]['code'] === T_ENUM_CASE) {
            if ($first !== false) {
                FixerHelper::requireSingleSpace(
                    $phpcsFile,
                    $stackPtr,
                    $first,
                    'Expected 1 space after the case keyword',
                    self::CODE_SPACE_AFTER_CASE,
                );
            }

            return;
        }

        $isClassConstant = Scopes::isOOConstant($phpcsFile, $stackPtr);
        if (!$isClassConstant && (!$this->checkGlobalConstants || $this->isInUseStatement($phpcsFile, $stackPtr))) {
            return;
        }

        $this->multiConstantSniff ??= new DisallowMultiConstantDefinitionSniff();
        $this->multiConstantSniff->process($phpcsFile, $stackPtr);

        if ($first !== false) {
            FixerHelper::requireSingleSpace(
                $phpcsFile,
                $stackPtr,
                $first,
                'Expected 1 space after the const keyword',
                self::CODE_SPACE_AFTER_CONST,
            );
        }

        // A global constant can't have a type.
        if (!$isClassConstant) {
            return;
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

    /**
     * Check whether a `const` keyword is part of an import, as in `use const Vendor\LIMIT;`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the `const` keyword.
     * @return bool True if the statement it's in starts with `use`.
     */
    private function isInUseStatement(File $phpcsFile, int $stackPtr): bool
    {
        $start = $phpcsFile->findStartOfStatement($stackPtr);

        return $phpcsFile->getTokens()[$start]['code'] === T_USE;
    }
}
