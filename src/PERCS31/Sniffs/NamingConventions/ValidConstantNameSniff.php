<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\NamingConventions;

use PERCS31\Helpers\NamingHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Utils\Scopes;

/**
 * Disallows a constant name that starts with an underscore.
 *
 * PER Coding Style 3.1, section 4.3, says constant names MUST NOT be prefixed with a single underscore. A double
 * underscore, as in `__A`, is allowed, as PSR2.Methods.MethodDeclaration reads the same rule for methods.
 * Generic.NamingConventions.UpperCaseConstantName only checks the case, so `const _HIDDEN = 1;` passes it.
 *
 * The rule is about showing visibility, which only a class constant has, so a global constant isn't checked by
 * default. Set checkGlobalConstants to check a `const` statement at file scope too, as the Jumbo standard does. A
 * constant made with `define()` isn't checked.
 */
class ValidConstantNameSniff implements Sniff
{
    public const string CODE_UNDERSCORE = 'Underscore';

    /**
     * Whether to check a global constant too, from a `const` statement outside a class.
     */
    public bool $checkGlobalConstants = false;

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
        if (!$this->checkGlobalConstants && !Scopes::isOOConstant($phpcsFile, $stackPtr)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        foreach (NamingHelper::findConstantNames($phpcsFile, $stackPtr) as $namePtr) {
            $name = $tokens[$namePtr]['content'];
            if (!str_starts_with($name, '_') || str_starts_with($name, '__')) {
                continue;
            }

            $phpcsFile->addError(
                'Constant name "%s" must not be prefixed with an underscore',
                $namePtr,
                self::CODE_UNDERSCORE,
                [$tokens[$namePtr]['content']],
            );
        }
    }
}
