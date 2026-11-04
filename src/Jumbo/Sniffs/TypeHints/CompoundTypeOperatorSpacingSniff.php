<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Disallows spaces around the `|` and `&` of a compound type, but allows the type to be split before one.
 *
 * PER Coding Style 3.1, section 2.5, says the union symbol `|` and intersection symbol `&` MUST NOT have a leading or
 * trailing space, and that a compound type MAY be split across lines, with the symbol at the start of each line.
 * SlevomatCodingStandard.TypeHints.DNFTypeHintFormat counts that line break as a space, so it rejects every split
 * type. The PERCS31 ruleset excludes its DisallowedWhitespaceAroundOperator code, and this sniff checks the spacing
 * instead:
 * - A space before or after the symbol, on the same line, is reported, and removed by the fix.
 * - A line break before the symbol is allowed.
 * - A line break after the symbol, which leaves it at the end of the line, is reported, and the fix moves the symbol
 *   to the start of the next line.
 *
 * Which types go on which line is checked by Jumbo.TypeHints.CompoundTypeLayout.
 */
class CompoundTypeOperatorSpacingSniff implements Sniff
{
    public const string CODE_SPACE_AROUND_OPERATOR = 'SpaceAroundOperator';

    public const string CODE_OPERATOR_AT_END_OF_LINE = 'OperatorAtEndOfLine';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_TYPE_UNION, T_TYPE_INTERSECTION];
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
        $before = $tokens[($stackPtr - 1)];
        $after = $tokens[($stackPtr + 1)];
        // An indent before the symbol, at the start of its line, isn't a space before it.
        $hasSpaceBefore = $before['code'] === T_WHITESPACE
            && $tokens[($stackPtr - 2)]['line'] === $tokens[$stackPtr]['line'];
        $isLineEnd = $after['code'] === T_WHITESPACE && str_contains($after['content'], $phpcsFile->eolChar);
        $hasSpaceAfter = $after['code'] === T_WHITESPACE && !$isLineEnd;
        $data = [$tokens[$stackPtr]['content']];

        if ($isLineEnd) {
            $this->reportLineEnd($phpcsFile, $stackPtr, $hasSpaceBefore);
            return;
        }

        if (!$hasSpaceBefore && !$hasSpaceAfter) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'There must be no space around the "%s" in a compound type',
            $stackPtr,
            self::CODE_SPACE_AROUND_OPERATOR,
            $data,
        );
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        if ($hasSpaceBefore) {
            $phpcsFile->fixer->replaceToken(($stackPtr - 1), '');
        }

        if ($hasSpaceAfter) {
            $phpcsFile->fixer->replaceToken(($stackPtr + 1), '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Report a symbol at the end of a line, and fix it by moving it to the start of the next line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the symbol.
     * @param bool $hasSpaceBefore Whether a space, on the same line, comes before the symbol.
     */
    private function reportLineEnd(File $phpcsFile, int $stackPtr, bool $hasSpaceBefore): void
    {
        $tokens = $phpcsFile->getTokens();
        $fix = $phpcsFile->addFixableError(
            'A compound type split across lines must have the "%s" at the start of the line, not the end',
            $stackPtr,
            self::CODE_OPERATOR_AT_END_OF_LINE,
            [$tokens[$stackPtr]['content']],
        );
        if (!$fix) {
            return;
        }

        // The symbol moves to just before the first type on the next line, after its indent.
        $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($next === false) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        if ($hasSpaceBefore) {
            $phpcsFile->fixer->replaceToken(($stackPtr - 1), '');
        }

        $phpcsFile->fixer->replaceToken($stackPtr, '');
        $phpcsFile->fixer->addContentBefore($next, $tokens[$stackPtr]['content']);
        $phpcsFile->fixer->endChangeset();
    }
}
