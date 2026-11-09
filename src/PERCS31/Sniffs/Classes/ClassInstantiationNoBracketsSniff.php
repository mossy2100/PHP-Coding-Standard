<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Warns about parentheses around a class instantiation that's immediately dereferenced, e.g. `(new Foo())->bar()`.
 *
 * PER Coding Style 3.1, section 4, says that when accessing a class member immediately after instantiating a class,
 * the instantiation SHOULD NOT be wrapped in parentheses. PHP 8.4 allows `new Foo()->bar()`. It's a SHOULD, so this is
 * a warning.
 *
 * Only parentheses holding exactly one `new` expression, followed by `->`, `?->` or `::`, are reported. So
 * `(new Foo() ?: $default)->bar()` is left alone, as removing them would change its meaning, and so is a function
 * call's argument list, as in `new self(new Foo())->bar()`. A `new` without an argument list, such as
 * `(new Foo)->bar()`, is left until PSR12.Classes.ClassInstantiation has added the `()`, as PHP needs them.
 *
 * SlevomatCodingStandard.PHP.UselessParentheses has a similar check (enableCheckAroundNew), but it misses `::` and
 * removes the parentheses from `(new Foo() ?: new Bar())->baz()`, changing its meaning.
 */
class ClassInstantiationNoBracketsSniff implements Sniff
{
    public const string CODE_UNNECESSARY = 'NewWithUnnecessaryParentheses';

    /**
     * Tokens that, before a `(`, make it a call's argument list rather than wrapping parentheses.
     *
     * @var list<int|string>
     */
    private const array BEFORE_CALL = [
        T_VARIABLE,
        T_CLOSE_PARENTHESIS,
        T_CLOSE_SQUARE_BRACKET,
        T_CLOSE_CURLY_BRACKET,
        T_NEW,
        T_ARRAY,
        T_LIST,
        T_USE,
        T_DECLARE,
    ];

    /**
     * Tokens that can make up the class reference in a `new` expression, before its argument list.
     *
     * @var list<int|string>
     */
    private const array CLASS_REFERENCE = [
        T_STRING,
        T_NAME_QUALIFIED,
        T_NAME_FULLY_QUALIFIED,
        T_NAME_RELATIVE,
        T_VARIABLE,
        T_SELF,
        T_STATIC,
        T_PARENT,
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_OPEN_PARENTHESIS];
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
        $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
        $newPtr = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if (!is_int($closer) || $newPtr === false || $tokens[$newPtr]['code'] !== T_NEW) {
            return;
        }

        // A control structure's or function's parentheses, or a call's argument list.
        if (isset($tokens[$stackPtr]['parenthesis_owner'])) {
            return;
        }

        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($stackPtr - 1), null, true);
        if (
            $previous !== false
            && (
                isset(Tokens::FUNCTION_NAME_TOKENS[$tokens[$previous]['code']])
                    || in_array($tokens[$previous]['code'], self::BEFORE_CALL, true)
            )
        ) {
            return;
        }

        // The parentheses must hold just the `new` expression.
        $end = $this->findNewExpressionEnd($phpcsFile, $newPtr, $closer);
        if ($end === null || $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($end + 1), null, true) !== $closer) {
            return;
        }

        $after = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closer + 1), null, true);
        if (
            $after === false
            || !in_array($tokens[$after]['code'], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON], true)
        ) {
            return;
        }

        // A comment between the parentheses and the expression would be lost, so leave that to a person.
        $warning = 'Unnecessary parentheses around a class instantiation; use "new Foo()->bar()", '
            . 'not "(new Foo())->bar()"';
        $hasComment = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($stackPtr + 1), $newPtr) !== false
            || $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($end + 1), $closer) !== false;
        if ($hasComment) {
            $phpcsFile->addWarning($warning, $stackPtr, self::CODE_UNNECESSARY);
            return;
        }

        if (!$phpcsFile->addFixableWarning($warning, $stackPtr, self::CODE_UNNECESSARY)) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = $stackPtr; $i < $newPtr; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        for ($i = ($end + 1); $i <= $closer; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Find the last token of a `new` expression: its argument list's `)`, or an anonymous class's `}`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $newPtr The position of `new`.
     * @param int $limit The position of the wrapping `)`, which the expression must end before.
     * @return int|null The position, or null if there's no argument list or the class reference isn't simple.
     */
    private function findNewExpressionEnd(File $phpcsFile, int $newPtr, int $limit): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($newPtr + 1), $limit, true);
        if ($next === false) {
            return null;
        }

        if ($tokens[$next]['code'] === T_ANON_CLASS) {
            $end = $tokens[$next]['scope_closer'] ?? null;

            return is_int($end) && $end < $limit ? $end : null;
        }

        for ($i = $next; $i < $limit; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_OPEN_PARENTHESIS) {
                $end = $tokens[$i]['parenthesis_closer'] ?? null;

                return is_int($end) && $end < $limit ? $end : null;
            }

            if (!in_array($code, self::CLASS_REFERENCE, true) && !isset(Tokens::EMPTY_TOKENS[$code])) {
                return null;
            }
        }

        return null;
    }
}
