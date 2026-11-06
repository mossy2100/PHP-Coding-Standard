<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Disallows a statement after a control structure's closing brace, on the same line.
 *
 * PER Coding Style 3.1, section 4, says any closing brace MUST NOT be followed by a comment or statement on the same
 * line, but it's in the section on classes, and PERCS31 follows PHP_CodeSniffer in reading it as classes and functions.
 * So this is an extra rule, for `if`, `else`, loops, `switch`, `try` and block `declare` statements. A comment is
 * allowed, such as `} // for $i`, and so is what continues a structure, such as `} else {`, or `} else if {`, which
 * PHP reads as `else` and `if`. The fix moves the statement to the next line.
 *
 * A block `declare` in the file header is skipped, as PERCS31.Files.FileHeader reports what follows it on the same
 * line.
 */
class StatementAfterClosingBraceSniff implements Sniff
{
    public const string CODE_STATEMENT_AFTER = 'StatementAfter';

    /**
     * The control structures this checks.
     *
     * @var list<int|string>
     */
    private const array STRUCTURES = [
        T_IF,
        T_ELSEIF,
        T_ELSE,
        T_FOR,
        T_FOREACH,
        T_WHILE,
        T_DO,
        T_SWITCH,
        T_TRY,
        T_CATCH,
        T_FINALLY,
        T_DECLARE,
    ];

    /**
     * Tokens that can follow a closing brace on its line without starting a statement.
     *
     * @var list<int|string>
     */
    private const array ALLOWED_NEXT = [
        T_ELSE,
        T_ELSEIF,
        T_CATCH,
        T_FINALLY,
        T_CLOSE_TAG,
        T_CLOSE_CURLY_BRACKET,
        T_SEMICOLON,
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_CLOSE_CURLY_BRACKET];
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
        $condition = $tokens[$stackPtr]['scope_condition'] ?? null;
        if (
            !is_int($condition)
            || !in_array($tokens[$condition]['code'], self::STRUCTURES, true)
            || $this->isDeclareInHeader($phpcsFile, $condition)
        ) {
            return;
        }

        $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (
            $next === false
            || $tokens[$next]['line'] !== $tokens[$stackPtr]['line']
            || isset(Tokens::COMMENT_TOKENS[$tokens[$next]['code']])
            || in_array($tokens[$next]['code'], self::ALLOWED_NEXT, true)
            || ($tokens[$condition]['code'] === T_DO && $tokens[$next]['code'] === T_WHILE)
        ) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'A statement must not follow a control structure\'s closing brace on the same line',
            $next,
            self::CODE_STATEMENT_AFTER,
        );
        if (!$fix) {
            return;
        }

        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
        assert(is_int($firstOnLine));
        $phpcsFile->fixer->beginChangeset();
        for ($i = ($stackPtr + 1); $i < $next; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $indent = str_repeat(' ', ($tokens[$firstOnLine]['column'] - 1));
        $phpcsFile->fixer->addContent($stackPtr, $phpcsFile->eolChar . $indent);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check whether a structure is a block declare in the file header, with only declare statements or a docblock
     * before it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $condition The position of the structure's keyword.
     * @return bool True if it is.
     */
    private function isDeclareInHeader(File $phpcsFile, int $condition): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$condition]['code'] !== T_DECLARE) {
            return false;
        }

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($condition - 1), null, true);
        while ($previous !== false && $tokens[$previous]['code'] === T_SEMICOLON) {
            $start = $phpcsFile->findStartOfStatement($previous);
            if ($tokens[$start]['code'] !== T_DECLARE) {
                return false;
            }

            $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($start - 1), null, true);
        }

        if ($previous !== false && $tokens[$previous]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
            $opener = $tokens[$previous]['comment_opener'];
            $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($opener - 1), null, true);
        }

        return $previous !== false && $tokens[$previous]['code'] === T_OPEN_TAG;
    }
}
