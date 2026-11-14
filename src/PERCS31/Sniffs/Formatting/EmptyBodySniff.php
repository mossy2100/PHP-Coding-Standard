<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Formatting;

use PERCS31\Helpers\EmptyBodyHelper;
use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPCSUtils\Utils\MessageHelper;

/**
 * Reports an empty class, function, closure, anonymous class or control structure body that isn't written as `{}` after
 * a single space.
 *
 * PER Coding Style 3.1, sections 4 and 4.4, says an empty class, interface, trait, enum, function or method body
 * should be abbreviated as `{}` on the same line as the previous symbol, separated by a space. It's a SHOULD, so this
 * sniff gives a warning. Section 7 says an empty closure body MUST be abbreviated the same way, and section 8 says an
 * anonymous class MUST follow the same rules as a closure, so those are errors. A body containing a comment isn't
 * empty.
 *
 * A body after a `//` or `#` comment, as in `function a() // Why.` then `{` and `}`, is reported but not fixed, as the
 * `{}` can't go on the comment's line.
 *
 * Section 5 says nothing about a control structure's empty body. It's read as a class's or a function's is, so it
 * SHOULD be `{}` too, as in `catch (Exception $e) {}`, and this sniff gives a warning.
 *
 * With strict on, as in the Jumbo standard, every empty body must be `{}`, so each of these is an error.
 */
class EmptyBodySniff implements Sniff
{
    public const string CODE_NOT_ABBREVIATED = 'NotAbbreviated';

    /**
     * Whether every empty body must be `{}`, so one that isn't is an error. PER Coding Style 3.1 only says SHOULD for
     * a class or function, and nothing for a control structure, so it's off by default.
     */
    public bool $strict = false;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [...EmptyBodyHelper::OWNER_TOKENS, ...EmptyBodyHelper::CONTROL_STRUCTURE_TOKENS];
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
        if (!EmptyBodyHelper::isEmpty($phpcsFile, $stackPtr)) {
            return;
        }

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
        $isMust = $this->strict || $code === T_CLOSURE || $code === T_ANON_CLASS;
        $data = match ($code) {
            T_CLOSURE => ['closure', 'must'],
            T_ANON_CLASS => ['anonymous class', 'must'],
            default => [strtolower($tokens[$stackPtr]['content']), $isMust ? 'must' : 'should'],
        };

        if (!$fixable) {
            MessageHelper::addMessage($phpcsFile, $message, $opener, $isMust, self::CODE_NOT_ABBREVIATED, $data);

            return;
        }

        $errorCode = self::CODE_NOT_ABBREVIATED;
        if (!MessageHelper::addFixableMessage($phpcsFile, $message, $opener, $isMust, $errorCode, $data)) {
            return;
        }

        FixerHelper::replaceBetween($phpcsFile, $previous, ($closer + 1), ' {}');
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
