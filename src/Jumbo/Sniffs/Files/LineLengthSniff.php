<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff as GenericSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Warns about a line over the soft limit of 120 characters, and, at a lower severity, over the recommended 80.
 *
 * PER Coding Style 3.1, section 2.3, says there MUST NOT be a hard limit, the soft limit MUST be 120 characters, and
 * lines SHOULD NOT be longer than 80. Generic.Files.LineLength has one warning threshold, so this sniff adds a second:
 * a line over $recommendedLineLimit, but within $lineLimit, gets an OverRecommended warning with a severity of 4. That
 * is below phpcs's default --warning-severity of 5, so it's only shown when asked for, with --warning-severity=4.
 *
 * The line is measured exactly as by the parent, which reports at most one message per line. Neither limit is
 * auto-fixable, as only a person can choose where to split a line.
 *
 * Portions of this file are from PHP_CodeSniffer's Generic.Files.LineLength sniff:
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2023 Squiz Pty Ltd (ABN 77 084 670 600)
 * @copyright 2023 PHPCSStandards and contributors
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/HEAD/licence.txt BSD Licence
 */
class LineLengthSniff extends GenericSniff
{
    public const string CODE_OVER_RECOMMENDED = 'OverRecommended';

    /**
     * The severity of the OverRecommended warning, below phpcs's default --warning-severity of 5.
     */
    public const int RECOMMENDED_SEVERITY = 4;

    /**
     * The recommended limit, over which a line gets a low-severity OverRecommended warning. 0 turns it off.
     */
    public int $recommendedLineLimit = 80;

    /**
     * Set the parent's limits to PER Coding Style's: a soft limit of 120, and no hard limit.
     *
     * The parent's properties can't be redeclared with a type, so they're set here. A ruleset can still change them.
     */
    public function __construct()
    {
        $this->lineLimit = 120;
        $this->absoluteLineLimit = 0;
    }

    /**
     * Checks if a line is too long.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     * @param int $stackPtr The first token on the next line.
     * @return void
     */
    protected function checkLineLength(File $phpcsFile, array $tokens, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $lineLimit = (int) $this->lineLimit;
        $absoluteLineLimit = (int) $this->absoluteLineLimit;
        $lastPtr = $this->findLastTokenOnLine($phpcsFile, $stackPtr);
        if ($lastPtr === null) {
            return;
        }

        $lineLength = $this->measure($phpcsFile, $lastPtr);
        if ($lineLength === null) {
            return;
        }

        $this->recordLengthMetric($phpcsFile, $lastPtr, $lineLength);
        $onlyComment = isset(Tokens::COMMENT_TOKENS[$tokens[$lastPtr]['code']])
            && $this->isOnlyComment($phpcsFile, $lastPtr);

        if ($absoluteLineLimit > 0 && $lineLength > $absoluteLineLimit) {
            $error = 'Line exceeds maximum limit of %s characters; contains %s characters';
            $phpcsFile->addError($error, $lastPtr, 'MaxExceeded', [$absoluteLineLimit, $lineLength]);
        } elseif ($lineLength > $lineLimit) {
            if ($onlyComment && $this->isUnbreakableComment($phpcsFile, $lastPtr, $lineLimit)) {
                return;
            }

            $warning = 'Line exceeds %s characters; contains %s characters';
            $phpcsFile->addWarning($warning, $lastPtr, 'TooLong', [$lineLimit, $lineLength]);
        } elseif ($this->recommendedLineLimit > 0 && $lineLength > $this->recommendedLineLimit) {
            if ($onlyComment && $this->isUnbreakableComment($phpcsFile, $lastPtr, $this->recommendedLineLimit)) {
                return;
            }

            $phpcsFile->addWarning(
                'Line exceeds the recommended %s characters; contains %s characters',
                $lastPtr,
                self::CODE_OVER_RECOMMENDED,
                [$this->recommendedLineLimit, $lineLength],
                self::RECOMMENDED_SEVERITY,
            );
        }
    }

    /**
     * Find the last token on a line, from the first token on the next line, as the parent does.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The first token on the next line.
     * @return int|null The position, or null for a blank line.
     */
    private function findLastTokenOnLine(File $phpcsFile, int $stackPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $stackPtr--;
        if ($tokens[$stackPtr]['column'] === 1 && $tokens[$stackPtr]['length'] === 0) {
            return null;
        }

        if ($tokens[$stackPtr]['column'] !== 1 && $tokens[$stackPtr]['content'] === $phpcsFile->eolChar) {
            $stackPtr--;
        }

        return $stackPtr;
    }

    /**
     * Measure a line, as the parent does.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $lastPtr The last token on the line.
     * @return int|null The length, or null if the line isn't checked.
     */
    private function measure(File $phpcsFile, int $lastPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $isComment = isset(Tokens::COMMENT_TOKENS[$tokens[$lastPtr]['code']]);
        $onlyComment = $isComment && $this->isOnlyComment($phpcsFile, $lastPtr);

        // A PHPCS annotation comment on a line by itself isn't checked.
        if ($onlyComment && in_array($tokens[$lastPtr]['code'], Tokens::PHPCS_ANNOTATION_TOKENS, true)) {
            return null;
        }

        $lineLength = $tokens[$lastPtr]['column'] + $tokens[$lastPtr]['length'] - 1;
        if ($this->ignoreComments && $isComment) {
            if ($onlyComment) {
                return null;
            }

            $lineLength -= $tokens[$lastPtr]['length'];
        }

        return $lineLength;
    }

    /**
     * Check whether a comment is the only thing on its line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $commentPtr The comment.
     * @return bool True if nothing but whitespace comes before it on the line.
     */
    private function isOnlyComment(File $phpcsFile, int $commentPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($commentPtr - 1), null, true);

        return $previous === false || $tokens[$previous]['line'] !== $tokens[$commentPtr]['line'];
    }

    /**
     * Check whether a comment on a line by itself can't be broken up within a limit, such as one holding a URL, as the
     * parent does.
     *
     * @param int $lastPtr The comment, the only token on its line.
     * @param int $limit The limit the comment is over.
     * @return bool True if the comment's last word wouldn't fit within the limit on a line of its own.
     */
    private function isUnbreakableComment(File $phpcsFile, int $lastPtr, int $limit): bool
    {
        $tokens = $phpcsFile->getTokens();
        $content = $tokens[$lastPtr]['content'];
        $oldLength = strlen($content);
        $newLength = strlen(ltrim($content, "/#\t "));
        $indent = ($tokens[$lastPtr]['column'] - 1) + ($oldLength - $newLength);
        $nonBreakingLength = $tokens[$lastPtr]['length'];
        $space = strrpos($content, ' ');
        if ($space !== false) {
            $nonBreakingLength -= ($space + 1);
        }

        return ($nonBreakingLength + $indent) > $limit;
    }

    /**
     * Record the line length metric, in the parent's groups.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $lastPtr The last token on the line.
     * @param int $lineLength The line's length.
     */
    private function recordLengthMetric(File $phpcsFile, int $lastPtr, int $lineLength): void
    {
        $group = match (true) {
            $lineLength <= 80 => '80 or less',
            $lineLength <= 120 => '81-120',
            $lineLength <= 150 => '121-150',
            default => '151 or more',
        };
        $phpcsFile->recordMetric($lastPtr, 'Line length', $group);
    }
}
