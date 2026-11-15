<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires one space between a `catch`'s type and its variable, as in `catch (Exception $e)`.
 *
 * PER Coding Style 3.1, section 5.6, shows `catch (FirstThrowableType $e)`, and says to "note the placement of
 * parentheses, spaces, and braces". A catch's parentheses are laid out like a function's parameter list with one
 * parameter, but the inherited sniffs that check the space between a parameter's type and its name,
 * Squiz.Functions.FunctionDeclarationArgumentSpacing and SlevomatCodingStandard.TypeHints.ParameterTypeHintSpacing,
 * read the parameters with a function's own parser, which doesn't accept a `catch`. So this is a sniff of its own.
 *
 * A line break before the variable is allowed, as a multi-catch's types may be split over lines. A comment before the
 * variable is left alone.
 */
class CatchVariableSpacingSniff implements Sniff
{
    public const string CODE_SPACE_BEFORE_VARIABLE = 'SpaceBeforeVariable';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_CATCH];
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
        $opener = $tokens[$stackPtr]['parenthesis_opener'] ?? null;
        $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
        if (!is_int($opener) || !is_int($closer)) {
            return;
        }

        // A catch with no variable (PHP 8.0) has nothing to space.
        $variable = $phpcsFile->findPrevious(T_WHITESPACE, ($closer - 1), $opener, true);
        if ($variable === false || $tokens[$variable]['code'] !== T_VARIABLE) {
            return;
        }

        // Anything but whitespace before the variable is the type, or a comment, which is left alone.
        $type = $phpcsFile->findPrevious(T_WHITESPACE, ($variable - 1), $opener, true);
        if (
            $type === false
            || $tokens[$type]['line'] !== $tokens[$variable]['line']
            || isset(Tokens::COMMENT_TOKENS[$tokens[$type]['code']])
        ) {
            return;
        }

        $found = strlen($phpcsFile->getTokensAsString(($type + 1), ($variable - $type - 1)));
        $error = 'Expected 1 space between the type and the variable of a catch; %s found';
        $code = self::CODE_SPACE_BEFORE_VARIABLE;
        FixerHelper::requireSingleSpace($phpcsFile, $type, $variable, $error, $code, data: [$found]);
    }
}
