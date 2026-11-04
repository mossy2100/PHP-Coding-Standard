<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Files\DeclareStatementSniff as Psr12Sniff;

/**
 * Checks the format of a declare statement, including one with several directives.
 *
 * PER Coding Style 3.1, section 3, says declare statements MUST NOT contain any spaces inside the parentheses. The
 * parent, PSR12.Files.DeclareStatement, assumes a single directive with an integer value, so it rejects a statement
 * with several, such as `declare(ticks=1,strict_types=1);`, or with a string value, such as
 * `declare(encoding='UTF-8');`, however it's spaced. This sniff leaves a single integer directive to the parent. For
 * anything else, it reports a space between `declare` and the opening parenthesis, as the parent does, and any
 * whitespace inside the parentheses. The parent's other layout checks, such as for a block declare's braces, aren't
 * run for those statements.
 */
class DeclareStatementSniff extends Psr12Sniff
{
    public const string CODE_SPACE_FOUND_INSIDE_PARENTHESES = 'SpaceFoundInsideParentheses';

    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the declare keyword.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        $closer = $opener === false ? null : ($tokens[$opener]['parenthesis_closer'] ?? null);
        if (!is_int($closer) || $this->parentCanParse($phpcsFile, $opener, $closer)) {
            parent::process($phpcsFile, $stackPtr);
            return;
        }

        if ($opener !== ($stackPtr + 1)) {
            $fix = $phpcsFile->addFixableError(
                'Expected no space between declare keyword and opening parenthesis in a declare statement',
                $stackPtr,
                'SpaceFoundAfterDeclare',
            );
            if ($fix) {
                $phpcsFile->fixer->replaceToken(($stackPtr + 1), '');
            }
        }

        $spaces = [];
        for ($i = ($opener + 1); $i < $closer; $i++) {
            if ($tokens[$i]['code'] === T_WHITESPACE) {
                $spaces[] = $i;
            }
        }

        if ($spaces === []) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Expected no spaces inside the parentheses of a declare statement',
            $opener,
            self::CODE_SPACE_FOUND_INSIDE_PARENTHESES,
        );
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        foreach ($spaces as $space) {
            $phpcsFile->fixer->replaceToken($space, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check whether the parent can read a declare statement: a single directive with an integer value.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the opening parenthesis.
     * @param int $closer The position of the closing parenthesis.
     * @return bool True for a single directive with an integer value, such as `strict_types=1`.
     */
    private function parentCanParse(File $phpcsFile, int $opener, int $closer): bool
    {
        if ($phpcsFile->findNext(T_COMMA, ($opener + 1), $closer) !== false) {
            return false;
        }

        $equals = $phpcsFile->findNext(T_EQUAL, ($opener + 1), $closer);
        $value = $equals === false ? false : $phpcsFile->findNext(T_WHITESPACE, ($equals + 1), $closer, true);

        return $value !== false && $phpcsFile->getTokens()[$value]['code'] === T_LNUMBER;
    }
}
