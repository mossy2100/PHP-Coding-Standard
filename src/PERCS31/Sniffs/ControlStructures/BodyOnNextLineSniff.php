<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\ControlStructures;

use PERCS31\Helpers\EmptyBodyHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires a control structure's body to start on the line after its opening brace, allowing an empty body as `{}`.
 *
 * PER Coding Style 3.1, section 5, says the body MUST be on the next line after the opening brace. It says nothing
 * about an empty body, which has nothing to put on the next line, so `catch (Exception $e) {}` is allowed, as `{}` is
 * for an empty class, function or closure (4, 4.4, 7).
 *
 * This replaces the NewlineAfterOpenBrace check of Squiz.ControlStructures.ControlSignature, which reports `{}`. That
 * check is in the middle of the Squiz sniff's process(), with no property to change it, so a subclass can't skip it for
 * one case, and the PERCS31 ruleset excludes its code. The check here is copied from PHP_CodeSniffer 4.0.4, with its
 * message and code, and changes one thing: a `}` straight after the `{` isn't reported. After a PHP_CodeSniffer
 * upgrade, compare it with the original.
 */
class BodyOnNextLineSniff implements Sniff
{
    public const string CODE_NEWLINE_AFTER_OPEN_BRACE = 'NewlineAfterOpenBrace';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return EmptyBodyHelper::CONTROL_STRUCTURE_TOKENS;
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
        $opener = $tokens[$stackPtr]['scope_opener'] ?? null;
        $closer = $tokens[$stackPtr]['scope_closer'] ?? null;
        if (!is_int($opener)) {
            return;
        }

        // An empty body written as `{}`.
        if ($tokens[$opener]['code'] === T_OPEN_CURLY_BRACKET && $closer === ($opener + 1)) {
            return;
        }

        // The first code after the brace, or a comment on a later line. A comment on the brace's line is skipped.
        for ($next = ($opener + 1); $next < $phpcsFile->numTokens; $next++) {
            $code = $tokens[$next]['code'];
            if ($code === T_WHITESPACE || ($code === T_INLINE_HTML && trim($tokens[$next]['content']) === '')) {
                continue;
            }

            $isOnBraceLine = $tokens[$next]['line'] === $tokens[$opener]['line'];
            if ($isOnBraceLine && (isset(Tokens::EMPTY_TOKENS[$code]) || $code === T_CLOSE_TAG)) {
                continue;
            }

            break;
        }

        if ($next === $phpcsFile->numTokens || $tokens[$next]['line'] !== $tokens[$opener]['line']) {
            return;
        }

        $error = 'Newline required after opening brace';
        if (!$phpcsFile->addFixableError($error, $opener, self::CODE_NEWLINE_AFTER_OPEN_BRACE)) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = ($opener + 1); $i < $next; $i++) {
            if (trim($tokens[$i]['content']) !== '') {
                break;
            }

            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContent($opener, $phpcsFile->eolChar);
        $phpcsFile->fixer->endChangeset();
    }
}
