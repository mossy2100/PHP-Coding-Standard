<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Formatting;

use PERCS31\Helpers\EmptyBodyHelper;
use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Reports an empty class, function, closure or anonymous class body that isn't written as `{}` after a single space.
 *
 * PER Coding Style 3.1, sections 4 and 4.4, says an empty class, interface, trait, enum, function or method body
 * should be abbreviated as `{}` on the same line as the previous symbol, separated by a space. It's a SHOULD, so this
 * sniff gives a warning. Section 7 says an empty closure body MUST be abbreviated the same way, and section 8 says an
 * anonymous class MUST follow the same rules as a closure, so those are errors. A body containing a comment isn't
 * empty.
 *
 * A body after a `//` or `#` comment, as in `function a() // Why.` then `{` and `}`, is reported but not fixed, as the
 * `{}` can't go on the comment's line.
 */
class EmptyBodySniff implements Sniff
{
    public const string CODE_NOT_ABBREVIATED = 'NotAbbreviated';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return EmptyBodyHelper::OWNER_TOKENS;
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        if (!EmptyBodyHelper::isEmpty($phpcsFile, $stackPtr)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$stackPtr]['scope_opener'];
        $closer = $tokens[$stackPtr]['scope_closer'];
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($opener - 1), null, true);
        assert(is_int($previous));

        // Already ' {}', which may follow a block comment, as in `function () /* Why. */ {}`.
        if ($closer === ($opener + 1) && $previous === ($opener - 2) && $tokens[($opener - 1)]['content'] === ' ') {
            return;
        }

        // `{}` can't follow a line comment on its line, so that's left to a person.
        $fixable = !$this->isLineComment($phpcsFile, $previous);
        $message = 'An empty %s body %s be written as {} on the same line as the declaration, after a single space.';
        $code = $tokens[$stackPtr]['code'];
        $isMust = $code === T_CLOSURE || $code === T_ANON_CLASS;
        $data = match ($code) {
            T_CLOSURE => ['closure', 'must'],
            T_ANON_CLASS => ['anonymous class', 'must'],
            default => [strtolower($tokens[$stackPtr]['content']), 'should'],
        };

        if (!$fixable) {
            if ($isMust) {
                $phpcsFile->addError($message, $opener, self::CODE_NOT_ABBREVIATED, $data);
            } else {
                $phpcsFile->addWarning($message, $opener, self::CODE_NOT_ABBREVIATED, $data);
            }

            return;
        }

        $fix = $isMust
            ? $phpcsFile->addFixableError($message, $opener, self::CODE_NOT_ABBREVIATED, $data)
            : $phpcsFile->addFixableWarning($message, $opener, self::CODE_NOT_ABBREVIATED, $data);
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        FixerHelper::blankBetween($phpcsFile, $previous, ($closer + 1));

        $phpcsFile->fixer->addContent($previous, ' {}');
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Check whether a token is a `//` or `#` comment, which runs to the end of its line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $ptr The position of the token.
     * @return bool True if it is.
     */
    private function isLineComment(File $phpcsFile, int $ptr): bool
    {
        $token = $phpcsFile->getTokens()[$ptr];

        return $token['code'] === T_COMMENT
            && (str_starts_with($token['content'], '//') || str_starts_with($token['content'], '#'));
    }
}
