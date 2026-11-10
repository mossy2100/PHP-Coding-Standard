<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Classes\ClosingBraceSniff as Psr12Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Disallows a comment or statement after the closing brace of a class, method or function, and after a property's
 * hook list or hook body.
 *
 * PER Coding Style 3.1, section 4, says any closing brace MUST NOT be followed by any comment or statement on the same
 * line. PSR12.Classes.ClosingBrace checks classes, interfaces, traits, enums and every function, but not a property
 * hook, so this sniff extends it. An anonymous class's, a closure's and a control structure's closing brace aren't
 * checked: each is part of a larger expression or statement, and what completes it, such as `};`, follows.
 *
 * A property hook's braces are covered, as section 4 covers properties: the hook list's `}` and each hook body's. Only
 * a comment after one is reported. A phpcs annotation is allowed, as it only works on that line. It isn't fixable, as
 * where the comment should go is left to a person. What follows a promoted property's hook list, a `,` or `)`, is part
 * of the parameter list, and PERCS31.Classes.PropertyHookLayout already puts each hook on its own line.
 */
class ClosingBraceSniff extends Psr12Sniff
{
    public const string CODE_STATEMENT_AFTER = 'StatementAfter';

    /**
     * Returns an array of tokens this test wants to listen for: the parent's, and a variable, for a property's hooks.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [...parent::register(), T_VARIABLE];
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

        parent::process($phpcsFile, $stackPtr);
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
