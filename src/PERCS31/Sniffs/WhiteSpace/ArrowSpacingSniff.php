<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Utils\Arrays;
use PHPCSUtils\Utils\Lists;
use PHPCSUtils\Utils\PassedParameters;

/**
 * Requires exactly one space before and after the `=>` in a `match` arm, and optionally in an array or a `yield`.
 *
 * PER Coding Style 3.1, section 6.2, doesn't list `=>` among the binary operators, so section 6 leaves its spacing
 * undefined, except where another section gives it a rule:
 * - A `match` arm: section 5.2 shows one space each side, and says to "note the placement of parentheses, spaces, and
 *   braces". Although it doesn't say so explicitly, it appears to imply the same rule that sections 4.9 and 7.1 set
 *   for a property hook's and an arrow function's `=>`. Checked by default (checkMatch).
 * - An array element, including a destructuring assignment's, or `yield $key => $value`: PER says nothing, so they're
 *   only checked if checkArray or checkYield is set, as the Jumbo standard does.
 *
 * Other uses of `=>` have their own sniffs: Squiz.ControlStructures.ForEachLoopDeclaration for `foreach`,
 * SlevomatCodingStandard.Functions.ArrowFunctionDeclaration for an arrow function, and
 * PERCS31.Classes.PropertyHookLayout for a property hook.
 *
 * A line break before or after the arrow is allowed, so a long arm, element or yield can be wrapped. For an array, this
 * replaces Squiz.Arrays.ArrayDeclaration's arrow spacing checks, which cover only a single-line array, and in a
 * multi-line array require the arrows aligned instead. Arrays and lists are parsed with PHPCSUtils.
 */
class ArrowSpacingSniff implements Sniff
{
    public const string CODE_SPACING_BEFORE = 'SpacingBefore';

    public const string CODE_SPACING_AFTER = 'SpacingAfter';

    /**
     * Tokens that end a `yield`'s key, when looking forward from the `yield` for its `=>`.
     *
     * @var list<int|string>
     */
    private const array YIELD_ENDS = [
        T_SEMICOLON,
        T_COMMA,
        T_CLOSE_PARENTHESIS,
        T_CLOSE_SQUARE_BRACKET,
        T_CLOSE_SHORT_ARRAY,
        T_CLOSE_CURLY_BRACKET,
        T_CLOSE_TAG,
    ];

    /**
     * Whether to check the `=>` in a `match` arm.
     */
    public bool $checkMatch = true;

    /**
     * Whether to check the `=>` in an array element, including a destructuring assignment's. PER says nothing about it.
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
        return [T_MATCH_ARROW, T_ARRAY, T_OPEN_SHORT_ARRAY, T_LIST, T_YIELD];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $code = $phpcsFile->getTokens()[$stackPtr]['code'];
        if ($code === T_MATCH_ARROW) {
            if ($this->checkMatch) {
                $this->checkArrow($phpcsFile, $stackPtr, 'match arm');
            }

            return;
        }

        if ($code === T_YIELD) {
            $arrow = $this->checkYield ? $this->findYieldArrow($phpcsFile, $stackPtr) : null;
            if ($arrow !== null) {
                $this->checkArrow($phpcsFile, $arrow, 'yield');
            }

            return;
        }

        if (!$this->checkArray) {
            return;
        }

        foreach ($this->findArrayArrows($phpcsFile, $stackPtr) as $arrow) {
            $this->checkArrow($phpcsFile, $arrow, 'array');
        }
    }

    /**
     * Find the `=>` of each keyed element of an array or destructuring list.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openerPtr The position of `array`, `list` or a short array's or list's `[`.
     * @return list<int> The positions of the arrows.
     */
    private function findArrayArrows(File $phpcsFile, int $openerPtr): array
    {
        $code = $phpcsFile->getTokens()[$openerPtr]['code'];
        $isList = $code === T_LIST || ($code === T_OPEN_SHORT_ARRAY && Lists::isShortList($phpcsFile, $openerPtr));
        if ($isList) {
            $arrows = array_column(Lists::getAssignments($phpcsFile, $openerPtr), 'double_arrow_token');

            return array_values(array_filter($arrows, 'is_int'));
        }

        if ($code === T_OPEN_SHORT_ARRAY && !Arrays::isShortArray($phpcsFile, $openerPtr)) {
            return [];
        }

        $arrows = [];
        foreach (PassedParameters::getParameters($phpcsFile, $openerPtr) as $element) {
            $arrow = Arrays::getDoubleArrowPtr($phpcsFile, (int) $element['start'], (int) $element['end']);
            if ($arrow !== false) {
                $arrows[] = $arrow;
            }
        }

        return $arrows;
    }

    /**
     * Find the `=>` between a `yield`'s key and value, if it has one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $yieldPtr The position of `yield`.
     * @return int|null The position of the `=>`, or null if the `yield` has no key.
     */
    private function findYieldArrow(File $phpcsFile, int $yieldPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $count = count($tokens);
        for ($i = ($yieldPtr + 1); $i < $count; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_DOUBLE_ARROW) {
                return $i;
            }

            // Skip a bracketed part of the key, such as a call's arguments or an array.
            if ($code === T_OPEN_PARENTHESIS && isset($tokens[$i]['parenthesis_closer'])) {
                $i = $tokens[$i]['parenthesis_closer'];
                continue;
            }

            if (isset($tokens[$i]['bracket_closer']) && $tokens[$i]['bracket_closer'] > $i) {
                $i = $tokens[$i]['bracket_closer'];
                continue;
            }

            if (in_array($code, self::YIELD_ENDS, true)) {
                return null;
            }
        }

        return null;
    }

    /**
     * Check the spacing on both sides of an arrow.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $arrowPtr The position of the arrow.
     * @param string $context What the arrow belongs to, for the error message.
     */
    private function checkArrow(File $phpcsFile, int $arrowPtr, string $context): void
    {
        $this->checkSpacing($phpcsFile, $arrowPtr, $arrowPtr - 1, 'before', $context, self::CODE_SPACING_BEFORE);
        $this->checkSpacing($phpcsFile, $arrowPtr, $arrowPtr + 1, 'after', $context, self::CODE_SPACING_AFTER);
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
