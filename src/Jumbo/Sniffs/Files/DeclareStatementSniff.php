<?php
/**
 * Checks the format of the declare statements.
 *
 * This is a fork of PSR12.Files.DeclareStatement from PHP_CodeSniffer 4.0.4, which assumes a single directive with an
 * integer value. So it rejects a declare statement with several directives, such as `declare(ticks=1,strict_types=1);`,
 * or with a string value, such as `declare(encoding='UTF-8');`, however it's spaced, and its later checks, such as for
 * a block declare's braces, then look in the wrong place. PER Coding Style 3.1, section 3, only says there MUST NOT be
 * any spaces inside the parentheses. This fork checks each directive in turn, with any value, so every check works for
 * every declare statement.
 *
 * Apart from the namespace, every change from upstream is marked with a "Jumbo:" comment, so a diff against the
 * upstream file shows only the fix. To update the fork to a new PHP_CodeSniffer release, copy the new upstream file,
 * re-apply the marked changes, and run the tests. Remove the fork once upstream handles several directives.
 *
 * @author    Sertan Danis <sdanis@squiz.net>
 * @copyright 2006-2023 Squiz Pty Ltd (ABN 77 084 670 600)
 * @copyright 2023 PHPCSStandards and contributors
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/HEAD/licence.txt BSD Licence
 */

namespace Jumbo\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class DeclareStatementSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_DECLARE];
    }


    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in
     *                                               the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, int $stackPtr)
    {
        // Allow a byte-order mark.
        $tokens = $phpcsFile->getTokens();

        // There should be no space between declare keyword and opening parenthesis.
        $parenthesis = ($stackPtr + 1);
        if ($tokens[($stackPtr + 1)]['type'] !== 'T_OPEN_PARENTHESIS') {
            $parenthesis = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
            $error       = 'Expected no space between declare keyword and opening parenthesis in a declare statement';

            if ($tokens[$parenthesis]['type'] === 'T_OPEN_PARENTHESIS') {
                $fix = $phpcsFile->addFixableError($error, $stackPtr, 'SpaceFoundAfterDeclare');

                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken(($stackPtr + 1), '');
                }
            } else {
                $phpcsFile->addError($error, $parenthesis, 'SpaceFoundAfterDeclare');
                $parenthesis = $phpcsFile->findNext(T_OPEN_PARENTHESIS, ($parenthesis + 1));
            }
        }

        // Jumbo: check each directive in turn, separated by commas, with any value, not just one with an integer value.
        if ($parenthesis === false || isset($tokens[$parenthesis]['parenthesis_closer']) === false) {
            // Live coding / parse error.
            return;
        }

        $closer = $tokens[$parenthesis]['parenthesis_closer'];
        $before = $parenthesis;
        while ($before < $closer) {
            $after = $phpcsFile->findNext(T_COMMA, ($before + 1), $closer);
            if ($after === false) {
                $after = $closer;
            }

            $this->processDirective($phpcsFile, $before, $after);
            $before = $after;
        }

        $parenthesis = $closer;
        // Jumbo: end of change.

        // Check for semicolon.
        $curlyBracket = false;
        if ($tokens[($parenthesis + 1)]['type'] !== 'T_SEMICOLON') {
            $token = $phpcsFile->findNext(T_WHITESPACE, ($parenthesis + 1), null, true);

            if ($tokens[$token]['type'] === 'T_OPEN_CURLY_BRACKET') {
                // Block declaration.
                $curlyBracket = $token;
            } elseif ($tokens[$token]['type'] === 'T_SEMICOLON') {
                $error = 'Expected no space between the closing parenthesis and the semicolon in a declare statement';
                $fix   = $phpcsFile->addFixableError($error, $parenthesis, 'SpaceFoundBeforeSemicolon');
                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken(($parenthesis + 1), '');
                }
            } elseif ($tokens[$token]['type'] === 'T_CLOSE_TAG') {
                if ($tokens[($parenthesis)]['line'] !== $tokens[$token]['line']) {
                    // Close tag must be on the same line..
                    $error = 'The close tag must be on the same line as the declare statement';
                    $fix   = $phpcsFile->addFixableError($error, $parenthesis, 'CloseTagOnNewLine');
                    if ($fix === true) {
                        $phpcsFile->fixer->replaceToken(($parenthesis + 1), ' ');
                    }
                }
            } else {
                $error = 'Expected no space between the closing parenthesis and the semicolon in a declare statement';
                $phpcsFile->addError($error, $parenthesis, 'SpaceFoundBeforeSemicolon');

                // See if there is a semicolon or curly bracket after this token.
                $token = $phpcsFile->findNext([T_WHITESPACE, T_COMMENT], ($token + 1), null, true);
                if ($tokens[$token]['type'] === 'T_OPEN_CURLY_BRACKET') {
                    $curlyBracket = $token;
                }
            }
        }

        if ($curlyBracket !== false) {
            $prevToken = $phpcsFile->findPrevious(T_WHITESPACE, ($curlyBracket - 1), null, true);
            $error     = 'Expected one space between closing parenthesis and opening curly bracket in a declare statement';

            // The opening curly bracket must on the same line with a single space between closing bracket.
            if ($tokens[$prevToken]['type'] !== 'T_CLOSE_PARENTHESIS') {
                $phpcsFile->addError($error, $curlyBracket, 'ExtraSpaceFoundAfterBracket');
            } elseif ($phpcsFile->getTokensAsString(($prevToken + 1), ($curlyBracket - $prevToken - 1)) !== ' ') {
                $fix = $phpcsFile->addFixableError($error, $curlyBracket, 'ExtraSpaceFoundAfterBracket');
                if ($fix === true) {
                    // Jumbo: remove whatever is between them, then add one space. The original's loop started past
                    // the brace when there was no space at all, as in `){`, so it never ended.
                    $phpcsFile->fixer->beginChangeset();
                    for ($i = ($prevToken + 1); $i < $curlyBracket; $i++) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }

                    $phpcsFile->fixer->addContentBefore($curlyBracket, ' ');
                    $phpcsFile->fixer->endChangeset();
                    // Jumbo: end of change.
                }
            }

            $closeCurlyBracket = $tokens[$curlyBracket]['bracket_closer'];

            $prevToken = $phpcsFile->findPrevious(T_WHITESPACE, ($closeCurlyBracket - 1), null, true);
            $nextToken = $phpcsFile->findNext([T_WHITESPACE, T_COMMENT], ($closeCurlyBracket + 1), null, true);
            $line      = $tokens[$closeCurlyBracket]['line'];

            // The closing curly bracket must be on a new line.
            if ($tokens[$prevToken]['line'] === $line || $tokens[$nextToken]['line'] === $line) {
                if ($tokens[$prevToken]['line'] === $line) {
                    $error = 'The closing curly bracket of a declare statement must be on a new line';
                    $fix   = $phpcsFile->addFixableError($error, $prevToken, 'CurlyBracketNotOnNewLine');
                    if ($fix === true) {
                        $phpcsFile->fixer->addNewline($prevToken);
                    }
                }
            }

            // Closing curly bracket must align with the declare keyword.
            if ($tokens[$stackPtr]['column'] !== $tokens[$closeCurlyBracket]['column']) {
                $error = 'The closing curly bracket of a declare statements must be aligned with the declare keyword';

                $fix = $phpcsFile->addFixableError($error, $closeCurlyBracket, 'CloseBracketNotAligned');
                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken(($closeCurlyBracket - 1), str_repeat(' ', ($tokens[$stackPtr]['column'] - 1)));
                }
            }

            // The open curly bracket must be the last code on the line.
            $token = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($curlyBracket + 1), null, true);
            if ($tokens[$curlyBracket]['line'] === $tokens[$token]['line']) {
                $error = 'The open curly bracket of a declare statement must be the last code on the line';
                $fix   = $phpcsFile->addFixableError($error, $token, 'CodeFoundAfterCurlyBracket');

                if ($fix === true) {
                    $phpcsFile->fixer->beginChangeset();
                    $prevToken = $phpcsFile->findPrevious(T_WHITESPACE, ($token - 1), null, true);

                    for ($i = ($prevToken + 1); $i < $token; $i++) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }

                    $phpcsFile->fixer->addNewlineBefore($token);

                    $phpcsFile->fixer->endChangeset();
                }
            }
        }
    }

    /**
     * Jumbo: checks one directive, and the spacing around it.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $before    The position of the opening parenthesis or comma before the
     *                                               directive.
     * @param int                         $after     The position of the comma or closing parenthesis after it.
     *
     * @return void
     */
    private function processDirective(File $phpcsFile, int $before, int $after)
    {
        $tokens    = $phpcsFile->getTokens();
        $directive = $phpcsFile->findNext(T_WHITESPACE, ($before + 1), $after, true);
        $equals    = ($directive === false) ? false : $phpcsFile->findNext(T_WHITESPACE, ($directive + 1), $after, true);
        $value     = ($equals === false) ? false : $phpcsFile->findNext(T_WHITESPACE, ($equals + 1), $after, true);
        if ($value === false || $tokens[$directive]['code'] !== T_STRING || $tokens[$equals]['code'] !== T_EQUAL) {
            // Live coding / parse error.
            return;
        }

        $opener = ($tokens[$before]['code'] === T_COMMA) ? 'comma' : 'opening parenthesis';
        $this->checkNoSpace(
            $phpcsFile,
            $before,
            $directive,
            'Expected no space between '.$opener.' and directive in a declare statement',
            'SpaceFoundBeforeDirective'
        );

        // The directive must be in lowercase.
        if ($tokens[$directive]['content'] !== strtolower($tokens[$directive]['content'])) {
            $error = 'The directive of a declare statement must be in lowercase';
            $fix   = $phpcsFile->addFixableError($error, $directive, 'DirectiveNotLowercase');
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken($directive, strtolower($tokens[$directive]['content']));
            }
        }

        $this->checkNoSpace(
            $phpcsFile,
            $directive,
            $equals,
            'Expected no space between directive and the equals sign in a declare statement',
            'SpaceFoundAfterDirective'
        );
        $this->checkNoSpace(
            $phpcsFile,
            $equals,
            $value,
            'Expected no space between equal sign and the directive value in a declare statement',
            'SpaceFoundBeforeDirectiveValue'
        );

        $closer = ($tokens[$after]['code'] === T_COMMA) ? 'comma' : 'closing parenthesis';
        $this->checkNoSpace(
            $phpcsFile,
            $value,
            $after,
            'Expected no space between the directive value and '.$closer.' in a declare statement',
            'SpaceFoundAfterDirectiveValue'
        );

    }//end processDirective()


    /**
     * Jumbo: reports whitespace between two tokens, and fixes it by removing it.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $from      The position of the token before the gap.
     * @param int                         $to        The position of the token after the gap.
     * @param string                      $error     The error message.
     * @param string                      $code      The error code.
     *
     * @return void
     */
    private function checkNoSpace(File $phpcsFile, int $from, int $to, string $error, string $code)
    {
        if (($from + 1) === $to) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        for ($i = ($from + 1); $i < $to; $i++) {
            if ($tokens[$i]['code'] !== T_WHITESPACE) {
                // A comment in the gap; leave it to a person.
                $phpcsFile->addError($error, $to, $code);
                return;
            }
        }

        $fix = $phpcsFile->addFixableError($error, $to, $code);
        if ($fix === true) {
            $phpcsFile->fixer->beginChangeset();
            for ($i = ($from + 1); $i < $to; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }

            $phpcsFile->fixer->endChangeset();
        }

    }//end checkNoSpace()
}
