<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Classes;

use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks the layout of a property's hook list: its braces, and the `=>` of a short hook.
 *
 * PER Coding Style 3.1, section 4.9, says:
 * - The opening brace MUST be on the same line as the property, after a single space.
 * - In the long form, the closing brace MUST be on its own line, with no comment after it.
 * - The entire body of the hook definition, and the body of each hook, MUST be indented one level, and hooks MUST be
 *   separated by a line break. So in a list with a `{}` body, each hook starts on its own line, each body starts on
 *   the line after its `{`, and each `}` is first on its line. An empty body, `{}`, has nothing to move.
 * - Hooks MAY be separated by an additional blank line, so more than one blank line between them is reported.
 * - A short hook's `=>` MUST have a single space on either side, and its body MUST begin on the same line.
 * - A short hook listed inline MUST have a single space after the opening brace and before the closing one.
 *
 * Section 4.10 says a list with no hook bodies, as on an interface or abstract property, MUST be on one line, as
 * `{ get; set; }`: a single space inside each brace, no space before each `;`, and a single space between operations.
 * A bodiless hook in a list with bodies also has no space before its `;`.
 *
 * Section 4.9's text doesn't say where a hook's own `{` goes, how a `set` hook's parameter list is spaced, or whether a
 * by-reference hook's `&` is followed by a space. Its examples show `get {` and `set(string $value)`, and section 4.5
 * puts no space after a by-reference parameter's `&`, so all three are checked: one space before a hook's `{`, a `set`
 * hook's parameter list spaced like a method's, and no space after `&`.
 *
 * A hook on a promoted constructor parameter is reported by PERCS31.Classes.PromotedPropertyHooks, so its body isn't
 * checked here. Indentation is checked by PERCS31.WhiteSpace.ScopeIndent, several statements on a line by
 * PERCS31.Formatting.DisallowMultipleStatements, `get` before `set` by PERCS31.Classes.PropertyDeclaration, and a
 * comment after the closing brace by PERCS31.Classes.ClosingBrace.
 */
class PropertyHookLayoutSniff implements Sniff
{
    public const string CODE_OPENER_NOT_ON_SAME_LINE = 'OpenerNotOnSameLine';

    public const string CODE_SPACE_BEFORE_OPENER = 'SpaceBeforeOpener';

    public const string CODE_CLOSER_NOT_ON_OWN_LINE = 'CloserNotOnOwnLine';

    public const string CODE_ARROW_SPACING = 'ArrowSpacing';

    public const string CODE_BODY_NOT_ON_SAME_LINE = 'BodyNotOnSameLine';

    public const string CODE_INLINE_SPACING = 'InlineSpacing';

    public const string CODE_BODILESS_LAYOUT = 'BodilessLayout';

    public const string CODE_SPACE_BEFORE_SEMICOLON = 'SpaceBeforeSemicolon';

    public const string CODE_SPACE_AFTER_REFERENCE = 'SpaceAfterReference';

    public const string CODE_HOOK_NOT_ON_OWN_LINE = 'HookNotOnOwnLine';

    public const string CODE_HOOK_BODY_NOT_ON_NEW_LINE = 'HookBodyNotOnNewLine';

    public const string CODE_HOOK_CLOSER_NOT_ON_OWN_LINE = 'HookCloserNotOnOwnLine';

    public const string CODE_SPACE_BEFORE_HOOK_BRACE = 'SpaceBeforeHookBrace';

    public const string CODE_TOO_MANY_BLANK_LINES_BETWEEN_HOOKS = 'TooManyBlankLinesBetweenHooks';

    public const string CODE_SPACE_BEFORE_HOOK_PARAMETERS = 'SpaceBeforeHookParameters';

    public const string CODE_SPACE_INSIDE_HOOK_PARAMETERS = 'SpaceInsideHookParameters';

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

        $hookList = PropertyHookHelper::findHookList($phpcsFile, $stackPtr);
        if ($hookList === null) {
            return;
        }

        [$opener, $closer] = $hookList;

        $this->checkOpener($phpcsFile, $opener);
        $this->checkReferences($phpcsFile, $opener, $closer);

        $this->checkHookParameters($phpcsFile, $opener, $closer);

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

        // A property inside parentheses is a promoted constructor parameter.
        $isPromoted = isset($tokens[$stackPtr]['nested_parenthesis']);
        $hasHookBodies = !$isPromoted && $this->checkHookBodies($phpcsFile, $opener, $closer);

        // A list on one line with a `{}` body is long form too. Its closing brace moves in the same pass as the rest,
        // as ScopeIndent and ConsistentIndent can't agree on the indentation after a line with two closing braces.
        if ($hasHookBodies || $tokens[$opener]['line'] !== $tokens[$closer]['line']) {
            $this->checkLongFormCloser($phpcsFile, $opener, $closer);
            if (!$isPromoted) {
                $this->checkBlankLinesBetweenHooks($phpcsFile, $opener, $closer);
            }
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
                FixerHelper::replaceBetween($phpcsFile, $i, $name, '');
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
                FixerHelper::replaceBetween($phpcsFile, $previous, $opener, ' ');
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
            FixerHelper::replaceBetween($phpcsFile, $previous, $opener, ' ');
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
                FixerHelper::replaceBetween($phpcsFile, $arrow, $body, ' ');
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
            FixerHelper::setBetween($phpcsFile, $name, $arrow, ' ');
            FixerHelper::setBetween($phpcsFile, $arrow, $body, ' ');
            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * Check that the closing brace of a hook list over several lines is on its own line. A comment after it is reported
     * by PERCS31.Classes.ClosingBrace.
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
                $indent = str_repeat(' ', LayoutHelper::lineIndent($phpcsFile, $opener));
                FixerHelper::replaceBetween($phpcsFile, $previous, $closer, $phpcsFile->eolChar . $indent);
            }
        }
    }

    /**
     * Check a `set` hook's parameter list as a method's: no space after `set`, and none just inside the parentheses. A
     * line break just inside them is left alone, as in a split list.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the hook list's opening brace.
     * @param int $closer The position of the hook list's closing brace.
     */
    private function checkHookParameters(File $phpcsFile, int $opener, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        foreach (PropertyHookHelper::parseHooks($phpcsFile, $opener, $closer) ?? [] as $hook) {
            $parenOpener = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($hook['namePtr'] + 1), null, true);
            if ($parenOpener === false || $tokens[$parenOpener]['code'] !== T_OPEN_PARENTHESIS) {
                continue;
            }

            $parenCloser = $tokens[$parenOpener]['parenthesis_closer'] ?? null;
            if (!is_int($parenCloser)) {
                continue;
            }

            if ($parenOpener !== ($hook['namePtr'] + 1)) {
                $this->reportHookParameterSpace(
                    $phpcsFile,
                    $hook['namePtr'],
                    $parenOpener,
                    'There must be no space between a hook\'s name and its parameter list',
                    self::CODE_SPACE_BEFORE_HOOK_PARAMETERS,
                );
            }

            $first = $parenOpener + 1;
            if ($tokens[$first]['code'] === T_WHITESPACE && !str_contains($tokens[$first]['content'], "\n")) {
                $next = $phpcsFile->findNext(T_WHITESPACE, $first, null, true);
                if ($next !== false && $next !== $parenCloser) {
                    $this->reportHookParameterSpace(
                        $phpcsFile,
                        $parenOpener,
                        $next,
                        'There must be no space just inside a hook\'s parameter list',
                        self::CODE_SPACE_INSIDE_HOOK_PARAMETERS,
                    );
                }
            }

            $last = $parenCloser - 1;
            $previous = $phpcsFile->findPrevious(T_WHITESPACE, $last, null, true);
            $isSameLine = $previous !== false && $tokens[$previous]['line'] === $tokens[$parenCloser]['line'];
            if ($tokens[$last]['code'] === T_WHITESPACE && $isSameLine && $previous !== $parenOpener) {
                $this->reportHookParameterSpace(
                    $phpcsFile,
                    $previous,
                    $parenCloser,
                    'There must be no space just inside a hook\'s parameter list',
                    self::CODE_SPACE_INSIDE_HOOK_PARAMETERS,
                );
            }
        }
    }

    /**
     * Report whitespace in a hook's parameter list, and fix it by removing it, unless a comment is in the way.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $before The position of the token before the whitespace.
     * @param int $after The position of the token after the whitespace.
     * @param string $error The message.
     * @param string $code The error code.
     */
    private function reportHookParameterSpace(
        File $phpcsFile,
        int $before,
        int $after,
        string $error,
        string $code,
    ): void {
        if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($before + 1), $after) !== false) {
            $phpcsFile->addError($error, $after, $code);
            return;
        }

        if ($phpcsFile->addFixableError($error, $after, $code)) {
            FixerHelper::replaceBetween($phpcsFile, $before, $after, '');
        }
    }

    /**
     * Check that the hooks of a list over several lines have at most one blank line between them.
     *
     * Section 4.9 says hooks in the long form MUST be separated by at least a single line break, and MAY be separated
     * by an additional blank line, and its example shows both. So one blank line is allowed, and more isn't. Hooks on
     * the same line are left to HookNotOnOwnLine.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the hook list's opening brace.
     * @param int $closer The position of the hook list's closing brace.
     */
    private function checkBlankLinesBetweenHooks(File $phpcsFile, int $opener, int $closer): void
    {
        $tokens = $phpcsFile->getTokens();
        $hooks = PropertyHookHelper::parseHooks($phpcsFile, $opener, $closer) ?? [];
        $previousEnd = null;
        foreach ($hooks as $hook) {
            $start = $hook['start'];
            if ($previousEnd === null || $tokens[$previousEnd]['line'] === $tokens[$start]['line']) {
                $previousEnd = $hook['end'];
                continue;
            }

            if ($this->countBlankLines($phpcsFile, $previousEnd, $start) > 1) {
                $this->reportTooManyBlankLines($phpcsFile, $previousEnd, $start);
            }

            $previousEnd = $hook['end'];
        }
    }

    /**
     * Count the blank lines between two tokens on different lines.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $from The position of the first token.
     * @param int $to The position of the second token.
     * @return int The number of lines between them with only whitespace on.
     */
    private function countBlankLines(File $phpcsFile, int $from, int $to): int
    {
        $tokens = $phpcsFile->getTokens();
        $usedLines = [];
        for ($i = ($from + 1); $i < $to; $i++) {
            if ($tokens[$i]['code'] !== T_WHITESPACE && $tokens[$i]['line'] > $tokens[$from]['line']) {
                $usedLines[$tokens[$i]['line']] = true;
            }
        }

        return $tokens[$to]['line'] - $tokens[$from]['line'] - 1 - count($usedLines);
    }

    /**
     * Report more than one blank line between hooks, and fix it to one if there's nothing but whitespace between them.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $previousEnd The position of the end of the first hook.
     * @param int $start The position of the start of the second hook.
     */
    private function reportTooManyBlankLines(File $phpcsFile, int $previousEnd, int $start): void
    {
        $error = 'There must be no more than one blank line between hooks';
        if ($phpcsFile->findNext(T_WHITESPACE, ($previousEnd + 1), $start, true) !== false) {
            $phpcsFile->addError($error, $start, self::CODE_TOO_MANY_BLANK_LINES_BETWEEN_HOOKS);
            return;
        }

        if ($phpcsFile->addFixableError($error, $start, self::CODE_TOO_MANY_BLANK_LINES_BETWEEN_HOOKS)) {
            $indent = str_repeat(' ', ($phpcsFile->getTokens()[$start]['column'] - 1));
            $eol = $phpcsFile->eolChar;
            FixerHelper::replaceBetween($phpcsFile, $previousEnd, $start, $eol . $eol . $indent);
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
            FixerHelper::setBetween($phpcsFile, $opener, $first, ' ');
            FixerHelper::setBetween($phpcsFile, $last, $closer, ' ');
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
            FixerHelper::replaceBetween($phpcsFile, $opener, $closer, $expected);
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
            $jumpTo = TokenHelper::findCloser($tokens[$i]);
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
                FixerHelper::replaceBetween($phpcsFile, $name, $i, '');
            }
        }
    }

    /**
     * Check the layout of a list with at least one `{}` body: each hook starts on its own line, and each body is on its
     * own lines, between its braces.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the hook list's opening brace.
     * @param int $closer The position of the hook list's closing brace.
     * @return bool True if the list has a `{}` body.
     */
    private function checkHookBodies(File $phpcsFile, int $opener, int $closer): bool
    {
        // Where a hook can end, so another can start: the list's opening brace, a `;`, or a body's closing brace.
        $hooks = PropertyHookHelper::parseHooks($phpcsFile, $opener, $closer) ?? [];
        $boundaries = [$opener, ...array_column($hooks, 'end')];
        $bodies = array_values(array_filter(array_column($hooks, 'bodyOpener'), is_int(...)));

        // A short hook may be inline, and a list with no bodies must be, so only a `{}` body rules them out.
        if ($bodies === []) {
            return false;
        }

        $hookIndent = LayoutHelper::lineIndent($phpcsFile, $opener) + LayoutHelper::INDENT;

        foreach ($boundaries as $boundary) {
            $this->checkHookStart($phpcsFile, $boundary, $closer, $hookIndent);
        }

        foreach ($bodies as $body) {
            $this->checkHookBrace($phpcsFile, $body);

            $this->checkHookBody($phpcsFile, $body, $hookIndent);
        }

        return true;
    }

    /**
     * Check that whatever follows the end of a hook, or the list's opening brace, isn't a hook on the same line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $boundary The position of the token before the hook.
     * @param int $closer The position of the hook list's closing brace.
     * @param int $hookIndent The number of spaces a hook is indented.
     */
    private function checkHookStart(File $phpcsFile, int $boundary, int $closer, int $hookIndent): void
    {
        $tokens = $phpcsFile->getTokens();
        $next = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($boundary + 1), $closer, true);
        if ($next === false || $tokens[$next]['line'] !== $tokens[$boundary]['line']) {
            return;
        }

        $error = 'A property hook must start on its own line when a hook in the list has a body in braces';
        if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($boundary + 1), $next) !== false) {
            $phpcsFile->addError($error, $next, self::CODE_HOOK_NOT_ON_OWN_LINE);
        } elseif ($phpcsFile->addFixableError($error, $next, self::CODE_HOOK_NOT_ON_OWN_LINE)) {
            FixerHelper::replaceBetween(
                $phpcsFile,
                $boundary,
                $next,
                $phpcsFile->eolChar . str_repeat(' ', $hookIndent),
            );
        }
    }

    /**
     * Check that a hook's opening brace is exactly one space after its name or parameters.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $bodyOpener The position of the hook's opening brace.
     */
    private function checkHookBrace(File $phpcsFile, int $bodyOpener): void
    {
        $tokens = $phpcsFile->getTokens();
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($bodyOpener - 1), null, true);
        if ($previous === false) {
            return;
        }

        $isOneSpace = ($bodyOpener - $previous) === 2 && $tokens[($bodyOpener - 1)]['content'] === ' ';
        if ($isOneSpace && !isset(Tokens::COMMENT_TOKENS[$tokens[$previous]['code']])) {
            return;
        }

        $error = "A property hook's opening brace must be one space after its name or parameters";
        if (isset(Tokens::COMMENT_TOKENS[$tokens[$previous]['code']])) {
            $phpcsFile->addError($error, $bodyOpener, self::CODE_SPACE_BEFORE_HOOK_BRACE);
        } elseif ($phpcsFile->addFixableError($error, $bodyOpener, self::CODE_SPACE_BEFORE_HOOK_BRACE)) {
            FixerHelper::replaceBetween($phpcsFile, $previous, $bodyOpener, ' ');
        }
    }

    /**
     * Check that a hook's body starts on the line after its opening brace, and that its closing brace is first on its
     * line. An empty body has nothing to move. A comment may follow the opening brace.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $bodyOpener The position of the hook's opening brace.
     * @param int $hookIndent The number of spaces a hook is indented.
     */
    private function checkHookBody(File $phpcsFile, int $bodyOpener, int $hookIndent): void
    {
        $tokens = $phpcsFile->getTokens();
        $bodyCloser = $tokens[$bodyOpener]['bracket_closer'] ?? null;
        if (!is_int($bodyCloser)) {
            return;
        }

        $last = $phpcsFile->findPrevious(T_WHITESPACE, ($bodyCloser - 1), $bodyOpener, true);
        if ($last === false || $last === $bodyOpener) {
            return;
        }

        $first = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($bodyOpener + 1), $bodyCloser, true);
        if ($first !== false && $tokens[$first]['line'] === $tokens[$bodyOpener]['line']) {
            $error = 'The body of a property hook must start on the line after its opening brace';
            if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($bodyOpener + 1), $first) !== false) {
                $phpcsFile->addError($error, $first, self::CODE_HOOK_BODY_NOT_ON_NEW_LINE);
            } elseif ($phpcsFile->addFixableError($error, $first, self::CODE_HOOK_BODY_NOT_ON_NEW_LINE)) {
                $indent = str_repeat(' ', ($hookIndent + LayoutHelper::INDENT));
                FixerHelper::replaceBetween($phpcsFile, $bodyOpener, $first, $phpcsFile->eolChar . $indent);
            }
        }

        if ($tokens[$last]['line'] !== $tokens[$bodyCloser]['line']) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'The closing brace of a property hook must be on its own line',
            $bodyCloser,
            self::CODE_HOOK_CLOSER_NOT_ON_OWN_LINE,
        );
        if ($fix) {
            FixerHelper::replaceBetween(
                $phpcsFile,
                $last,
                $bodyCloser,
                $phpcsFile->eolChar . str_repeat(' ', $hookIndent),
            );
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
        $hooks = PropertyHookHelper::parseHooks($phpcsFile, $opener, $closer) ?? [];

        return array_values(array_filter(array_column($hooks, 'arrowPtr'), is_int(...)));
    }
}
