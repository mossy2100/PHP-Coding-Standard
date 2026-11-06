<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires each `match` arm to start on a new line.
 *
 * PER Coding Style 3.1, section 5.2, says a `match` expression looks like its example, which has one arm per line. So
 * `1 => 2, default => 3,` on one line is reported, and the fix moves the second arm to the next line. Several
 * conditions in one arm, as in `1, 2, 3 => 'a',`, are one arm, so they're fine. The indent is left to
 * PERCS31.WhiteSpace.ScopeIndent.
 */
class MatchArmPlacementSniff implements Sniff
{
    public const string CODE_NOT_ON_OWN_LINE = 'NotOnOwnLine';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_MATCH];
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
        $opener = $tokens[$stackPtr]['scope_opener'] ?? null;
        $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
        if (!is_int($opener) || !is_int($closer)) {
            return;
        }

        $inBody = false;
        for ($i = ($opener + 1); $i < $closer; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_MATCH_ARROW) {
                $inBody = true;
            } elseif ($code === T_COMMA && $inBody) {
                // The comma that ends an arm's body.
                $inBody = false;
                $this->checkNextArm($phpcsFile, $i, $closer);
            }

            // Commas and arrows inside brackets aren't this match's.
            $jumpTo = $tokens[$i]['parenthesis_closer'] ?? $tokens[$i]['bracket_closer']
                ?? $tokens[$i]['attribute_closer'] ?? null;
            if (is_int($jumpTo) && $jumpTo > $i && $jumpTo < $closer) {
                $i = $jumpTo;
            }
        }
    }

    /**
     * Check that the arm after a comma, if there's one, starts on a new line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $comma The position of the comma that ends the previous arm.
     * @param int $closer The position of the match's closing brace.
     */
    private function checkNextArm(File $phpcsFile, int $comma, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($comma + 1), $closer, true);
        if ($next === false || $tokens[$next]['line'] !== $tokens[$comma]['line']) {
            return;
        }

        $error = 'Each match arm must start on a new line';
        if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($comma + 1), $next) !== false) {
            $phpcsFile->addError($error, $next, self::CODE_NOT_ON_OWN_LINE);
            return;
        }

        if (!$phpcsFile->addFixableError($error, $next, self::CODE_NOT_ON_OWN_LINE)) {
            return;
        }

        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $comma, true);
        assert(is_int($firstOnLine));
        $indent = str_repeat(' ', ($tokens[$firstOnLine]['column'] - 1));
        $phpcsFile->fixer->beginChangeset();
        for ($i = ($comma + 1); $i < $next; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContent($comma, $phpcsFile->eolChar . $indent);
        $phpcsFile->fixer->endChangeset();
    }
}
