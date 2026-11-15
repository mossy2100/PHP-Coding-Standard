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
 * Checks the parentheses of a class instantiation: none around one that's immediately dereferenced, as in
 * `(new Foo())->bar()`, and an argument list after `new (expression)`, as in `new (className())()`.
 *
 * Unnecessary parentheses: PER Coding Style 3.1, section 4, says that when accessing a class member immediately after
 * instantiating a class, the instantiation SHOULD NOT be wrapped in parentheses. PHP 8.4 allows `new Foo()->bar()`.
 * It's a SHOULD, so this is a warning.
 *
 * Only parentheses holding exactly one `new` expression, followed by `->`, `?->` or `::`, are reported. So
 * `(new Foo() ?: $default)->bar()` is left alone, as removing them would change its meaning, and so is a function
 * call's argument list, as in `new self(new Foo())->bar()` or `make(new Foo())->bar()`. That includes a call through
 * an array or a string, as in `[$factory, 'make'](new Foo())->bar()` or `'make'(new Foo())->bar()`. A `new` without
 * an argument list, such as `(new Foo)->bar()`, is left until PSR12.Classes.ClassInstantiation has added the `()`, as
 * PHP needs them.
 *
 * A keyword can come straight before the parentheses, as in `return(new Foo())->bar()`. The fix puts a space in their
 * place there, giving `return new Foo()->bar()`. `include` and `require` are keywords too, not functions, so the
 * parentheses in `include(new Foo())->path()` group the expression, and are reported.
 *
 * The parentheses in `clone(new Foo())->bar` group the expression too, but PERCS31.PHP.RequireCloneParentheses reports
 * them, as its fix has to keep a pair for `clone`.
 *
 * SlevomatCodingStandard.PHP.UselessParentheses has a similar check (enableCheckAroundNew), but it misses `::` and
 * removes the parentheses from `(new Foo() ?: new Bar())->baz()`, changing its meaning.
 *
 * A missing argument list: section 4 also says that when instantiating a new class, parentheses MUST always be
 * present, even when there are no arguments passed to the constructor. PSR12.Classes.ClassInstantiation checks that,
 * but it stops at the first `(` after `new`, and takes it for the argument list. In `new (className())`, the
 * parentheses hold the expression that gives the class, and there's no argument list, so it passes. This sniff reports
 * it, as MissingParentheses, an error. It only looks at a `new` followed by `(`, and leaves every other form to the
 * PSR-12 sniff. SlevomatCodingStandard.ControlStructures.NewWithParentheses reports it too, but its fix turns
 * `new Foo instanceof Bar` into `new Foo instanceof Bar()`, which PHP rejects.
 */
class ClassInstantiationParenthesesSniff implements Sniff
{
    public const string CODE_UNNECESSARY = 'NewWithUnnecessaryParentheses';

    public const string CODE_MISSING_PARENTHESES = 'MissingParentheses';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_OPEN_PARENTHESIS, T_NEW];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        if ($phpcsFile->getTokens()[$stackPtr]['code'] === T_NEW) {
            $this->checkDynamicClass($phpcsFile, $stackPtr);
        } else {
            $this->checkGrouping($phpcsFile, $stackPtr);
        }
    }

    /**
     * Warn about parentheses that only group a `new` expression that's then dereferenced, and fix it by removing them.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the opening parenthesis.
     */
    private function checkGrouping(File $phpcsFile, int $stackPtr): void
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

        // A call's argument list, as in `foo(new Foo())->bar()`, or the class expression of `new (...)`. The
        // parentheses after `include` or `require` group the expression. Those after `clone` are left to
        // PERCS31.PHP.RequireCloneParentheses.
        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($stackPtr - 1), null, true);
        $previousCode = $previous === false ? null : $tokens[$previous]['code'];
        $isCall = !isset(Tokens::INCLUDE_TOKENS[$previousCode])
            && (TokenHelper::isArgumentListOpener($phpcsFile, $stackPtr)
                || $previousCode === T_NEW
                || isset(Tokens::FUNCTION_NAME_TOKENS[$previousCode]));
        if ($isCall) {
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

        // A word straight before the parenthesis, as in `return(new Foo())`, needs a space in its place.
        $before = $tokens[($stackPtr - 1)];
        $isGlued = $before['code'] !== T_WHITESPACE && preg_match('/\w$/', $before['content']) === 1;

        $phpcsFile->fixer->beginChangeset();
        FixerHelper::blankBetween($phpcsFile, ($stackPtr - 1), $newPtr);
        if ($isGlued) {
            $phpcsFile->fixer->addContent(($stackPtr - 1), ' ');
        }

        FixerHelper::blankBetween($phpcsFile, $end, ($closer + 1));

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Report `new (expression)` with no argument list after it, and fix it by adding `()`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of `new`.
     */
    private function checkDynamicClass(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if ($opener === false || $tokens[$opener]['code'] !== T_OPEN_PARENTHESIS) {
            return;
        }

        // The parentheses hold the class, and the argument list follows them.
        $closer = $tokens[$opener]['parenthesis_closer'] ?? null;
        if (!is_int($closer)) {
            return;
        }

        $after = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closer + 1), null, true);
        if ($after !== false && $tokens[$after]['code'] === T_OPEN_PARENTHESIS) {
            return;
        }

        $error = 'Parentheses must be used when instantiating a new class';
        if ($phpcsFile->addFixableError($error, $stackPtr, self::CODE_MISSING_PARENTHESES)) {
            $phpcsFile->fixer->addContent($closer, '()');
        }
    }
}
