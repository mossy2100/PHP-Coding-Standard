<?php

declare(strict_types=1);

namespace Mossy2100\Sniffs\Formatting;

use Mossy2100\Helpers\EmptyBodyHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Reports an empty class, function, closure or anonymous class body that isn't written as `{}` after a single space.
 *
 * PER Coding Style 3.1, sections 4 and 4.4, says an empty class, interface, trait, enum, function or method body
 * should be abbreviated as `{}` on the same line as the previous symbol, separated by a space. It's a SHOULD, so this
 * sniff gives a warning. Section 7 says an empty closure body MUST be abbreviated the same way, and section 8 says an
 * anonymous class MUST follow the same rules as a closure, so those are errors. A body containing a comment isn't
 * empty.
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

        // A comment before the brace would end up before the {}, so leave it alone.
        if (isset(Tokens::COMMENT_TOKENS[$tokens[$previous]['code']])) {
            return;
        }

        // Already ' {}'.
        if ($closer === ($opener + 1) && $previous === ($opener - 2) && $tokens[($opener - 1)]['content'] === ' ') {
            return;
        }

        $message = 'An empty %s body %s be written as {} on the same line as the declaration, after a single space.';
        if ($tokens[$stackPtr]['code'] === T_CLOSURE) {
            $fix = $phpcsFile->addFixableError($message, $opener, self::CODE_NOT_ABBREVIATED, ['closure', 'must']);
        } elseif ($tokens[$stackPtr]['code'] === T_ANON_CLASS) {
            $data = ['anonymous class', 'must'];
            $fix = $phpcsFile->addFixableError($message, $opener, self::CODE_NOT_ABBREVIATED, $data);
        } else {
            $data = [strtolower($tokens[$stackPtr]['content']), 'should'];
            $fix = $phpcsFile->addFixableWarning($message, $opener, self::CODE_NOT_ABBREVIATED, $data);
        }

        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($previous + 1); $i <= $closer; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContent($previous, ' {}');
        $phpcsFile->fixer->endChangeset();
    }
}
