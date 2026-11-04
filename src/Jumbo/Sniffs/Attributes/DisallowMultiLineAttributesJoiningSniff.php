<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Attributes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Disallows several attributes in one attribute block when the block spans more than one line.
 *
 * PER Coding Style 3.0, section 12.3, allows several attributes in one block, e.g. `#[Attribute, Deprecated]`, but
 * says that if the list is split into multiple lines, the attributes MUST be in separate blocks.
 * SlevomatCodingStandard.Attributes.DisallowAttributesJoining disallows joining altogether, and its fix leaves a block
 * written over several lines in a poor layout, so this sniff replaces it. The fix puts each attribute in its own block,
 * on its own line, at the block's indentation.
 */
class DisallowMultiLineAttributesJoiningSniff implements Sniff
{
    public const string CODE_JOINED = 'Joined';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_ATTRIBUTE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the attribute block's opener.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$stackPtr]['attribute_closer'] ?? null;
        if (!is_int($closer) || $tokens[$closer]['line'] === $tokens[$stackPtr]['line']) {
            return;
        }

        $attributes = $this->splitAttributes($phpcsFile, $stackPtr, $closer);
        if (count($attributes) < 2) {
            return;
        }

        // A comment outside an attribute would be lost or misplaced by the fix, so leave that to a person.
        $error = 'Attributes in a block split over several lines must be in separate blocks';
        if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, $stackPtr, $closer) !== false) {
            $phpcsFile->addError($error, $stackPtr, self::CODE_JOINED);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, self::CODE_JOINED);
        if (!$fix) {
            return;
        }

        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
        assert(is_int($firstOnLine));
        $indent = str_repeat(' ', ($tokens[$firstOnLine]['column'] - 1));
        $blocks = array_map(static fn(string $attribute): string => '#[' . $attribute . ']', $attributes);

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($stackPtr, implode($phpcsFile->eolChar . $indent, $blocks));
        for ($i = ($stackPtr + 1); $i <= $closer; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Split an attribute block into its attributes, at the commas that aren't inside an argument list.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the `#[`.
     * @param int $closer The position of the `]`.
     * @return list<string> Each attribute's text, trimmed.
     */
    private function splitAttributes(File $phpcsFile, int $opener, int $closer): array
    {
        $tokens = $phpcsFile->getTokens();
        $attributes = [];
        $start = $opener + 1;
        for ($i = $start; $i <= $closer; $i++) {
            $jumpTo = $tokens[$i]['parenthesis_closer'] ?? $tokens[$i]['bracket_closer'] ?? null;
            if (is_int($jumpTo) && $jumpTo > $i && $jumpTo < $closer) {
                $i = $jumpTo;
                continue;
            }

            if ($i !== $closer && $tokens[$i]['code'] !== T_COMMA) {
                continue;
            }

            $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, $start, $i, true);
            if ($first !== false) {
                $attributes[] = trim($phpcsFile->getTokensAsString($first, ($i - $first)));
            }

            $start = $i + 1;
        }

        return $attributes;
    }
}
