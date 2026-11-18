<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Formatting;

use PERCS31\Helpers\EmptyBodyHelper;
use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\MessageHelper;

/**
 * Reports an empty class, function, closure or anonymous class body that isn't written as `{}` after a single space,
 * and with strict on, an empty control structure body too.
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
 * Section 5 says nothing about a control structure's empty body, so it isn't checked by default.
 *
 * With strict on, as in the Jumbo standard, every empty body must be `{}`, including a control structure's, as in
 * `catch (Exception $e) {}`, so each of these is an error.
 *
 * Section 7 also says an empty closure SHOULD be replaced with an arrow function, "if possible", as in `fn() => null`.
 * This sniff gives a warning for one, as ClosureNotArrowFunction, and the fix replaces it, dropping any `use` list. It
 * isn't possible for a closure with a `void` or `never` return type, as an arrow function always returns a value, so
 * that one is left alone. So is a closure that returns by reference. A closure with a comment anywhere in it is
 * reported but not fixed, as the fix could lose or misplace the comment.
 */
class EmptyBodySniff implements Sniff
{
    public const string CODE_NOT_ABBREVIATED = 'NotAbbreviated';

    public const string CODE_CLOSURE_NOT_ARROW_FUNCTION = 'ClosureNotArrowFunction';

    /**
     * Whether every empty body must be `{}`, including a control structure's, so one that isn't is an error. PER Coding
     * Style 3.1 only says SHOULD for a class or function, and nothing for a control structure, so it's off by default.
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
        $isControlStructure = in_array($tokens[$stackPtr]['code'], EmptyBodyHelper::CONTROL_STRUCTURE_TOKENS, true);
        if ((!$this->strict && $isControlStructure) || !EmptyBodyHelper::isEmpty($phpcsFile, $stackPtr)) {
            return;
        }

        $opener = $tokens[$stackPtr]['scope_opener'];
        $closer = $tokens[$stackPtr]['scope_closer'];
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($opener - 1), null, true);
        assert(is_int($previous));

        // Once a closure is replaced with an arrow function, it has no body left to check.
        if ($tokens[$stackPtr]['code'] === T_CLOSURE && $this->checkArrowFunction($phpcsFile, $stackPtr, $previous)) {
            return;
        }

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
     * Report an empty closure that could be an arrow function, as section 7 says it SHOULD be, and fix it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the closure's `function` keyword.
     * @param int $previous The position of the last token before the body's opening brace, other than whitespace.
     * @return bool True if it was fixed.
     */
    private function checkArrowFunction(File $phpcsFile, int $stackPtr, int $previous): bool
    {
        $tokens = $phpcsFile->getTokens();
        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        $parenthesisCloser = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
        $returnType = strtolower($phpcsFile->getMethodProperties($stackPtr)['return_type']);

        // An arrow function always returns a value, so it can't be void or never. One that returns by reference is
        // left alone.
        if (
            !is_int($parenthesisCloser)
            || $next === false
            || $tokens[$next]['code'] === T_BITWISE_AND
            || in_array($returnType, ['void', 'never'], true)
        ) {
            return false;
        }

        // A comment anywhere in the closure is left to a person, so the fix can't lose or misplace it.
        $opener = $tokens[$stackPtr]['scope_opener'];
        $fixable = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($stackPtr + 1), $opener) === false;

        $message = 'An empty closure should be replaced with an arrow function, as in fn() => null';
        $code = self::CODE_CLOSURE_NOT_ARROW_FUNCTION;
        if (!$fixable) {
            $phpcsFile->addWarning($message, $stackPtr, $code);

            return false;
        }

        if (!$phpcsFile->addFixableWarning($message, $stackPtr, $code)) {
            return false;
        }

        // The fix removes the use list, and puts `=> null` after the last token before the body.
        $use = $phpcsFile->findNext(T_USE, ($parenthesisCloser + 1), $opener);
        $useCloser = $use === false ? null : ($tokens[$use]['parenthesis_closer'] ?? null);

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($stackPtr, 'fn');
        if ($next === ($stackPtr + 2)) {
            // `fn` has no space before its parenthesis.
            $phpcsFile->fixer->replaceToken(($stackPtr + 1), '');
        }

        if (is_int($useCloser)) {
            for ($i = ($parenthesisCloser + 1); $i <= $useCloser; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }

        $last = $previous === $useCloser ? $parenthesisCloser : $previous;
        for ($i = ($last + 1); $i <= $tokens[$stackPtr]['scope_closer']; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContent($last, ' => null');
        $phpcsFile->fixer->endChangeset();

        return true;
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
