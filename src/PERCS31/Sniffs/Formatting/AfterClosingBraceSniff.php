<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Formatting;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Disallows a statement after any closing brace on the same line, and a comment after the closing brace of a class,
 * method, function or property hook.
 *
 * PER Coding Style 3.1, section 4, says any closing brace MUST NOT be followed by any comment or statement on the same
 * line. It's read as two rules:
 * - No statement after any closing brace, as in `} run();` after an `if`. Section 2.3 allows one statement per line.
 * - No comment after the closing brace of a class, interface, trait, enum, method or function, or of a property's hook
 *   list or hook body, which are what section 4 is about. A comment is allowed after any other brace, as in
 *   `} // for $i`.
 *
 * What continues the brace's own statement isn't a new statement, so `} else {`, `} catch (Exception $e) {` and a
 * `do` loop's `} while ($a);` are allowed. So is `} else if {`, which PHP reads as `else` and `if`. The closing brace
 * of a closure, an anonymous class or a `match` isn't checked, as the rest of its expression follows it, as in `});`.
 * A statement after its `;` is reported by PERCS31.Formatting.DisallowMultipleStatements.
 *
 * This replaces PSR12.Classes.ClosingBrace, which checks classes and functions only, and reports a comment and a
 * statement under one code that can't be fixed.
 *
 * Also allowed after a brace:
 * - A phpcs annotation, as it only works on that line.
 * - A closing PHP tag.
 * - Another closing brace, which PERCS31.WhiteSpace.ScopeClosingBrace reports.
 * - The `,` or `)` after a promoted property's hook list, which is part of the parameter list.
 * - The next hook after a hook's body, which PERCS31.Classes.PropertyHookLayout reports.
 *
 * A block `declare` in the file header is skipped, as PERCS31.Files.FileHeader reports what follows it on the same
 * line.
 *
 * The fix moves the statement to the next line. A comment isn't moved, as where it should go is left to a person.
 * Neither is a statement with a comment before it, or the `;` of an empty statement, as in `class Foo {};`.
 */
class AfterClosingBraceSniff implements Sniff
{
    public const string CODE_STATEMENT_AFTER = 'StatementAfter';

    public const string CODE_COMMENT_AFTER = 'CommentAfter';

    /**
     * The declarations whose closing brace can't be followed by a comment.
     *
     * @var list<int|string>
     */
    private const array DECLARATIONS = [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION];

    /**
     * The other structures whose closing brace is checked, for a statement only.
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
        T_NAMESPACE,
        T_USE,
    ];

    /**
     * Tokens that can follow any closing brace on its line without starting a statement.
     *
     * @var list<int|string>
     */
    private const array ALLOWED_NEXT = [T_CLOSE_TAG, T_CLOSE_CURLY_BRACKET];

    /**
     * Tokens that continue a structure after its closing brace, and an empty statement.
     *
     * @var list<int|string>
     */
    private const array CONTINUATIONS = [T_ELSE, T_ELSEIF, T_CATCH, T_FINALLY, T_SEMICOLON];

    /**
     * Returns an array of tokens this test wants to listen for: a closing brace, and a variable, for a property's
     * hooks, whose braces have no owner in the token stack.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_CLOSE_CURLY_BRACKET, T_VARIABLE];
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
        if ($tokens[$stackPtr]['code'] === T_VARIABLE) {
            $this->processHooks($phpcsFile, $stackPtr);

            return;
        }

        $condition = $tokens[$stackPtr]['scope_condition'] ?? null;
        if (!is_int($condition)) {
            return;
        }

        $code = $tokens[$condition]['code'];
        if (in_array($code, self::DECLARATIONS, true)) {
            $this->checkAfter($phpcsFile, $stackPtr, true, self::ALLOWED_NEXT);

            return;
        }

        if (!in_array($code, self::STRUCTURES, true) || $this->isDeclareInHeader($phpcsFile, $condition)) {
            return;
        }

        $allowed = [...self::ALLOWED_NEXT, ...self::CONTINUATIONS];
        if ($code === T_DO) {
            $allowed[] = T_WHILE;
        }

        $this->checkAfter($phpcsFile, $stackPtr, false, $allowed);
    }

    /**
     * Check the closing braces of a property's hook list and its hook bodies.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $varPtr The position of a variable, which may be a property's.
     */
    private function processHooks(File $phpcsFile, int $varPtr): void
    {
        if (!PropertyHookHelper::isPropertyVariable($phpcsFile, $varPtr)) {
            return;
        }

        $hookList = PropertyHookHelper::findHookList($phpcsFile, $varPtr);
        if ($hookList === null) {
            return;
        }

        [$opener, $closer] = $hookList;
        $tokens = $phpcsFile->getTokens();
        foreach (PropertyHookHelper::parseHooks($phpcsFile, $opener, $closer) ?? [] as $hook) {
            $bodyOpener = $hook['bodyOpener'];
            $bodyCloser = $bodyOpener === null ? null : ($tokens[$bodyOpener]['bracket_closer'] ?? null);
            if (is_int($bodyCloser)) {
                // Only another hook can follow a hook's body, and PropertyHookLayout puts each on its own line.
                $this->checkAfter($phpcsFile, $bodyCloser, true, null);
            }
        }

        // What follows a promoted property's hook list, a `,` or `)`, is part of the parameter list.
        $this->checkAfter($phpcsFile, $closer, true, [...self::ALLOWED_NEXT, T_COMMA, T_CLOSE_PARENTHESIS]);
    }

    /**
     * Report a comment or a statement after a closing brace on the same line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $closer The position of the closing brace.
     * @param bool $isCommentBanned Whether a comment after the brace is reported.
     * @param list<int|string>|null $allowed The tokens that can follow the brace, or null to check for a comment only.
     */
    private function checkAfter(File $phpcsFile, int $closer, bool $isCommentBanned, ?array $allowed): void
    {
        $tokens = $phpcsFile->getTokens();
        $line = $tokens[$closer]['line'];

        // A phpcs annotation only works on its line, so it's allowed there. A comment after a promoted property's
        // comma is still after its hook list's brace.
        $comments = array_diff_key(Tokens::COMMENT_TOKENS, Tokens::PHPCS_ANNOTATION_TOKENS);
        $skipped = in_array(T_COMMA, ($allowed ?? []), true) ? [T_WHITESPACE, T_COMMA] : [T_WHITESPACE];
        $comment = $phpcsFile->findNext($skipped, ($closer + 1), null, true);
        $hasComment = $comment !== false
            && $tokens[$comment]['line'] === $line
            && isset($comments[$tokens[$comment]['code']]);
        if ($hasComment && $isCommentBanned) {
            assert(is_int($comment));
            $phpcsFile->addError(
                'A comment must not follow the closing brace of a class, function or property hook on the same line',
                $comment,
                self::CODE_COMMENT_AFTER,
            );
        }

        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closer + 1), null, true);
        if (
            $allowed === null
            || $next === false
            || $tokens[$next]['line'] !== $line
            || in_array($tokens[$next]['code'], $allowed, true)
        ) {
            return;
        }

        // An empty statement isn't moved, as it should be removed.
        $isFixable = $tokens[$next]['code'] !== T_SEMICOLON
            && $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($closer + 1), $next) === false;
        $error = 'A statement must not follow a closing brace on the same line';
        if (!FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $next, self::CODE_STATEMENT_AFTER)) {
            return;
        }

        $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $closer, true);
        assert(is_int($firstOnLine));
        $phpcsFile->fixer->beginChangeset();
        FixerHelper::blankBetween($phpcsFile, $closer, $next);

        $indent = str_repeat(' ', ($tokens[$firstOnLine]['column'] - 1));
        $phpcsFile->fixer->addContent($closer, $phpcsFile->eolChar . $indent);
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
