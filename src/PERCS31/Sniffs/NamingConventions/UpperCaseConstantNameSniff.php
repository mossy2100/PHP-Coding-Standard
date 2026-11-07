<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\NamingConventions;

use PERCS31\Helpers\NamingHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions\UpperCaseConstantNameSniff as GenericSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires constant names in UPPER_CASE, except in an enum, where PascalCase is also allowed, and recommended.
 *
 * Generic.NamingConventions.UpperCaseConstantName, from PSR-1, requires every constant name in UPPER_CASE. PER Coding
 * Style 3.1, section 9, says constants in an enum MAY use either PascalCase or UPPER_CASE, and PascalCase is
 * RECOMMENDED. So for an enum constant, this sniff allows PascalCase, reports UPPER_CASE with a warning, and anything
 * else, such as camelCase or `HTTPStatus`, with an error. PascalCase is as ValidEnumCaseName describes, so acronyms
 * approved in PERCS31.NamingConventions.AcronymCasing are allowed.
 *
 * The parent also reports a global `const`, outside a class, as a class constant, with the ClassConstantNotUpperCase
 * code. This sniff reports it as `define()` is reported, with ConstantNotUpperCase. Everything else is left to the
 * parent.
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
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return void
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] !== T_CONST) {
            parent::process($phpcsFile, $stackPtr);
            return;
        }

        $conditions = $tokens[$stackPtr]['conditions'];
        $scope = $conditions === [] ? null : end($conditions);
        if ($scope !== null && isset(Tokens::OO_SCOPE_TOKENS[$scope]) && $scope !== T_ENUM) {
            parent::process($phpcsFile, $stackPtr);
            return;
        }

        $namePtr = $this->findConstantName($phpcsFile, $stackPtr);
        if ($namePtr === null) {
            return;
        }

        if ($scope === T_ENUM) {
            $this->checkEnumConstant($phpcsFile, $namePtr);
            return;
        }

        $name = $tokens[$namePtr]['content'];
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
     * Find a `const` statement's name: the last token before the `=`, after any type.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $constPtr The position of `const`.
     * @return int|null The position of the name, or null if the statement is incomplete.
     */
    private function findConstantName(File $phpcsFile, int $constPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $equals = $phpcsFile->findNext([T_EQUAL, T_SEMICOLON], ($constPtr + 1));
        if ($equals === false || $tokens[$equals]['code'] !== T_EQUAL) {
            return null;
        }

        $namePtr = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($equals - 1), ($constPtr + 1), true);

        return $namePtr === false ? null : $namePtr;
    }

    /**
     * Check an enum constant's name: PascalCase passes, UPPER_CASE gets a low-severity warning, and anything else an
     * error.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $namePtr The position of the constant's name.
     */
    private function checkEnumConstant(File $phpcsFile, int $namePtr): void
    {
        $name = $phpcsFile->getTokens()[$namePtr]['content'];
        if (NamingHelper::isPascalCaseInFile($phpcsFile, $name)) {
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
