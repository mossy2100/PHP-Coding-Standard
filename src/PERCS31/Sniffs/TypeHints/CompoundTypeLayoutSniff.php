<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\TypeHints;

use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a compound type split across lines to have one type, or one union segment, per line.
 *
 * PER Coding Style 3.1, section 2.5, says that if a compound type is split across lines:
 * - If it contains only unions or only intersections, each line MUST have a single type.
 * - If it contains both, each line MUST have a single union segment, and all intersections in a segment MUST be on the
 *   same line.
 * - The symbol it's split on MUST be at the start of each line.
 *
 * PERCS31.TypeHints.CompoundTypeOperatorSpacing checks that last rule. This sniff checks the other two. A type is split
 * if any `|` or `&` outside parentheses starts a line. Then every one of them must start a line, and the fix breaks the
 * line before each that doesn't. A `&` inside a DNF type's parentheses must never start a line, and the fix joins it to
 * the line before.
 *
 * It also covers a `set` hook's parameter type, e.g. `set(int|string $value)`, whose symbols and parentheses
 * PHP_CodeSniffer gives other tokens, as it doesn't know about hooks.
 */
class CompoundTypeLayoutSniff implements Sniff
{
    public const string CODE_MULTIPLE_TYPES_ON_LINE = 'MultipleTypesOnLine';

    public const string CODE_SPLIT_INTERSECTION = 'SplitIntersection';

    /**
     * The tokens that can make up a type, besides whitespace and comments.
     *
     * @var list<int|string>
     */
    private const array TYPE_TOKENS = [
        T_STRING,
        T_NAME_QUALIFIED,
        T_NAME_FULLY_QUALIFIED,
        T_NAME_RELATIVE,
        T_NULL,
        T_FALSE,
        T_TRUE,
        T_SELF,
        T_STATIC,
        T_PARENT,
        T_CALLABLE,
        T_TYPE_UNION,
        T_TYPE_INTERSECTION,
        T_TYPE_OPEN_PARENTHESIS,
        T_TYPE_CLOSE_PARENTHESIS,
    ];

    /**
     * The tokens PHP_CodeSniffer gives a type's parts in a `set` hook's parameter, which it reads as an expression.
     *
     * @var list<int|string>
     */
    private const array HOOK_TYPE_TOKENS = [
        T_BITWISE_OR,
        T_BITWISE_AND,
        T_OPEN_PARENTHESIS,
        T_CLOSE_PARENTHESIS,
        T_ARRAY,
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_TYPE_UNION, T_TYPE_INTERSECTION, T_BITWISE_OR, T_BITWISE_AND];
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
        $code = $tokens[$stackPtr]['code'];
        $isHook = $code === T_BITWISE_OR || $code === T_BITWISE_AND;
        if ($isHook && !PropertyHookHelper::isHookParameterType($phpcsFile, $stackPtr)) {
            return;
        }

        $symbols = $this->findSymbols($phpcsFile, $stackPtr, $isHook);

        // Check each type once, from its first symbol.
        if ($symbols[0] !== $stackPtr) {
            return;
        }

        // Split the symbols into those outside parentheses, and those inside a DNF type's parentheses.
        $topLevel = [];
        $grouped = [];
        $depth = $this->isInsideGroup($phpcsFile, $stackPtr, $isHook) ? 1 : 0;
        $last = $symbols[(count($symbols) - 1)];
        for ($i = $stackPtr; $i <= $last; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_TYPE_OPEN_PARENTHESIS || $code === T_OPEN_PARENTHESIS) {
                $depth++;
            } elseif ($code === T_TYPE_CLOSE_PARENTHESIS || $code === T_CLOSE_PARENTHESIS) {
                $depth--;
            } elseif ($this->isSymbol($code)) {
                if ($depth === 0) {
                    $topLevel[] = $i;
                } else {
                    $grouped[] = $i;
                }
            }
        }

        foreach ($grouped as $symbol) {
            if (LayoutHelper::startsLine($phpcsFile, $symbol)) {
                $this->reportSplitIntersection($phpcsFile, $symbol);
            }
        }

        $isSplit = false;
        foreach ($topLevel as $symbol) {
            $isSplit = $isSplit || LayoutHelper::startsLine($phpcsFile, $symbol);
        }

        if (!$isSplit) {
            return;
        }

        $error = $grouped === []
            ? 'A compound type split across lines must have a single type on each line'
            : 'A compound type split across lines must have a single union segment on each line';
        foreach ($topLevel as $symbol) {
            if (!LayoutHelper::startsLine($phpcsFile, $symbol)) {
                $this->reportMultipleTypesOnLine($phpcsFile, $symbol, $error);
            }
        }
    }

    /**
     * Check whether a type's first symbol is inside a DNF type's parentheses, as in `(A&B)|C`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the type's first symbol.
     * @param bool $isHook Whether the symbol is in a `set` hook's parameter type.
     * @return bool True if a group is open at the symbol.
     */
    private function isInsideGroup(File $phpcsFile, int $stackPtr, bool $isHook): bool
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = ($stackPtr - 1); $i >= 0 && $this->isTypeToken($tokens[$i]['code'], $isHook); $i--) {
            if ($tokens[$i]['code'] === T_TYPE_OPEN_PARENTHESIS) {
                return true;
            }

            if ($tokens[$i]['code'] === T_OPEN_PARENTHESIS) {
                // In a hook's parameter, the list's own `(` follows the name `set`. A group's doesn't.
                $before = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($i - 1), null, true);

                return $before !== false && $tokens[$before]['code'] !== T_STRING;
            }
        }

        return false;
    }

    /**
     * Find the `|` and `&` symbols of the type a symbol belongs to.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of a symbol.
     * @param bool $isHook Whether the symbol is in a `set` hook's parameter type.
     * @return non-empty-list<int> The positions of the symbols, in order.
     */
    private function findSymbols(File $phpcsFile, int $stackPtr, bool $isHook): array
    {
        $tokens = $phpcsFile->getTokens();
        $start = $stackPtr;
        while ($start > 0 && $this->isTypeToken($tokens[($start - 1)]['code'], $isHook)) {
            $start--;
        }

        $symbols = [];
        for ($i = $start; isset($tokens[$i]) && $this->isTypeToken($tokens[$i]['code'], $isHook); $i++) {
            if ($this->isSymbol($tokens[$i]['code'])) {
                $symbols[] = $i;
            }
        }

        return $symbols === [] ? [$stackPtr] : $symbols;
    }

    /**
     * Check whether a token can be part of a type.
     *
     * @param int|string $code The token's code.
     * @param bool $isHook Whether the type is a `set` hook's parameter type.
     */
    private function isTypeToken(int|string $code, bool $isHook): bool
    {
        return in_array($code, self::TYPE_TOKENS, true)
            || isset(Tokens::EMPTY_TOKENS[$code])
            || ($isHook && in_array($code, self::HOOK_TYPE_TOKENS, true));
    }

    /**
     * Check whether a token is a `|` or `&` in a type. The process() method has already ruled out any bitwise operator
     * that isn't one.
     *
     * @param int|string $code The token's code.
     */
    private function isSymbol(int|string $code): bool
    {
        return in_array($code, [T_TYPE_UNION, T_TYPE_INTERSECTION, T_BITWISE_OR, T_BITWISE_AND], true);
    }

    /**
     * Report a `&` inside parentheses that starts a line, and fix it by joining it to the line before.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $symbol The position of the symbol.
     */
    private function reportSplitIntersection(File $phpcsFile, int $symbol): void
    {
        $error = 'All intersections in a union segment must be on the same line';
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($symbol - 1), null, true);
        $prevContent = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($symbol - 1), null, true);
        if ($prev === false || $prev !== $prevContent) {
            // A comment before the symbol would end up inside the type's line, so leave that to a person.
            $phpcsFile->addError($error, $symbol, self::CODE_SPLIT_INTERSECTION);
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $symbol, self::CODE_SPLIT_INTERSECTION);
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($prev + 1); $i < $symbol; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Report a `|` or `&` outside parentheses that doesn't start a line, and fix it by breaking the line before it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $symbol The position of the symbol.
     * @param string $error The message.
     */
    private function reportMultipleTypesOnLine(File $phpcsFile, int $symbol, string $error): void
    {
        $fix = $phpcsFile->addFixableError($error, $symbol, self::CODE_MULTIPLE_TYPES_ON_LINE);
        if (!$fix) {
            return;
        }

        // The new line takes the indentation of the line the symbol is on.
        $tokens = $phpcsFile->getTokens();
        $break = $phpcsFile->eolChar . str_repeat(' ', LayoutHelper::lineIndent($phpcsFile, $symbol));

        if ($tokens[($symbol - 1)]['code'] === T_WHITESPACE) {
            $phpcsFile->fixer->replaceToken(($symbol - 1), $break);
        } else {
            $phpcsFile->fixer->addContentBefore($symbol, $break);
        }
    }
}
