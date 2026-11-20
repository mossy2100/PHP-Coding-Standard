<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Files;

use PERCS31\Helpers\FileHeaderHelper;
use PERCS31\Helpers\FixerHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Files\FileHeaderSniff as Psr12Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Namespaces;
use PHPCSUtils\Utils\UseStatements;

/**
 * Checks the file header, allowing a declare statement between PHP tags on the first line of a file with markup.
 *
 * PER Coding Style 3.1, section 3, says that to declare strict types in a file containing markup outside PHP tags,
 * the declaration MUST be on the first line, with its own opening and closing tags, e.g.
 * `<?php declare(strict_types=1) ?>`. PSR12.Files.FileHeader reads that as a header, and requires blank lines after
 * the tag and the declaration. This sniff skips such a file, as the parent skips other files that mix PHP and markup,
 * and leaves everything else to the parent.
 *
 * Section 3 says a header block MUST NOT contain a blank line, and the file docblock is one, but the parent only checks
 * the lines between two statements. This sniff reports a line inside the file docblock that's empty, or only spaces, as
 * SpacingInsideDocblockBlock, and fixes it to a `*` line. A line with only a `*` isn't blank.
 *
 * Section 3 also lists "One or more declare statements" as one block, with no blank line in it. The parent ends the
 * header at a block declare, so it wants a blank line before one. This sniff counts one as a declare statement, so
 * there's no blank line between it and the declare statements before it.
 *
 * A file with markup before its first PHP opening tag, such as a template, has no file header, so its blocks and blank
 * lines aren't checked. The parent skips a file that mixes PHP and markup when it has several opening tags, but with
 * only one, it reports a declare after the markup as a misplaced header, and its spacing fixes then fight other sniffs.
 * An import in such a file is still reported, as ImportAfterCode, as it can't be at the top of the file.
 *
 * Section 3 also says the opening tag on the first line MUST be on its own line "unless it is a file containing markup
 * outside of PHP opening and closing tags". So a template may start with `<?php require 'header.php'; ?>`. With only
 * that one tag, the parent reads the line as a header, and wants a blank line after the tag, which splits it. This
 * sniff skips the header's spacing checks in a file with markup whose first tag shares its line.
 *
 * In a file with markup, a strict_types declare anywhere else is reported, as StrictTypesNotOnFirstLine, whether it
 * sets strict_types to 1 or 0. If the declare is all that's in its PHP block, the fix collapses the block onto the
 * first line. Otherwise, it adds the first-line form at the top, and removes the declare statement and the blank line
 * after it. PHP skips a shebang line, so in a file starting with one, the second line counts as the first.
 *
 * The parent ends a `use` statement at its first comma, so it reads `use A, B;` as the end of the header, and reports
 * the missing blank line before `B`. Section 3 doesn't forbid several imports in one statement, so this sniff extends
 * that line to its semicolon, and adds any `use` statements after it.
 *
 * The parent also takes a comment at the end of a block's last line, as in `use A; // Why.`, for the start of the next
 * block, so it reports a missing blank line, and its fix moves the comment onto a line of its own. This sniff counts
 * such a comment as part of the line it ends.
 *
 * When code follows the opening tag on the first line, as in `<?php echo 1;`, PSR12.Files.OpenTag reports it as
 * NotAlone. The parent reported it too, as a missing blank line after the tag, so this sniff leaves the tag's line out
 * of the header then. Once the code is moved, the blank line after the tag is checked as usual.
 *
 * The parent only looks at the header, so an import after the rest of the code passed. Section 3 puts the imports
 * before "The remainder of the code in the file", and says that in a file with markup they "MUST be present at the top
 * of the file". This sniff reports one as ImportAfterCode, but not in a file with more than one namespace, where each
 * namespace has its own imports. It isn't fixed, as moving an import can change what a name resolves to.
 *
 * A file that starts with `<?php declare(strict_types=1) ?>` may carry its header on in the next PHP block, when no
 * markup or `<?=` block comes before it. Then an import after that block's header is reported, as in any PHP file.
 * Otherwise, an import after the first markup or `<?=` block is.
 */
class FileHeaderSniff extends Psr12Sniff
{
    public const string CODE_STRICT_TYPES_NOT_ON_FIRST_LINE = 'StrictTypesNotOnFirstLine';

    public const string CODE_IMPORT_AFTER_CODE = 'ImportAfterCode';

    public const string CODE_BLANK_LINE_IN_DOCBLOCK = 'SpacingInsideDocblockBlock';

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @return int|null The position to skip to, as the parent returns.
     */
    public function process(File $phpcsFile, int $stackPtr): ?int
    {
        // The header may carry on in the next PHP block, if no markup or `<?=` block comes first. Then an import after
        // that block's header is past it. Otherwise, an import after the markup or the `<?=` is.
        if (FileHeaderHelper::hasInlineDeclare($phpcsFile)) {
            $output = $this->findOutput($phpcsFile, $stackPtr);
            $nextTag = $phpcsFile->findNext(T_OPEN_TAG, ($stackPtr + 1));
            if ($nextTag !== false && ($output === null || $nextTag < $output)) {
                $this->checkImportsAfterCode($phpcsFile, $nextTag);
            } elseif ($output !== null) {
                $this->checkImportsAfterCode($phpcsFile, $stackPtr, false, $output);
            }

            return $phpcsFile->numTokens;
        }

        // A file that starts with markup has no header, so every import in it is after the top of the file.
        if (FileHeaderHelper::hasMarkupBeforeFirstTag($phpcsFile)) {
            $this->checkImportsAfterCode($phpcsFile, $stackPtr, false);

            return $phpcsFile->numTokens;
        }

        // Once the declare is reported, the rest of the header is left until it's moved, so the fixes don't clash.
        if ($this->checkStrictTypesOnFirstLine($phpcsFile, $stackPtr)) {
            return $phpcsFile->numTokens;
        }

        if ($phpcsFile->findPrevious(T_OPEN_TAG, ($stackPtr - 1)) === false) {
            $this->checkImportsAfterCode($phpcsFile, $stackPtr);
        }

        // In a file with markup, the first tag may share its line, so the header's blank lines don't apply.
        if (FileHeaderHelper::tagSharesLine($phpcsFile, $stackPtr) && FileHeaderHelper::hasMarkup($phpcsFile)) {
            return $phpcsFile->numTokens;
        }

        $result = parent::process($phpcsFile, $stackPtr);

        return is_int($result) ? $result : null;
    }

    /**
     * Check the spacing and grouping of the header's blocks, as the parent does, and that the file docblock has no
     * blank line in it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array<int, array<string, mixed>> $headerLines The header's lines, from getHeaderLines().
     */
    public function processHeaderLines(File $phpcsFile, array $headerLines): void
    {
        parent::processHeaderLines($phpcsFile, $headerLines);

        foreach ($headerLines as $line) {
            if ($line['type'] === 'docblock' && is_int($line['start'])) {
                $this->checkDocblockBlankLines($phpcsFile, $line['start']);
            }
        }
    }

    /**
     * Report each blank line inside the file docblock: a line with no `*`, empty or only spaces. Section 3 says a
     * header block MUST NOT contain a blank line. The fix turns it into a `*` line, lined up with the other stars, so
     * the paragraph break and the comment's text are kept. A docblock with no stars to line up with isn't fixed.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the docblock's opening tag.
     */
    private function checkDocblockBlankLines(File $phpcsFile, int $opener): void
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$opener]['comment_closer'] ?? null;
        if (!is_int($closer)) {
            return;
        }

        $star = $phpcsFile->findNext(T_DOC_COMMENT_STAR, ($opener + 1), $closer);
        $prefix = $star === false ? null : str_repeat(' ', ($tokens[$star]['column'] - 1)) . '*';

        $error = 'Header blocks must not contain blank lines';
        for ($i = ($opener + 1); $i < $closer; $i++) {
            // Look at each line from its first token, and skip the opening tag's line.
            if ($tokens[$i]['column'] !== 1) {
                continue;
            }

            $last = $i;
            while (isset($tokens[($last + 1)]) && $tokens[($last + 1)]['line'] === $tokens[$i]['line']) {
                $last++;
            }

            $isBlank = true;
            for ($j = $i; $j <= $last; $j++) {
                $isBlank = $isBlank && $tokens[$j]['code'] === T_DOC_COMMENT_WHITESPACE;
            }

            if ($isBlank && $last < $closer) {
                $code = self::CODE_BLANK_LINE_IN_DOCBLOCK;
                if (FixerHelper::addFixableErrorIf($phpcsFile, $prefix !== null, $error, $i, $code)) {
                    $phpcsFile->fixer->beginChangeset();
                    for ($j = $i; $j < $last; $j++) {
                        $phpcsFile->fixer->replaceToken($j, '');
                    }

                    $phpcsFile->fixer->replaceToken($last, "{$prefix}{$phpcsFile->eolChar}");
                    $phpcsFile->fixer->endChangeset();
                }
            }

            $i = $last;
        }
    }

    /**
     * Get the lines of the file's header, including a block declare that follows it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the opening tag.
     * @return array<int, array<string, mixed>> The header's lines.
     */
    public function getHeaderLines(File $phpcsFile, int $stackPtr): array
    {
        $tokens = $phpcsFile->getTokens();
        $lines = $this->completeUseLines($phpcsFile, parent::getHeaderLines($phpcsFile, $stackPtr));
        $lines = $this->includeTrailingComments($phpcsFile, $lines);

        // Code on the first line's opening tag is reported by PSR12.Files.OpenTag, so the tag's blank line isn't.
        $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (
            $stackPtr === 0 && $next !== false && $tokens[$next]['line'] === $tokens[$stackPtr]['line']
            && ($lines[0]['type'] ?? null) === 'tag'
        ) {
            array_shift($lines);
        }

        $last = end($lines);
        if ($last === false || !in_array($last['type'], ['tag', 'docblock', 'declare'], true)) {
            return $lines;
        }

        $lastEnd = $last['end'];
        assert(is_int($lastEnd));
        $next = $phpcsFile->findNext(T_WHITESPACE, ($lastEnd + 1), null, true);
        if ($next === false || $tokens[$next]['code'] !== T_DECLARE || !isset($tokens[$next]['scope_closer'])) {
            return $lines;
        }

        $lines[] = ['type' => 'declare', 'start' => $next, 'end' => $tokens[$next]['scope_closer']];

        return $lines;
    }

    /**
     * Extend the last `use` line to its semicolon, where the parent stopped at a comma, and add the `use` statements
     * after it, which the parent took for the start of the code.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array<int, array<string, mixed>> $lines The header's lines, from the parent.
     * @return array<int, array<string, mixed>> The header's lines.
     */
    private function completeUseLines(File $phpcsFile, array $lines): array
    {
        $tokens = $phpcsFile->getTokens();
        $index = array_key_last($lines);
        if ($index === null || !is_string($lines[$index]['type']) || !str_starts_with($lines[$index]['type'], 'use')) {
            return $lines;
        }

        $start = $lines[$index]['start'];
        $end = $lines[$index]['end'];
        assert(is_int($start) && is_int($end));
        $semicolon = $phpcsFile->findNext(T_SEMICOLON, $start);
        if ($semicolon === false || $semicolon <= $end) {
            return $lines;
        }

        $lines[$index]['end'] = $semicolon;
        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($semicolon + 1), null, true);
        while ($next !== false && $tokens[$next]['code'] === T_USE) {
            $semicolon = $phpcsFile->findNext(T_SEMICOLON, $next);
            if ($semicolon === false) {
                break;
            }

            $type = 'use';
            $useType = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($next + 1), null, true);
            $content = $useType === false ? '' : strtolower($tokens[$useType]['content']);
            if ($content === 'function' || $content === 'const') {
                $type .= " {$content}";
            }

            $lines[] = ['type' => $type, 'start' => $next, 'end' => $semicolon];
            $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($semicolon + 1), null, true);
        }

        return $lines;
    }

    /**
     * Extend each header line to take in a comment at the end of it, which the parent took for the start of the next
     * block, on the same line, with no blank line before it.
     *
     * The opening tag's line is left alone, as PSR12.Files.OpenTag decides whether a comment may follow the tag.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array<int, array<string, mixed>> $lines The header's lines.
     * @return array<int, array<string, mixed>> The header's lines.
     */
    private function includeTrailingComments(File $phpcsFile, array $lines): array
    {
        $tokens = $phpcsFile->getTokens();
        foreach ($lines as $index => $line) {
            $end = $line['end'];
            assert(is_int($end));
            if ($line['type'] === 'tag') {
                continue;
            }

            $next = $phpcsFile->findNext(T_WHITESPACE, ($end + 1), null, true);
            while (
                $next !== false && isset(Tokens::COMMENT_TOKENS[$tokens[$next]['code']])
                && $tokens[$next]['line'] === $tokens[$end]['line']
            ) {
                $lines[$index]['end'] = $next;
                $next = $phpcsFile->findNext(T_WHITESPACE, ($next + 1), null, true);
            }
        }

        return $lines;
    }

    /**
     * Report an import after the header, in a file with one namespace or none.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the first opening tag.
     * @param bool $hasHeader Whether the opening tag starts a header. It doesn't in a file that starts with markup.
     * @param int|null $after The position to look for imports after, or null for the end of the header.
     */
    private function checkImportsAfterCode(
        File $phpcsFile,
        int $stackPtr,
        bool $hasHeader = true,
        ?int $after = null,
    ): void {
        $tokens = $phpcsFile->getTokens();
        $namespaces = 0;
        $namespace = $phpcsFile->findNext(T_NAMESPACE, 0);
        while ($namespace !== false) {
            if (Namespaces::isDeclaration($phpcsFile, $namespace)) {
                $namespaces++;
            }

            $namespace = $phpcsFile->findNext(T_NAMESPACE, ($namespace + 1));
        }

        if ($namespaces > 1) {
            return;
        }

        $lines = $hasHeader ? $this->getHeaderLines($phpcsFile, $stackPtr) : [];
        $last = end($lines);
        $headerEnd = $after ?? ($last === false ? $stackPtr : $last['end']);
        assert(is_int($headerEnd));

        $use = $phpcsFile->findNext(T_USE, ($headerEnd + 1));
        while ($use !== false) {
            if ($tokens[$use]['conditions'] === [] && UseStatements::isImportUse($phpcsFile, $use)) {
                $phpcsFile->addError(
                    'An import must be in the file header, before the rest of the code',
                    $use,
                    self::CODE_IMPORT_AFTER_CODE,
                );
            }

            $use = $phpcsFile->findNext(T_USE, ($use + 1));
        }
    }

    /**
     * Find the first output after a position: markup, ignoring white space between two PHP blocks, or a `<?=` block.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position to search from.
     * @return int|null The position of the first inline HTML token that isn't blank, or of the first `<?=` tag, or
     *     null if there's neither.
     */
    private function findOutput(File $phpcsFile, int $stackPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $output = $phpcsFile->findNext([T_INLINE_HTML, T_OPEN_TAG_WITH_ECHO], ($stackPtr + 1));
        while ($output !== false) {
            if ($tokens[$output]['code'] === T_OPEN_TAG_WITH_ECHO || trim($tokens[$output]['content']) !== '') {
                return $output;
            }

            $output = $phpcsFile->findNext([T_INLINE_HTML, T_OPEN_TAG_WITH_ECHO], ($output + 1));
        }

        return null;
    }

    /**
     * Report a strict_types declare that isn't on the first line of a file with markup, as section 3 requires, and fix
     * it. After a shebang line, the first line is the one after it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the opening tag.
     * @return bool True if it was reported.
     */
    private function checkStrictTypesOnFirstLine(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $declare = FileHeaderHelper::findStrictTypesDeclare($phpcsFile);
        if (
            $declare === null || $stackPtr !== FileHeaderHelper::findFirstLineTag($phpcsFile)
            || !FileHeaderHelper::hasMarkup($phpcsFile)
        ) {
            return false;
        }

        // PHP requires strict_types in the first statement, so anything else isn't valid code.
        $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        $closer = $tokens[$declare]['parenthesis_closer'] ?? null;
        if ($first !== $declare || !is_int($closer)) {
            return false;
        }

        $end = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closer + 1), null, true);
        if ($end === false) {
            return false;
        }

        if ($tokens[$end]['code'] !== T_SEMICOLON) {
            $end = $closer;
        }

        $fix = $phpcsFile->addFixableError(
            'In a file with markup, the strict_types declaration must be on the first line%s, between its own PHP '
                . 'tags, as in <?php declare(strict_types=1) ?>',
            $declare,
            self::CODE_STRICT_TYPES_NOT_ON_FIRST_LINE,
            [$stackPtr === 0 ? '' : ' after the shebang line'],
        );
        if (!$fix) {
            return true;
        }

        $statement = $phpcsFile->getTokensAsString($declare, ($closer - $declare + 1));
        $next = $phpcsFile->findNext(T_WHITESPACE, ($end + 1), null, true);
        $isOnlyStatement = $next !== false && $tokens[$next]['code'] === T_CLOSE_TAG
            && $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), $declare, true) === false;

        $phpcsFile->fixer->beginChangeset();
        if ($isOnlyStatement) {
            // The declare is all that's in the block, so the block becomes the first-line form.
            $phpcsFile->fixer->replaceToken($stackPtr, "<?php {$statement} ");
            for ($i = ($stackPtr + 1); $i < $next; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        } else {
            $phpcsFile->fixer->addContentBefore($stackPtr, "<?php {$statement} ?>{$phpcsFile->eolChar}");
            $last = $next === false ? $phpcsFile->numTokens : $next;
            for ($i = $declare; $i < $last; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }

        $phpcsFile->fixer->endChangeset();

        return true;
    }
}
