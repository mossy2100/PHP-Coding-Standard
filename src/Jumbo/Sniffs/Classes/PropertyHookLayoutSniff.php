<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Classes;

use Jumbo\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks the layout of a property's hook list: its braces, and the `=>` of a short hook.
 *
 * PER Coding Style 3.1, section 4.9, says:
 * - The opening brace MUST be on the same line as the property, after a single space.
 * - In the long form, the closing brace MUST be on its own line, with no comment after it.
 * - A short hook's `=>` MUST have a single space on either side, and its body MUST begin on the same line.
 * - A short hook listed inline MUST have a single space after the opening brace and before the closing one.
 *
 * Section 4.10 says a list with no hook bodies, as on an interface or abstract property, MUST be on one line, as
 * `{ get; set; }`: a single space inside each brace, no space before each `;`, and a single space between operations.
 * A bodiless hook in a list with bodies also has no space before its `;`.
 *
 * PER says nothing about a space in a by-reference hook, `& get`, so it's only reported when checkReferenceSpacing is
 * set, as it is in Jumbo.
 *
 * Indentation is checked by Jumbo.WhiteSpace.ScopeIndent, one hook per line by
 * Jumbo.Formatting.DisallowMultipleStatements, and `get` before `set` by Jumbo.Classes.PropertyDeclaration.
 */
class PropertyHookLayoutSniff implements Sniff
{
    public const string CODE_OPENER_NOT_ON_SAME_LINE = 'OpenerNotOnSameLine';

    public const string CODE_SPACE_BEFORE_OPENER = 'SpaceBeforeOpener';

    public const string CODE_CLOSER_NOT_ON_OWN_LINE = 'CloserNotOnOwnLine';

    public const string CODE_COMMENT_AFTER_CLOSER = 'CommentAfterCloser';

    public const string CODE_ARROW_SPACING = 'ArrowSpacing';

    public const string CODE_BODY_NOT_ON_SAME_LINE = 'BodyNotOnSameLine';

    public const string CODE_INLINE_SPACING = 'InlineSpacing';

    public const string CODE_BODILESS_LAYOUT = 'BodilessLayout';

    public const string CODE_SPACE_BEFORE_SEMICOLON = 'SpaceBeforeSemicolon';

    public const string CODE_SPACE_AFTER_REFERENCE = 'SpaceAfterReference';

    /**
     * Whether to report whitespace between a by-reference hook's `&` and its name, e.g. `& get`. PER says nothing
     * about it, so it's off by default.
     */
    public bool $checkReferenceSpacing = false;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_VARIABLE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        if (!PropertyHookHelper::isPropertyVariable($phpcsFile, $stackPtr)) {
            return;
        }

        $opener = PropertyHookHelper::findHookListOpener($phpcsFile, $stackPtr);
        $closer = $opener === false ? null : ($phpcsFile->getTokens()[$opener]['bracket_closer'] ?? null);
        if ($opener === false || !is_int($closer)) {
            return;
        }

        $this->checkOpener($phpcsFile, $opener);
        if ($this->checkReferenceSpacing) {
            $this->checkReferences($phpcsFile, $opener, $closer);
        }

        $arrows = $this->findArrows($phpcsFile, $opener, $closer);
        $hasBlockBody = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, ($opener + 1), $closer) !== false;
        if ($arrows === [] && !$hasBlockBody) {
            $this->checkBodiless($phpcsFile, $opener, $closer);
            return;
        }

        foreach ($arrows as $arrow) {
            $this->checkArrow($phpcsFile, $arrow);
        }

        $this->checkBodilessSemicolons($phpcsFile, $opener, $closer);
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$opener]['line'] !== $tokens[$closer]['line']) {
            $this->checkLongFormCloser($phpcsFile, $opener, $closer);
        } elseif ($arrows !== []) {
            $this->checkInlineSpacing($phpcsFile, $opener, $closer);
        }
    }

    /**
     * Report whitespace between a by-reference hook's `&` and its name, e.g. `& get`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the hook list's opening brace.
     * @param int $closer The position of the hook list's closing brace.
     */
    private function checkReferences(File $phpcsFile, int $opener, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = ($opener + 1); $i < $closer; $i++) {
            if ($tokens[($i + 1)]['code'] !== T_WHITESPACE || !PropertyHookHelper::isHookReference($phpcsFile, $i)) {
                continue;
            }

            // A comment between them would have to move, so leave that alone.
            $name = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($i + 1), null, true);
            if ($name === false || $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($i + 1), $name) !== false) {
                continue;
            }

            $fix = $phpcsFile->addFixableError(
                'There must be no space between a by-reference hook\'s "&" and its name',
                $i,
                self::CODE_SPACE_AFTER_REFERENCE,
            );
            if ($fix) {
                $this->setSpacing($phpcsFile, $i, $name, '');
            }
        }
    }

    /**
     * Check that the opening brace is on the property's line, after a single space.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the hook list's opening brace.
     */
    private function checkOpener(File $phpcsFile, int $opener): void
    {
        $tokens = $phpcsFile->getTokens();
        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($opener - 1), null, true);
        if ($previous === false) {
            return;
        }

        $hasComment = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($previous + 1), $opener) !== false;
        if ($tokens[$previous]['line'] !== $tokens[$opener]['line']) {
            $error = 'The opening brace of a property\'s hooks must be on the same line as the property';
            if ($hasComment) {
                $phpcsFile->addError($error, $opener, self::CODE_OPENER_NOT_ON_SAME_LINE);
                return;
            }

            if ($phpcsFile->addFixableError($error, $opener, self::CODE_OPENER_NOT_ON_SAME_LINE)) {
                $this->setSpacing($phpcsFile, $previous, $opener, ' ');
            }

            return;
        }

        if ($hasComment || $phpcsFile->getTokensAsString(($previous + 1), ($opener - $previous - 1)) === ' ') {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Expected 1 space before the opening brace of a property\'s hooks',
            $opener,
            self::CODE_SPACE_BEFORE_OPENER,
        );
        if ($fix) {
            $this->setSpacing($phpcsFile, $previous, $opener, ' ');
        }
    }

    /**
     * Check that a short hook's `=>` has a single space on either side, with the body starting on its line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $arrow The position of the `=>`.
     */
    private function checkArrow(File $phpcsFile, int $arrow): void
    {
        $tokens = $phpcsFile->getTokens();
        $name = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($arrow - 1), null, true);
        $body = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($arrow + 1), null, true);
        if ($name === false || $body === false) {
            return;
        }

        $hasComment = $phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($name + 1), $body) !== false;
        if ($tokens[$body]['line'] !== $tokens[$arrow]['line']) {
            $error = 'The body of a short property hook must begin on the same line as its "=>"';
            if ($hasComment) {
                $phpcsFile->addError($error, $arrow, self::CODE_BODY_NOT_ON_SAME_LINE);
            } elseif ($phpcsFile->addFixableError($error, $arrow, self::CODE_BODY_NOT_ON_SAME_LINE)) {
                $this->setSpacing($phpcsFile, $arrow, $body, ' ');
            }

            return;
        }

        $before = $phpcsFile->getTokensAsString(($name + 1), ($arrow - $name - 1));
        $after = $phpcsFile->getTokensAsString(($arrow + 1), ($body - $arrow - 1));
        if ($hasComment || ($before === ' ' && $after === ' ')) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Expected 1 space either side of a property hook\'s "=>"',
            $arrow,
            self::CODE_ARROW_SPACING,
        );
        if ($fix) {
            $phpcsFile->fixer->beginChangeset();
            $this->setSpacing($phpcsFile, $name, $arrow, ' ');
            $this->setSpacing($phpcsFile, $arrow, $body, ' ');
            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * Check that the closing brace of a hook list over several lines is on its own line, with no comment after it.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the hook list's opening brace.
     * @param int $closer The position of the hook list's closing brace.
     */
    private function checkLongFormCloser(File $phpcsFile, int $opener, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($closer - 1), $opener, true);
        if ($previous !== false && $tokens[$previous]['line'] === $tokens[$closer]['line']) {
            $error = 'The closing brace of a property\'s hooks must be on its own line';
            if (isset(Tokens::COMMENT_TOKENS[$tokens[$previous]['code']])) {
                $phpcsFile->addError($error, $closer, self::CODE_CLOSER_NOT_ON_OWN_LINE);
            } elseif ($phpcsFile->addFixableError($error, $closer, self::CODE_CLOSER_NOT_ON_OWN_LINE)) {
                $firstOnLine = $phpcsFile->findFirstOnLine(T_WHITESPACE, $opener, true);
                assert(is_int($firstOnLine));
                $indent = str_repeat(' ', ($tokens[$firstOnLine]['column'] - 1));
                $this->setSpacing($phpcsFile, $previous, $closer, $phpcsFile->eolChar . $indent);
            }
        }

        $next = $phpcsFile->findNext(T_WHITESPACE, ($closer + 1), null, true);
        if (
            $next !== false
            && $tokens[$next]['line'] === $tokens[$closer]['line']
            && isset(Tokens::COMMENT_TOKENS[$tokens[$next]['code']])
        ) {
            $phpcsFile->addError(
                'A comment must not follow the closing brace of a property\'s hooks',
                $next,
                self::CODE_COMMENT_AFTER_CLOSER,
            );
        }
    }

    /**
     * Check that an inline hook list has a single space after its opening brace and before its closing one.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the hook list's opening brace.
     * @param int $closer The position of the hook list's closing brace.
     */
    private function checkInlineSpacing(File $phpcsFile, int $opener, int $closer): void
    {
        $first = $phpcsFile->findNext(T_WHITESPACE, ($opener + 1), $closer, true);
        $last = $phpcsFile->findPrevious(T_WHITESPACE, ($closer - 1), $opener, true);
        if ($first === false || $last === false) {
            return;
        }

        $after = $phpcsFile->getTokensAsString(($opener + 1), ($first - $opener - 1));
        $before = $phpcsFile->getTokensAsString(($last + 1), ($closer - $last - 1));
        if ($after === ' ' && $before === ' ') {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Expected 1 space after the opening brace and before the closing brace of an inline property hook',
            $opener,
            self::CODE_INLINE_SPACING,
        );
        if ($fix) {
            $phpcsFile->fixer->beginChangeset();
            $this->setSpacing($phpcsFile, $opener, $first, ' ');
            $this->setSpacing($phpcsFile, $last, $closer, ' ');
            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * Check that a list with no hook bodies is written as `{ get; set; }`, on one line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the hook list's opening brace.
     * @param int $closer The position of the hook list's closing brace.
     */
    private function checkBodiless(File $phpcsFile, int $opener, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        $operations = [];
        $start = $opener + 1;
        for ($i = ($opener + 1); $i < $closer; $i++) {
            if ($tokens[$i]['code'] === T_SEMICOLON) {
                $operations[] = trim($phpcsFile->getTokensAsString($start, ($i - $start))) . ';';
                $start = $i + 1;
            }
        }

        $expected = ' ' . implode(' ', $operations) . ' ';
        $actual = $phpcsFile->getTokensAsString(($opener + 1), ($closer - $opener - 1));
        if ($operations === [] || $actual === $expected) {
            return;
        }

        $error = 'A property hook list with no bodies must be written on one line, with single spaces, e.g. '
            . '"{ get; set; }"';
        if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($opener + 1), $closer) !== false) {
            $phpcsFile->addError($error, $opener, self::CODE_BODILESS_LAYOUT);
            return;
        }

        if ($phpcsFile->addFixableError($error, $opener, self::CODE_BODILESS_LAYOUT)) {
            $phpcsFile->fixer->beginChangeset();
            for ($i = ($opener + 1); $i < $closer; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }

            $phpcsFile->fixer->addContent($opener, $expected);
            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * Check that a bodiless hook, in a list that also has bodies, has no space before its `;`.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the hook list's opening brace.
     * @param int $closer The position of the hook list's closing brace.
     */
    private function checkBodilessSemicolons(File $phpcsFile, int $opener, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = ($opener + 1); $i < $closer; $i++) {
            $jumpTo = $tokens[$i]['parenthesis_closer'] ?? $tokens[$i]['bracket_closer'] ?? null;
            if (is_int($jumpTo) && $jumpTo > $i && $jumpTo < $closer) {
                $i = $jumpTo;
                continue;
            }

            if ($tokens[$i]['code'] !== T_SEMICOLON || $tokens[($i - 1)]['code'] !== T_WHITESPACE) {
                continue;
            }

            // Only a bodiless hook's `;` follows its name directly.
            $name = $phpcsFile->findPrevious(T_WHITESPACE, ($i - 1), $opener, true);
            if ($name === false || !in_array(strtolower($tokens[$name]['content']), ['get', 'set'], true)) {
                continue;
            }

            $fix = $phpcsFile->addFixableError(
                'There must be no space before the ";" of a property hook with no body',
                $i,
                self::CODE_SPACE_BEFORE_SEMICOLON,
            );
            if ($fix) {
                $this->setSpacing($phpcsFile, $name, $i, '');
            }
        }
    }

    /**
     * Find the `=>` of each short hook in a hook list, skipping any inside a hook's parameters or body.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the hook list's opening brace.
     * @param int $closer The position of the hook list's closing brace.
     * @return list<int> The positions.
     */
    private function findArrows(File $phpcsFile, int $opener, int $closer): array
    {
        $tokens = $phpcsFile->getTokens();
        $arrows = [];
        $inShortBody = false;
        for ($i = ($opener + 1); $i < $closer; $i++) {
            // A short hook's body runs to the `;` at this level, so a `=>` within it isn't a hook's.
            if ($tokens[$i]['code'] === T_DOUBLE_ARROW && !$inShortBody) {
                $arrows[] = $i;
                $inShortBody = true;
            } elseif ($tokens[$i]['code'] === T_SEMICOLON) {
                $inShortBody = false;
            }

            $jumpTo = $tokens[$i]['parenthesis_closer'] ?? $tokens[$i]['bracket_closer']
                ?? $tokens[$i]['attribute_closer'] ?? null;
            if (is_int($jumpTo) && $jumpTo > $i && $jumpTo < $closer) {
                $i = $jumpTo;
            }
        }

        return $arrows;
    }

    /**
     * Replace whatever is between two tokens with the given whitespace.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $from The token before the gap.
     * @param int $to The token after the gap.
     * @param string $spacing The whitespace to put in the gap.
     */
    private function setSpacing(File $phpcsFile, int $from, int $to, string $spacing): void
    {
        $phpcsFile->fixer->beginChangeset();
        for ($i = ($from + 1); $i < $to; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addContent($from, $spacing);
        $phpcsFile->fixer->endChangeset();
    }
}
