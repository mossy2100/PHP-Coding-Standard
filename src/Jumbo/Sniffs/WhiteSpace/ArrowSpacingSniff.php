<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires exactly one space before and after the `=>` in a `match` arm, and optionally in an array or a `yield`.
 *
 * PER Coding Style 3.1, section 6.2, doesn't list `=>` among the binary operators, so section 6 leaves its spacing
 * undefined, except where another section gives it a rule:
 * - A `match` arm: section 5.2 shows one space each side, and says to "note the placement of parentheses, spaces, and
 *   braces". Although it doesn't say so explicitly, it appears to imply the same rule that sections 4.9 and 7.1 set
 *   for a property hook's and an arrow function's `=>`. Checked by default (checkMatch).
 * - An array element, or `yield $key => $value`: PER says nothing, so they're only checked if checkArray or checkYield
 *   is set, as the Jumbo standard does.
 *
 * Other uses of `=>` have their own sniffs: Squiz.ControlStructures.ForEachLoopDeclaration for `foreach`,
 * SlevomatCodingStandard.Functions.ArrowFunctionDeclaration for an arrow function, and
 * Jumbo.Classes.PropertyHookLayout for a property hook.
 *
 * A line break before or after the arrow is allowed, so a long arm, element or yield can be wrapped. For an array, this
 * replaces Squiz.Arrays.ArrayDeclaration's arrow spacing checks, which cover only a single-line array, and in a
 * multi-line array require the arrows aligned instead.
 */
class ArrowSpacingSniff implements Sniff
{
    public const string CODE_SPACING_BEFORE = 'SpacingBefore';

    public const string CODE_SPACING_AFTER = 'SpacingAfter';

    /**
     * Tokens that, walking back from a `=>`, end the search for the array or `yield` it belongs to.
     *
     * @var list<int|string>
     */
    private const array BOUNDARIES = [
        T_SEMICOLON,
        T_OPEN_PARENTHESIS,
        T_OPEN_SQUARE_BRACKET,
        T_OPEN_CURLY_BRACKET,
        T_OPEN_TAG,
    ];

    /**
     * Closing brackets and braces to skip over, walking back from a `=>`.
     *
     * @var list<int|string>
     */
    private const array BRACKET_CLOSERS = [T_CLOSE_SQUARE_BRACKET, T_CLOSE_SHORT_ARRAY, T_CLOSE_CURLY_BRACKET];

    /**
     * Whether to check the `=>` in a `match` arm.
     */
    public bool $checkMatch = true;

    /**
     * Whether to check the `=>` in an array element. PER says nothing about it.
     */
    public bool $checkArray = false;

    /**
     * Whether to check the `=>` in `yield $key => $value`. PER says nothing about it.
     */
    public bool $checkYield = false;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_MATCH_ARROW, T_DOUBLE_ARROW];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $context = $phpcsFile->getTokens()[$stackPtr]['code'] === T_MATCH_ARROW
            ? 'match arm'
            : $this->findContext($phpcsFile, $stackPtr);

        $isChecked = match ($context) {
            'match arm' => $this->checkMatch,
            'array' => $this->checkArray,
            'yield' => $this->checkYield,
            default => false,
        };
        if (!$isChecked) {
            return;
        }

        $this->checkSpacing($phpcsFile, $stackPtr, $stackPtr - 1, 'before', $context, self::CODE_SPACING_BEFORE);
        $this->checkSpacing($phpcsFile, $stackPtr, $stackPtr + 1, 'after', $context, self::CODE_SPACING_AFTER);
    }

    /**
     * Find what a `=>` belongs to, by walking back over its key.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $arrowPtr The position of the `=>`.
     * @return string|null "array" or "yield", or null for anything else, such as a `foreach`.
     */
    private function findContext(File $phpcsFile, int $arrowPtr): ?string
    {
        $tokens = $phpcsFile->getTokens();

        // A `yield` can only be the start of this `=>`'s key, before any comma or earlier `=>` in a list.
        $isKeyStart = true;
        for ($i = ($arrowPtr - 1); $i >= 0; $i--) {
            $code = $tokens[$i]['code'];
            if ($code === T_YIELD && $isKeyStart) {
                return 'yield';
            }

            if ($code === T_OPEN_SHORT_ARRAY) {
                return 'array';
            }

            if ($code === T_OPEN_PARENTHESIS) {
                $owner = $tokens[$i]['parenthesis_owner'] ?? null;

                return $owner !== null && $tokens[$owner]['code'] === T_ARRAY ? 'array' : null;
            }

            if ($code === T_CLOSE_PARENTHESIS && isset($tokens[$i]['parenthesis_opener'])) {
                $i = $tokens[$i]['parenthesis_opener'];
                continue;
            }

            // Skip a bracketed key, or an earlier element's closure or match body.
            $isBracketCloser = in_array($code, self::BRACKET_CLOSERS, true);
            if ($isBracketCloser && isset($tokens[$i]['bracket_opener'])) {
                $i = $tokens[$i]['bracket_opener'];

                // A closure's or match's body: carry on from its keyword, past any return type.
                $owner = $tokens[$i]['scope_condition'] ?? null;
                if (is_int($owner) && $owner < $i) {
                    $i = $owner;
                }

                continue;
            }

            // A case label's or an alternative syntax colon ends the search; a return type's doesn't.
            if ($code === T_COLON && isset($tokens[$i]['scope_condition'])) {
                return null;
            }

            if ($code === T_COMMA || $code === T_DOUBLE_ARROW) {
                $isKeyStart = false;
                continue;
            }

            if (in_array($code, self::BOUNDARIES, true)) {
                return null;
            }
        }

        return null;
    }

    /**
     * Check the spacing on one side of the arrow, and fix it if needed.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $arrowPtr The position of the arrow.
     * @param int $sidePtr The position of the token next to the arrow, on the side being checked.
     * @param string $side 'before' or 'after', for the error message.
     * @param string $context What the arrow belongs to, for the error message.
     * @param string $code The error code.
     */
    private function checkSpacing(
        File $phpcsFile,
        int $arrowPtr,
        int $sidePtr,
        string $side,
        string $context,
        string $code,
    ): void {
        $tokens = $phpcsFile->getTokens();
        $isWhitespace = $tokens[$sidePtr]['code'] === T_WHITESPACE;

        // One space is correct. A line break is allowed; indentation at the start of a line is a separate token.
        if (
            $isWhitespace
            && (
                $tokens[$sidePtr]['content'] === ' '
                || str_contains($tokens[$sidePtr]['content'], "\n")
                || $tokens[$sidePtr]['column'] === 1
            )
        ) {
            return;
        }

        $found = $isWhitespace ? strlen($tokens[$sidePtr]['content']) : 0;
        $fix = $phpcsFile->addFixableError(
            'Expected 1 space %s "=>" in %s; %s found.',
            $arrowPtr,
            $code,
            [$side, $context, $found],
        );
        if (!$fix) {
            return;
        }

        if ($isWhitespace) {
            $phpcsFile->fixer->replaceToken($sidePtr, ' ');
        } elseif ($side === 'before') {
            $phpcsFile->fixer->addContentBefore($arrowPtr, ' ');
        } else {
            $phpcsFile->fixer->addContent($arrowPtr, ' ');
        }
    }
}
