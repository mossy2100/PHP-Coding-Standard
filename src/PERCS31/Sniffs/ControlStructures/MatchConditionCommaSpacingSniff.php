<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSExtra\Universal\Sniffs\WhiteSpace\CommaSpacingSniff;

/**
 * Requires no space before, and one space after, a comma between a `match` arm's conditions, as in `1, 2 => 'a'`.
 *
 * PER Coding Style 3.1, section 5.2, shows a `match` arm's conditions as `1, 2, 3 => multipleCases(),`, and its
 * examples set the rules here. Universal.WhiteSpace.CommaSpacing checks this, but with the same codes as for arrays
 * and other lists, which PER doesn't set the spacing of, so it can't be enabled for `match` conditions alone. It's
 * final, so this sniff passes it only the commas between an arm's conditions, rather than extending it. Its messages
 * and fixes are unchanged, and its codes are reported under this sniff's name.
 */
class MatchConditionCommaSpacingSniff implements Sniff
{
    /**
     * Universal's sniff, which checks the spacing.
     */
    private ?CommaSpacingSniff $commaSpacing = null;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_COMMA];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        if (!$this->isBetweenConditions($phpcsFile, $stackPtr)) {
            return;
        }

        $this->commaSpacing ??= new CommaSpacingSniff();
        $this->commaSpacing->process($phpcsFile, $stackPtr);
    }

    /**
     * Check whether a comma is between two of a `match` arm's conditions, not after an arm or in a nested list.
     *
     * Walking back from the comma at the `match` body's top level, an arm's `=>` comes first if the comma ends the
     * arm. Otherwise the walk reaches the start of the conditions: the body's `{`, or the comma that ends the previous
     * arm, or another condition's comma.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the comma.
     * @return bool True if it's between two conditions.
     */
    private function isBetweenConditions(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $conditions = $tokens[$stackPtr]['conditions'];
        $owner = array_key_last($conditions);
        if ($owner === null || $conditions[$owner] !== T_MATCH) {
            return false;
        }

        $opener = $tokens[$owner]['scope_opener'] ?? null;
        if (!is_int($opener)) {
            return false;
        }

        $openers = [T_OPEN_PARENTHESIS, T_OPEN_SQUARE_BRACKET, T_OPEN_SHORT_ARRAY, T_OPEN_CURLY_BRACKET, T_ATTRIBUTE];
        for ($i = ($stackPtr - 1); $i > $opener; $i--) {
            $code = $tokens[$i]['code'];
            if ($code === T_MATCH_ARROW) {
                return false;
            }

            if ($code === T_COMMA) {
                return true;
            }

            // An opener means the comma is nested, as in an array condition; a closer is skipped back to its opener.
            if (in_array($code, $openers, true)) {
                return false;
            }

            $i = $tokens[$i]['parenthesis_opener'] ?? $tokens[$i]['bracket_opener'] ?? $tokens[$i]['attribute_opener']
                ?? $i;
        }

        return true;
    }
}
