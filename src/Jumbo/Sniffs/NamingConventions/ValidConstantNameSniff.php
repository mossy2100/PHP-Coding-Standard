<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Disallows a constant name that starts with an underscore.
 *
 * PER Coding Style 3.0, section 4.3, says constant names MUST NOT be prefixed with a single underscore.
 * Generic.NamingConventions.UpperCaseConstantName only checks the case, so `const _HIDDEN = 1;` passes it. This sniff
 * checks class constants and `const` statements at file scope. A constant made with `define()` isn't checked.
 */
class ValidConstantNameSniff implements Sniff
{
    public const string CODE_UNDERSCORE = 'Underscore';

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
        $tokens = $phpcsFile->getTokens();
        $endPtr = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1));
        if ($endPtr === false) {
            return;
        }

        // Each constant's name is the token before its '=' at the statement's own level, not one inside a value.
        $level = count($tokens[$stackPtr]['nested_parenthesis'] ?? []);
        $bracketDepth = 0;
        for ($i = ($stackPtr + 1); $i < $endPtr; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_OPEN_SHORT_ARRAY || $code === T_OPEN_SQUARE_BRACKET) {
                $bracketDepth++;
            } elseif ($code === T_CLOSE_SHORT_ARRAY || $code === T_CLOSE_SQUARE_BRACKET) {
                $bracketDepth--;
            }

            if ($code !== T_EQUAL || $bracketDepth !== 0 || count($tokens[$i]['nested_parenthesis'] ?? []) !== $level) {
                continue;
            }

            $namePtr = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($i - 1), $stackPtr, true);
            if ($namePtr === false || !str_starts_with($tokens[$namePtr]['content'], '_')) {
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
