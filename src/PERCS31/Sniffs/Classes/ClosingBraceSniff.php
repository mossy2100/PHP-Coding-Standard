<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Classes\ClosingBraceSniff as Psr12Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Disallows a comment or statement after the closing brace of a class or function, including an anonymous class.
 *
 * PER Coding Style 3.1, section 4, says any closing brace MUST NOT be followed by any comment or statement on the same
 * line, and that the term "class" refers to all classes. PSR12.Classes.ClosingBrace checks classes, interfaces,
 * traits, enums and functions, but not an anonymous class, and has no property to add one. So this sniff extends it.
 *
 * An anonymous class's `}` is part of an expression, so what completes it may follow, as in `};`, `});` or `})->x;`.
 * Only a comment after it on the same line is reported, as in `}; // Comment` or `}, // Comment`. A statement after
 * the `;` is left to PERCS31.Formatting.DisallowMultipleStatements. A phpcs annotation is allowed, as it only works on
 * that line. It isn't fixable, as where the comment should go is left to a person.
 */
class ClosingBraceSniff extends Psr12Sniff
{
    public const string CODE_STATEMENT_AFTER = 'StatementAfter';

    /**
     * Returns an array of tokens this test wants to listen for: the parent's, and an anonymous class.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [...parent::register(), T_ANON_CLASS];
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
        if ($tokens[$stackPtr]['code'] !== T_ANON_CLASS) {
            parent::process($phpcsFile, $stackPtr);
            return;
        }

        $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
        if (!is_int($closer)) {
            return;
        }

        // A phpcs annotation only works on its line, so it's allowed there.
        $comments = array_diff_key(Tokens::COMMENT_TOKENS, Tokens::PHPCS_ANNOTATION_TOKENS);
        $comment = $phpcsFile->findNext($comments, ($closer + 1));
        if ($comment === false || $tokens[$comment]['line'] !== $tokens[$closer]['line']) {
            return;
        }

        $phpcsFile->addError(
            'Closing brace must not be followed by any comment or statement on the same line',
            $closer,
            self::CODE_STATEMENT_AFTER,
        );
    }
}
