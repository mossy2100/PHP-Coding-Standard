<?php
/**
 * Requires a multi-line PHP block in a file with markup to have its tags on their own lines, level with its code.
 *
 * This is a fork of Squiz.PHP.EmbeddedPhp from PHP_CodeSniffer 4.0.4, as the code to change is in private methods, so a
 * subclass can't reach it. PER Coding Style 3.1 doesn't set this layout, so it's an extra rule, in Jumbo only. Each
 * change from upstream is marked with a "Jumbo:" comment:
 * - A file's first opening tag is only skipped if nothing comes before it, so a file with one block is checked.
 * - A tag on one line isn't checked at all, as PER's own <?php declare(strict_types=1) ?> has no semicolon.
 * - A short echo tag over several lines is reported, with no fix.
 * - A blank line just inside the tags is allowed.
 * - Every statement directly in the block is checked for too much indent, not just the first.
 * - A closing tag on its own line must be level with the opening tag. PERCS31.WhiteSpace.ScopeIndent doesn't check it.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2023 Squiz Pty Ltd (ABN 77 084 670 600)
 * @copyright 2023 PHPCSStandards and contributors
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/HEAD/licence.txt BSD Licence
 */

namespace Jumbo\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class EmbeddedPhpSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [
            T_OPEN_TAG,
            T_OPEN_TAG_WITH_ECHO,
        ];
    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in the
     *                                               stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, int $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $closeTag = $phpcsFile->findNext(T_CLOSE_TAG, $stackPtr);

        // Jumbo: a short echo tag isn't a block, so it can't be laid out as one. Over several lines, it's reported.
        if ($tokens[$stackPtr]['code'] === T_OPEN_TAG_WITH_ECHO) {
            if ($closeTag !== false && $tokens[$stackPtr]['line'] !== $tokens[$closeTag]['line']) {
                $phpcsFile->addError('A short echo tag must be on one line', $stackPtr, 'MultiLineEcho');
            }

            return;
        }

        // Jumbo: a tag on one line isn't checked.
        if ($closeTag === false || $tokens[$stackPtr]['line'] !== $tokens[$closeTag]['line']) {
            $this->validateMultilineEmbeddedPhp($phpcsFile, $stackPtr, $closeTag);
        }
    }


    /**
     * Validates embedded PHP that exists on multiple lines.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile  The file being scanned.
     * @param int                         $stackPtr   The position of the current token in the
     *                                                stack passed in $tokens.
     * @param int|false                   $closingTag The position of the PHP close tag in the
     *                                                stack passed in $tokens.
     *
     * @return void
     */
    private function validateMultilineEmbeddedPhp(File $phpcsFile, int $stackPtr, $closingTag)
    {
        $tokens = $phpcsFile->getTokens();

        $prevTag = $phpcsFile->findPrevious($this->register(), ($stackPtr - 1));
        // Jumbo: skip the first open tag only if nothing comes before it, not if it's in markup.
        if ($prevTag === false && $stackPtr === 0) {
            // This is the first open tag.
            return;
        }

        $firstContent = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($firstContent === false) {
            // Unclosed PHP open tag at the end of a file. Nothing to do.
            return;
        }

        if ($closingTag !== false) {
            $firstContentAfterBlock = $phpcsFile->findNext(T_WHITESPACE, ($closingTag + 1), $phpcsFile->numTokens, true);
            if ($firstContentAfterBlock === false) {
                // Final closing tag. It will be handled elsewhere.
                return;
            }

            // We have an opening and a closing tag, that lie within other content.
            if ($firstContent === $closingTag) {
                $this->reportEmptyTagSet($phpcsFile, $stackPtr, $closingTag);
                return;
            }
        }

        if ($tokens[$firstContent]['line'] === $tokens[$stackPtr]['line']) {
            $error = 'Opening PHP tag must be on a line by itself';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'ContentAfterOpen');
            if ($fix === true) {
                $padding = $this->calculateLineIndent($phpcsFile, $stackPtr);

                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($stackPtr, rtrim($tokens[$stackPtr]['content']));
                $phpcsFile->fixer->addNewline($stackPtr);
                $phpcsFile->fixer->addContent($stackPtr, str_repeat(' ', $padding));

                if ($tokens[($stackPtr + 1)]['code'] === T_WHITESPACE) {
                    $phpcsFile->fixer->replaceToken(($stackPtr + 1), '');
                }

                $phpcsFile->fixer->endChangeset();
            }
        } else {
            // Check the indent of the first line, except if it is a scope closer.
            if (isset($tokens[$firstContent]['scope_closer']) === false
                || $tokens[$firstContent]['scope_closer'] !== $firstContent
            ) {
                // Jumbo: the check for a blank line at the top is removed.
                $indent        = $this->calculateLineIndent($phpcsFile, $stackPtr);
                $contentColumn = ($tokens[$firstContent]['column'] - 1);
                if ($contentColumn !== $indent) {
                    $error = 'First line of embedded PHP code must be indented %s spaces; %s found';
                    $data  = [
                        $indent,
                        $contentColumn,
                    ];
                    $fix   = $phpcsFile->addFixableError($error, $firstContent, 'Indent', $data);
                    if ($fix === true) {
                        $padding = str_repeat(' ', $indent);
                        if ($contentColumn === 0) {
                            $phpcsFile->fixer->addContentBefore($firstContent, $padding);
                        } else {
                            $phpcsFile->fixer->replaceToken(($firstContent - 1), $padding);
                        }
                    }
                }
            }
        }

        // Jumbo: every statement directly in the block is checked, not just the first.
        $this->checkStatementIndents($phpcsFile, $stackPtr, $firstContent, $closingTag);

        $lastContentBeforeBlock = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($tokens[$lastContentBeforeBlock]['line'] === $tokens[$stackPtr]['line']
            && (($tokens[$lastContentBeforeBlock]['code'] === T_INLINE_HTML
            && trim($tokens[$lastContentBeforeBlock]['content']) !== '')
            // Jumbo: a first tag can have only one token before it.
            || ($lastContentBeforeBlock > 0
            && $tokens[($lastContentBeforeBlock - 1)]['code'] !== T_INLINE_HTML
            && $tokens[($lastContentBeforeBlock - 1)]['line'] === $tokens[$stackPtr]['line']))
        ) {
            $error = 'Opening PHP tag must be on a line by itself';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'ContentBeforeOpen');
            if ($fix === true) {
                $padding = $this->calculateLineIndent($phpcsFile, $lastContentBeforeBlock);

                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->addContentBefore($stackPtr, $phpcsFile->eolChar . str_repeat(' ', $padding));

                // Make sure we don't leave trailing whitespace behind.
                if ($tokens[($stackPtr - 1)]['code'] === T_INLINE_HTML
                    && trim($tokens[($stackPtr - 1)]['content']) === ''
                ) {
                    $phpcsFile->fixer->replaceToken(($stackPtr - 1), '');
                }

                $phpcsFile->fixer->endChangeset();
            }
        } else {
            // Find the first token on the first non-empty line we find.
            // Jumbo: a first tag can have only one token before it.
            for ($first = max(0, ($lastContentBeforeBlock - 1)); $first > 0; $first--) {
                if ($tokens[$first]['line'] === $tokens[$stackPtr]['line']) {
                    continue;
                } elseif (trim($tokens[$first]['content']) !== '') {
                    $first = $phpcsFile->findFirstOnLine([], $first, true);
                    break;
                }
            }

            $expected  = $this->calculateLineIndent($phpcsFile, $first);
            $expected += 4;
            $found     = ($tokens[$stackPtr]['column'] - 1);
            if ($found > $expected) {
                $error = 'Opening PHP tag indent incorrect; expected no more than %s spaces but found %s';
                $data  = [
                    $expected,
                    $found,
                ];
                $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'OpenTagIndent', $data);
                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken(($stackPtr - 1), str_repeat(' ', $expected));
                }
            }
        }

        if ($closingTag === false) {
            return;
        }

        // Jumbo: a closing tag on its own line is level with the opening tag, unless it's in a scope the block opened.
        $this->checkClosingTagIndent($phpcsFile, $stackPtr, $closingTag);

        $lastContent            = $phpcsFile->findPrevious(T_WHITESPACE, ($closingTag - 1), ($stackPtr + 1), true);
        $firstContentAfterBlock = $phpcsFile->findNext(T_WHITESPACE, ($closingTag + 1), null, true);

        if ($tokens[$lastContent]['line'] === $tokens[$closingTag]['line']) {
            $error = 'Closing PHP tag must be on a line by itself';
            $fix   = $phpcsFile->addFixableError($error, $closingTag, 'ContentBeforeEnd');
            if ($fix === true) {
                // Calculate the indent for the close tag.
                // If the close tag is on the same line as the first content, re-use the indent
                // calculated for the first content line to prevent the indent being based on an
                // "old" indent, not the _new_ (fixed) indent.
                if ($tokens[$firstContent]['line'] === $tokens[$lastContent]['line']
                    && isset($indent) === true
                ) {
                    $closerIndent = $indent;
                } else {
                    $closerIndent = $this->calculateLineIndent($phpcsFile, $closingTag);
                }

                $phpcsFile->fixer->beginChangeset();

                if ($tokens[($closingTag - 1)]['code'] === T_WHITESPACE) {
                    $phpcsFile->fixer->replaceToken(($closingTag - 1), '');
                }

                $phpcsFile->fixer->addContentBefore($closingTag, str_repeat(' ', $closerIndent));
                $phpcsFile->fixer->addNewlineBefore($closingTag);
                $phpcsFile->fixer->endChangeset();
            }
        } elseif ($firstContentAfterBlock !== false
            && $tokens[$firstContentAfterBlock]['line'] === $tokens[$closingTag]['line']
        ) {
            $error = 'Closing PHP tag must be on a line by itself';
            $fix   = $phpcsFile->addFixableError($error, $closingTag, 'ContentAfterEnd');
            if ($fix === true) {
                $indent = $this->calculateLineIndent($phpcsFile, $closingTag);
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->addNewline($closingTag);
                $phpcsFile->fixer->addContent($closingTag, str_repeat(' ', $indent));

                if ($tokens[$firstContentAfterBlock]['code'] === T_INLINE_HTML) {
                    $trimmedHtmlContent = ltrim($tokens[$firstContentAfterBlock]['content']);
                    if ($trimmedHtmlContent === '') {
                        // HTML token contains only whitespace and the next token after is PHP, not HTML, so remove the whitespace.
                        $phpcsFile->fixer->replaceToken($firstContentAfterBlock, '');
                    } else {
                        // The HTML token has content, so remove leading whitespace in favour of the indent.
                        $phpcsFile->fixer->replaceToken($firstContentAfterBlock, $trimmedHtmlContent);
                    }
                }

                if ($tokens[$firstContentAfterBlock]['code'] === T_OPEN_TAG
                    || $tokens[$firstContentAfterBlock]['code'] === T_OPEN_TAG_WITH_ECHO
                ) {
                    // Next token is a PHP open tag which will also have thrown an error.
                    // Prevent both fixers running in the same loop by making sure the token is "touched" during this loop.
                    // This prevents a stray new line being added between the close and open tags.
                    $phpcsFile->fixer->replaceToken($firstContentAfterBlock, $tokens[$firstContentAfterBlock]['content']);
                }

                $phpcsFile->fixer->endChangeset();
            }
        }

        // Jumbo: the check for a blank line at the bottom is removed.
    }


    /**
     * Jumbo: reports a closing tag on its own line that isn't level with the opening tag.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile  The file being scanned.
     * @param int                         $stackPtr   The position of the opening tag.
     * @param int                         $closingTag The position of the closing tag.
     *
     * @return void
     */
    private function checkClosingTagIndent(File $phpcsFile, int $stackPtr, int $closingTag)
    {
        $tokens   = $phpcsFile->getTokens();
        $expected = $this->calculateLineIndent($phpcsFile, $stackPtr);
        $found    = ($tokens[$closingTag]['column'] - 1);
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($closingTag - 1), null, true);
        if ($found === $expected
            || $tokens[$previous]['line'] === $tokens[$closingTag]['line']
            || $tokens[$closingTag]['conditions'] !== $tokens[$stackPtr]['conditions']
        ) {
            return;
        }

        $error = 'A closing PHP tag must be level with its opening tag; expected %s spaces, found %s';
        $fix   = $phpcsFile->addFixableError($error, $closingTag, 'CloseTagIndent', [$expected, $found]);
        if ($fix === true) {
            if ($found === 0) {
                $phpcsFile->fixer->addContentBefore($closingTag, str_repeat(' ', $expected));
            } else {
                $phpcsFile->fixer->replaceToken(($closingTag - 1), str_repeat(' ', $expected));
            }
        }
    }


    /**
     * Jumbo: reports a statement directly in a block, on its own line, indented past the opening tag's line. Too little
     * indent is left to PERCS31.WhiteSpace.ScopeIndent.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
     * @param int                         $stackPtr     The position of the opening tag.
     * @param int                         $firstContent The position of the block's first content, already checked.
     * @param int|false                   $closingTag   The position of the closing tag, or false if there isn't one.
     *
     * @return void
     */
    private function checkStatementIndents(File $phpcsFile, int $stackPtr, int $firstContent, $closingTag)
    {
        $tokens = $phpcsFile->getTokens();
        $indent = $this->calculateLineIndent($phpcsFile, $stackPtr);
        $end    = ($closingTag === false ? $phpcsFile->numTokens : $closingTag);

        for ($i = ($firstContent + 1); $i < $end; $i++) {
            // Only the first code on a line, directly in the block, and indented too far.
            if ($tokens[($i - 1)]['code'] !== T_WHITESPACE
                || $tokens[($i - 1)]['column'] !== 1
                || isset(Tokens::EMPTY_TOKENS[$tokens[$i]['code']]) === true
                || $tokens[$i]['conditions'] !== $tokens[$stackPtr]['conditions']
                || ($tokens[$i]['column'] - 1) <= $indent
            ) {
                continue;
            }

            $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($i - 1), $stackPtr, true);
            if ($previous !== false
                && $tokens[$previous]['code'] !== T_SEMICOLON
                && $tokens[$previous]['code'] !== T_CLOSE_CURLY_BRACKET
            ) {
                // A continuation line, not the start of a statement.
                continue;
            }

            $error = 'A statement in embedded PHP code must be indented %s spaces; %s found';
            $data  = [
                $indent,
                ($tokens[$i]['column'] - 1),
            ];
            $fix   = $phpcsFile->addFixableError($error, $i, 'StatementIndent', $data);
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken(($i - 1), str_repeat(' ', $indent));
            }
        }
    }


    /**
     * Report and fix a set of empty PHP tags.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in the
     *                                               stack passed in $tokens.
     * @param int                         $closeTag  The position of the PHP close tag in the
     *                                               stack passed in $tokens.
     *
     * @return void
     */
    private function reportEmptyTagSet(File $phpcsFile, int $stackPtr, int $closeTag)
    {
        $tokens = $phpcsFile->getTokens();
        $error  = 'Empty embedded PHP tag found';
        $fix    = $phpcsFile->addFixableError($error, $stackPtr, 'Empty');
        if ($fix === true) {
            $phpcsFile->fixer->beginChangeset();
            for ($i = $stackPtr; $i <= $closeTag; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }

            // Prevent leaving indentation whitespace behind when the empty tag set is the only thing on the affected lines.
            if (isset($tokens[($closeTag + 1)]) === true
                && $tokens[($closeTag + 1)]['line'] !== $tokens[$closeTag]['line']
                && $tokens[($stackPtr - 1)]['code'] === T_INLINE_HTML
                && $tokens[($stackPtr - 1)]['line'] === $tokens[$stackPtr]['line']
                && $tokens[($stackPtr - 1)]['column'] === 1
                && trim($tokens[($stackPtr - 1)]['content']) === ''
            ) {
                $phpcsFile->fixer->replaceToken(($stackPtr - 1), '');
            }

            $phpcsFile->fixer->endChangeset();
        }
    }


    /**
     * Calculate the indent of the line containing the stackPtr.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in the
     *                                               stack passed in $tokens.
     *
     * @return int
     */
    private function calculateLineIndent(File $phpcsFile, int $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        for ($firstOnLine = $stackPtr; $tokens[$firstOnLine]['column'] !== 1; $firstOnLine--);

        // Check if this is a subsequent line in a star-slash comment containing leading indent.
        // In that case, we'll need the first line of the comment to correctly determine the indent.
        while ($tokens[$firstOnLine]['code'] === T_COMMENT
            && $tokens[$firstOnLine]['content'] !== ltrim($tokens[$firstOnLine]['content'])
        ) {
            for (--$firstOnLine; $tokens[$firstOnLine]['column'] !== 1; $firstOnLine--);
        }

        $indent = 0;
        if ($tokens[$firstOnLine]['code'] === T_WHITESPACE) {
            $indent = ($tokens[($firstOnLine + 1)]['column'] - 1);
        } elseif ($tokens[$firstOnLine]['code'] === T_INLINE_HTML
            || $tokens[$firstOnLine]['code'] === T_END_HEREDOC
            || $tokens[$firstOnLine]['code'] === T_END_NOWDOC
        ) {
            $indent = (strlen($tokens[$firstOnLine]['content']) - strlen(ltrim($tokens[$firstOnLine]['content'])));
        } elseif ($tokens[$firstOnLine]['code'] === T_DOC_COMMENT_WHITESPACE) {
            $indent = (strlen($tokens[$firstOnLine]['content']) - strlen(ltrim($tokens[$firstOnLine]['content'])) - 1);
        }

        return $indent;
    }
}
