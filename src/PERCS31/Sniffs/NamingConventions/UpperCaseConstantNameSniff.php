<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\NamingConventions;

use PERCS31\Helpers\NamingHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions\UpperCaseConstantNameSniff as GenericSniff;
use PHPCSUtils\Utils\Scopes;

/**
 * Requires class constant names in UPPER_CASE, except in an enum, where PascalCase is also allowed, and recommended.
 *
 * Generic.NamingConventions.UpperCaseConstantName, from PSR-1, requires every constant name in UPPER_CASE, but PSR-1
 * only requires it of class constants. So this sniff changes two things:
 * - A global constant, from `const` or `define()`, isn't checked by default. Set checkGlobalConstants to check it, and
 *   any alias of a constant. The parent reports a global `const` as a class constant, with the
 *   ClassConstantNotUpperCase code, and this sniff reports it as `define()` is reported, with ConstantNotUpperCase.
 * - PER Coding Style 3.1, section 9, says constants in an enum MAY use either PascalCase or UPPER_CASE, and PascalCase
 *   is RECOMMENDED. So for an enum constant, this sniff allows PascalCase, reports UPPER_CASE with a warning, and
 *   anything else, such as camelCase or `HTTPStatus`, with an error, as a run of capitals isn't PascalCase.
 */
class UpperCaseConstantNameSniff extends GenericSniff
{
    /**
     * The parent's code for a constant made with define(), used here for a global const too.
     */
    public const string CODE_CONSTANT_NOT_UPPER_CASE = 'ConstantNotUpperCase';

    public const string CODE_ENUM_CONSTANT_NOT_PASCAL_CASE = 'EnumConstantNotPascalCase';

    public const string CODE_ENUM_CONSTANT_INVALID_CASE = 'EnumConstantInvalidCase';

    /**
     * Whether to require global constants, from `const` or `define()`, and aliases of constants, to be in UPPER_CASE.
     * PER doesn't require it.
     */
    public bool $checkGlobalConstants = false;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [...parent::register(), T_AS];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return void
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] === T_AS) {
            $aliasPtr = $this->checkGlobalConstants ? NamingHelper::findConstantAlias($phpcsFile, $stackPtr) : false;
            if ($aliasPtr !== false) {
                $this->checkGlobalConstant($phpcsFile, $aliasPtr);
            }

            return;
        }

        if ($tokens[$stackPtr]['code'] !== T_CONST) {
            // Anything else the parent checks is a call to `define()`.
            if ($this->checkGlobalConstants) {
                parent::process($phpcsFile, $stackPtr);
            }

            return;
        }

        $isClassConstant = Scopes::isOOConstant($phpcsFile, $stackPtr);
        $isEnum = $isClassConstant && Scopes::validDirectScope($phpcsFile, $stackPtr, T_ENUM) !== false;
        if ($isClassConstant && !$isEnum) {
            parent::process($phpcsFile, $stackPtr);
            return;
        }

        foreach (NamingHelper::findConstantNames($phpcsFile, $stackPtr) as $namePtr) {
            if ($isEnum) {
                $this->checkEnumConstant($phpcsFile, $namePtr);
            } elseif ($this->checkGlobalConstants) {
                $this->checkGlobalConstant($phpcsFile, $namePtr);
            }
        }
    }

    /**
     * Check that a global constant or an alias of one is in UPPER_CASE.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $namePtr The position of the name.
     */
    private function checkGlobalConstant(File $phpcsFile, int $namePtr): void
    {
        $name = $phpcsFile->getTokens()[$namePtr]['content'];
        if (strtoupper($name) !== $name) {
            $phpcsFile->addError(
                'Constants must be uppercase; expected %s but found %s',
                $namePtr,
                self::CODE_CONSTANT_NOT_UPPER_CASE,
                [strtoupper($name), $name],
            );
        }
    }

    /**
     * Check an enum constant's name: PascalCase passes, UPPER_CASE gets a warning, and anything else an error.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $namePtr The position of the constant's name.
     */
    private function checkEnumConstant(File $phpcsFile, int $namePtr): void
    {
        $name = $phpcsFile->getTokens()[$namePtr]['content'];
        if (NamingHelper::isPascalCase($name)) {
            return;
        }

        if (strtoupper($name) === $name) {
            $phpcsFile->addWarning(
                'An enum constant should be in PascalCase; found %s',
                $namePtr,
                self::CODE_ENUM_CONSTANT_NOT_PASCAL_CASE,
                [$name],
            );
            return;
        }

        $phpcsFile->addError(
            'An enum constant must be in PascalCase or UPPER_CASE; found %s',
            $namePtr,
            self::CODE_ENUM_CONSTANT_INVALID_CASE,
            [$name],
        );
    }
}
