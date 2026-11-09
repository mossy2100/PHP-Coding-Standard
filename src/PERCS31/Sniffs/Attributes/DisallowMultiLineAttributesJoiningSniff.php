<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Attributes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\AttributeBlock;

/**
 * Disallows several attributes in one attribute block when the block spans more than one line.
 *
 * PER Coding Style 3.1, section 12.3, allows several attributes in one block, e.g. `#[Attribute, Deprecated]`, but
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

        $attributes = [];
        foreach (AttributeBlock::getAttributes($phpcsFile, $stackPtr) as $attribute) {
            $start = $attribute['start'];
            $end = $attribute['end'];
            if (is_int($start) && is_int($end)) {
                $attributes[] = trim($phpcsFile->getTokensAsString($start, ($end - $start + 1)));
            }
        }
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
}
