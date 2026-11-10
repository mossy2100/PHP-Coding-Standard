<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Classes\ClosingBraceSniff as Psr12Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Scopes;

/**
 * Disallows a comment or statement after the closing brace of a class or method, including an anonymous class, and
 * after a property's hook list or hook body.
 *
 * PER Coding Style 3.1, section 4, says any closing brace MUST NOT be followed by any comment or statement on the same
 * line, and that the term "class" refers to all classes. Section 4 covers classes, properties and methods, so a
 * function outside a class isn't checked by default. Set checkFunctions to check it too, as the Jumbo standard does.
 * PSR12.Classes.ClosingBrace checks classes, interfaces, traits, enums and every function, but not an anonymous class,
 * and has no property to add one or leave functions out. So this sniff extends it.
 *
 * An anonymous class's `}` is part of an expression, so what completes it may follow, as in `};`, `});` or `})->x;`.
 * Only a comment after it on the same line is reported, as in `}; // Comment` or `}, // Comment`. A statement after
 * the `;` is left to PERCS31.Formatting.DisallowMultipleStatements. A phpcs annotation is allowed, as it only works on
 * that line. It isn't fixable, as where the comment should go is left to a person.
 *
 * A property hook's braces are covered too, as section 4 covers properties: the hook list's `}` and each hook body's.
 * As with an anonymous class, only a comment after one is reported. What follows a promoted property's hook list, a
 * `,` or `)`, is part of the parameter list, and PERCS31.Classes.PropertyHookLayout already puts each hook on its own
 * line.
 */
class ClosingBraceSniff extends Psr12Sniff
{
    public const string CODE_STATEMENT_AFTER = 'StatementAfter';

    /**
     * Whether to check a function outside a class, interface, trait or enum. Section 4 is about classes, properties
     * and methods, so it's off by default.
     */
    public bool $checkFunctions = false;

    /**
     * Returns an array of tokens this test wants to listen for: the parent's, an anonymous class, and a variable, for a
     * property's hooks.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [...parent::register(), T_ANON_CLASS, T_VARIABLE];
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

        if (
            $tokens[$stackPtr]['code'] === T_FUNCTION
            && !$this->checkFunctions
            && !Scopes::isOOMethod($phpcsFile, $stackPtr)
        ) {
            return;
        }

        if ($tokens[$stackPtr]['code'] !== T_ANON_CLASS) {
            parent::process($phpcsFile, $stackPtr);
            return;
        }

        $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
        if (!is_int($closer)) {
            return;
        }

        $this->checkCommentAfter($phpcsFile, $closer);
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
                $this->checkCommentAfter($phpcsFile, $bodyCloser);
            }
        }

        $this->checkCommentAfter($phpcsFile, $closer);
    }

    /**
     * Report a comment after a closing brace on the same line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $closer The position of the closing brace.
     */
    private function checkCommentAfter(File $phpcsFile, int $closer): void
    {
        // A phpcs annotation only works on its line, so it's allowed there.
        $tokens = $phpcsFile->getTokens();
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
