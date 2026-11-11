<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\PHP;

use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\LowerCaseTypeSniff as GenericSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires PHP's types in lower case, including in a `set` hook's parameter.
 *
 * PER Coding Style 3.1, section 2.5, says all PHP types MUST be in lower case. This extends Generic.PHP.LowerCaseType,
 * and changes two things:
 * - It checks the type of a `set` hook's parameter, as in `set(INT $value)`. PHP_CodeSniffer doesn't know about
 *   property hooks (PHP 8.4), so it reads that type as an expression, and the parent doesn't see it. Only a type read
 *   as a plain name is checked. One read as a keyword, such as `SELF` or `NULL`, is already reported there by
 *   PERCS31.PHP.LowerCaseKeyword or Generic.PHP.LowerCaseConstant.
 * - It drops the parent's reports for a `set` hook's parameter after an attribute, as in `set(#[A] INT $value)`. There,
 *   the parent reads the parameter as a property, and reports its type twice, as PropertyTypeFound.
 */
class LowerCaseTypeSniff extends GenericSniff
{
    /**
     * PHP's types, as the parent lists them. In a hook's parameter, only one read as a plain name is checked.
     *
     * @var array<string, true>
     */
    private const array TYPES = [
        'self' => true,
        'parent' => true,
        'array' => true,
        'callable' => true,
        'bool' => true,
        'float' => true,
        'int' => true,
        'string' => true,
        'iterable' => true,
        'void' => true,
        'object' => true,
        'mixed' => true,
        'static' => true,
        'false' => true,
        'true' => true,
        'null' => true,
        'never' => true,
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [...parent::register(), T_STRING];
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
        if ($tokens[$stackPtr]['code'] !== T_STRING) {
            parent::process($phpcsFile, $stackPtr);

            return;
        }

        // A hook's name is cheap to test, so it comes before the search for a hook's parameter list.
        if (strtolower($tokens[$stackPtr]['content']) !== 'set') {
            return;
        }

        $opener = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if ($opener === false || !PropertyHookHelper::isHookParameterList($phpcsFile, $opener)) {
            return;
        }

        $error = 'PHP parameter type declarations must be lowercase; expected "%s" but found "%s"';
        $closer = $tokens[$opener]['parenthesis_closer'];
        for ($i = ($opener + 1); $i < $closer; $i++) {
            // An attribute's arguments are expressions, and the type ends at the parameter.
            $i = $tokens[$i]['attribute_closer'] ?? $i;
            if ($tokens[$i]['code'] === T_VARIABLE) {
                break;
            }

            $content = $tokens[$i]['content'];
            if ($tokens[$i]['code'] === T_STRING && isset(self::TYPES[strtolower($content)])) {
                $this->processType($phpcsFile, $i, $content, $error, 'ParamTypeFound');
            }
        }
    }

    /**
     * Report a type that isn't in lower case, unless it's the parent's misreading of a hook's parameter as a property.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the type.
     * @param string $type The type, as written.
     * @param string $error The error message.
     * @param string $errorCode The error code.
     */
    protected function processType(File $phpcsFile, int $stackPtr, string $type, string $error, string $errorCode): void
    {
        $isMisread = $errorCode === 'PropertyTypeFound'
            && PropertyHookHelper::isInHookParameterList($phpcsFile, $stackPtr);
        if (!$isMisread) {
            parent::processType($phpcsFile, $stackPtr, $type, $error, $errorCode);
        }
    }
}
