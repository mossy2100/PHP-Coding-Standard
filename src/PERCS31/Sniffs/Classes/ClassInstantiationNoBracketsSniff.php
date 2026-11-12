<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Tokens\Collections;

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
        $end = TokenHelper::findNewExpressionEnd($phpcsFile, $newPtr, $closer);
        if ($end === null || $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($end + 1), null, true) !== $closer) {
            return;
        }

        $after = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closer + 1), null, true);
        if ($after === false || !isset(Collections::objectOperators()[$tokens[$after]['code']])) {
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
        FixerHelper::blankBetween($phpcsFile, ($stackPtr - 1), $newPtr);
        FixerHelper::blankBetween($phpcsFile, $end, ($closer + 1));

        $phpcsFile->fixer->endChangeset();
    }
}
