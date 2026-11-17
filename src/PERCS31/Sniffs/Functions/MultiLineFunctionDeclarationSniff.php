<?php

declare(strict_types=1);

namespace PERCS31\Sniffs\Functions;

use PERCS31\Helpers\EmptyBodyHelper;
use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PERCS31\Helpers\PropertyHookHelper;
use PERCS31\Helpers\TokenHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Functions\MultiLineFunctionDeclarationSniff as SquizSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks function, closure and arrow function declarations, allowing an empty body as `{}` after a single-line
 * signature.
 *
 * Squiz.Functions.MultiLineFunctionDeclaration requires the opening brace of a single-line declaration on the next
 * line, so it reports `function foo(): void {}`. PER Coding Style 3.1, sections 4.4 and 7, says an empty function body
 * should, and an empty closure body must, be written that way. For an empty body on one line, this sniff skips the
 * single-line brace checks. That also skips the parent's `CloseBracketNewLine` check for those functions, as it can't
 * be run on its own. Everything else is left to the parent, including multi-line declarations, which already allow
 * `) {}`.
 *
 * The parent also reports any blank line in a multi-line parameter or `use` list (EmptyLine). PER Coding Style 3.1,
 * section 2.3, allows blank lines "except where explicitly forbidden", and section 12.4's example has one between two
 * parameters. The parent's check can't be run on its own, so the PERCS31 ruleset excludes EmptyLine, and this sniff
 * reports blank lines itself (BlankLine). One between two parameters, or two `use` variables, is allowed. One straight
 * after a list's opening parenthesis, or straight before its closing one, is reported, as the first item goes on the
 * next line, and the closing parenthesis on the line after the last. So is one after an attribute, as section 12.2
 * says there MUST NOT be any blank lines between the attributes and the structure.
 *
 * The parent also wants every line of a multi-line parameter or `use` list at the same indent (Indent), so it reports a
 * default value wrapped at an operator, as in `string $e = 'a'` then `. 'b',`, which section 6.4 indents once more.
 * That check can't skip one kind of line, so the PERCS31 ruleset excludes Indent, and this sniff checks the indent
 * itself (ParameterIndent), only of a line that starts a parameter, a `use` variable or their attributes, and of each
 * closing parenthesis. A continuation line is left to the sniffs for section 6.4.
 *
 * The parent reads a parameter list split only by an attribute, as in `function a(#[A]` then `$b)`, as a single-line
 * declaration, so nothing reported it. This sniff reads any list whose parentheses are on different lines as
 * multi-line.
 *
 * The parent doesn't register an arrow function. Section 7.1 says one follows the same rules as a closure, so this
 * sniff registers `fn` too, and checks a split parameter list. It has no braces, so the brace checks don't apply.
 *
 * The parent would also indent every line of a promoted parameter's multi-line hook list as a parameter, fighting
 * PERCS31.WhiteSpace.ScopeIndent until phpcbf fails. PERCS31.Classes.PropertyHooks already reports that code,
 * and it has to be rewritten, so this sniff leaves its parameter list alone.
 *
 * The parent's fix for a closure's `use` on the line after the closing parenthesis never finishes. Its SpaceBeforeUse
 * fix reads the line break as no space, and adds one, leaving `use` on its line, and its Indent fix then indents that
 * line. The two undo each other until phpcbf fails. So this sniff reports that `use` itself, with the parent's code,
 * and joins it to the parenthesis's line. The parent's checks run on the next pass. This is a workaround for an
 * upstream bug, in PEAR.Functions.FunctionDeclaration, to remove once it's fixed there.
 */
class MultiLineFunctionDeclarationSniff extends SquizSniff
{
    public const string CODE_BLANK_LINE = 'BlankLine';

    public const string CODE_BLANK_LINE_BEFORE_CLOSE_BRACKET = 'BlankLineBeforeCloseBracket';

    public const string CODE_PARAMETER_INDENT = 'ParameterIndent';

    /**
     * Returns an array of tokens this test wants to listen for: the parent's, and an arrow function's `fn`.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [...parent::register(), T_FN];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        if ($phpcsFile->getTokens()[$stackPtr]['code'] === T_FN) {
            $this->processArrowFunction($phpcsFile, $stackPtr);
            return;
        }

        if ($this->joinUseToParenthesis($phpcsFile, $stackPtr)) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * Determine if this is a multi-line function declaration.
     *
     * The parent goes by where each parameter starts, so it reads `function a(#[A]` then `$b)` as a single-line
     * declaration: the attribute follows the `(` on its line, and the `)` follows the parameter on its line. A
     * parameter list whose parentheses are on different lines is always multi-line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the function keyword.
     * @param int $openBracket The position of the parameter list's opening parenthesis.
     * @param array<int, array<string, mixed>> $tokens The stack of tokens that make up the file.
     * @return bool True if the declaration is split across lines.
     */
    public function isMultiLineDeclaration(File $phpcsFile, int $stackPtr, int $openBracket, array $tokens): bool
    {
        $closeBracket = $tokens[$openBracket]['parenthesis_closer'] ?? null;
        if (is_int($closeBracket) && $tokens[$openBracket]['line'] !== $tokens[$closeBracket]['line']) {
            return true;
        }

        return (bool) parent::isMultiLineDeclaration($phpcsFile, $stackPtr, $openBracket, $tokens);
    }

    /**
     * Report a closure's `use` on a line after its parameter list's closing parenthesis, and join it to that line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the function keyword.
     * @return bool True if it was reported, so the parent's checks, whose fixes for it undo each other, are skipped.
     */
    private function joinUseToParenthesis(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
        $use = $this->findUse($phpcsFile, $stackPtr);
        if (!is_int($closer) || $use === null || $tokens[$use]['line'] === $tokens[$closer]['line']) {
            return false;
        }

        // A comment between them would be lost.
        $isFixable = $phpcsFile->findNext(T_WHITESPACE, ($closer + 1), $use, true) === false;
        $error = 'Expected 1 space before USE keyword; found newline';
        if (FixerHelper::addFixableErrorIf($phpcsFile, $isFixable, $error, $use, 'SpaceBeforeUse')) {
            FixerHelper::replaceBetween($phpcsFile, $closer, $use, ' ');
        }

        return true;
    }

    /**
     * Find a closure's `use`, between its parameter list and its body.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the function keyword.
     * @return int|null The position of `use`, or null if it isn't a closure, or has no `use`.
     */
    private function findUse(File $phpcsFile, int $stackPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
        $opener = $tokens[$stackPtr]['scope_opener'] ?? null;
        if ($tokens[$stackPtr]['code'] !== T_CLOSURE || !is_int($closer) || !is_int($opener)) {
            return null;
        }

        $use = $phpcsFile->findNext(T_USE, ($closer + 1), $opener);

        return $use === false ? null : $use;
    }

    /**
     * Checks an arrow function's multi-line parameter list, with the parents' argument list checks.
     *
     * PER Coding Style 3.1, section 7.1, says an arrow function follows the same rules as a closure, so a split
     * parameter list has its first parameter on the next line, one parameter per line, each indented once, and the
     * closing parenthesis on a line of its own. An arrow function has no braces, so the parents' brace checks don't
     * apply.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of `fn`.
     */
    private function processArrowFunction(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$stackPtr]['parenthesis_opener'] ?? null;
        $closer = $tokens[$stackPtr]['parenthesis_closer'] ?? null;
        if (!is_int($opener) || !is_int($closer) || $tokens[$opener]['line'] === $tokens[$closer]['line']) {
            return;
        }

        $this->processBracket($phpcsFile, $opener, $tokens);
        $this->processArgumentList($phpcsFile, $stackPtr, $this->indent);
        $this->checkBlankLines($phpcsFile, $stackPtr);
    }

    /**
     * Processes a multi-line function's arguments, unless a promoted parameter has a multi-line hook list.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the function keyword.
     * @param int $indent The number of spaces code should be indented.
     * @param string $type The type of the token the brackets belong to.
     * @return void
     */
    public function processArgumentList(File $phpcsFile, int $stackPtr, int $indent, string $type = 'function'): void
    {
        if (PropertyHookHelper::hasMultiLinePromotedPropertyHook($phpcsFile, $stackPtr)) {
            return;
        }

        parent::processArgumentList($phpcsFile, $stackPtr, $indent, $type);
        $this->checkParameterIndents($phpcsFile, $stackPtr, $indent);
    }

    /**
     * Check the indent of each line that starts a parameter, a `use` variable or their attributes, and of each closing
     * parenthesis, in a multi-line parameter or `use` list.
     *
     * A line that continues a parameter, such as a default value wrapped at an operator, isn't checked here, as
     * section 6.4 indents it once past its parameter. Nor is a line inside brackets, such as a default array's.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the function keyword.
     * @param int $indent The number of spaces in one indent level.
     */
    private function checkParameterIndents(File $phpcsFile, int $stackPtr, int $indent): void
    {
        $tokens = $phpcsFile->getTokens();
        $functionIndent = LayoutHelper::lineIndent($phpcsFile, $stackPtr);
        $lists = [[$tokens[$stackPtr]['parenthesis_opener'] ?? null, $tokens[$stackPtr]['parenthesis_closer'] ?? null]];
        $use = $this->findUse($phpcsFile, $stackPtr);
        if ($use !== null) {
            $lists[] = [$tokens[$use]['parenthesis_opener'] ?? null, $tokens[$use]['parenthesis_closer'] ?? null];
        }

        foreach ($lists as [$opener, $closer]) {
            if (!is_int($opener) || !is_int($closer) || $tokens[$opener]['line'] === $tokens[$closer]['line']) {
                continue;
            }

            for ($i = ($opener + 1); $i <= $closer; $i++) {
                if (!LayoutHelper::startsLine($phpcsFile, $i) || $tokens[$i]['code'] === T_WHITESPACE) {
                    continue;
                }

                $expected = $this->findExpectedParameterIndent($phpcsFile, $i, $opener, $closer);
                $found = $tokens[$i]['column'] - 1;
                $spaces = $expected === null ? null : ($functionIndent + ($expected * $indent));
                if ($spaces === null || $spaces === $found) {
                    continue;
                }

                $error = 'Multi-line function declaration not indented correctly; expected %s spaces but found %s';
                $code = self::CODE_PARAMETER_INDENT;
                FixerHelper::reportIndent($phpcsFile, $i, $spaces, $error, $code, [$spaces, $found]);
            }
        }
    }

    /**
     * Find how many levels past the function's line a line in a parameter or `use` list is indented, if it's a line
     * this sniff checks.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $first The position of the line's first token.
     * @param int $opener The position of the list's opening parenthesis.
     * @param int $closer The position of the list's closing parenthesis.
     * @return int|null 0 for the closing parenthesis, 1 for a line that starts an item or its attributes, or null for a
     *     line that isn't checked.
     */
    private function findExpectedParameterIndent(File $phpcsFile, int $first, int $opener, int $closer): ?int
    {
        if ($first === $closer) {
            return 0;
        }

        // The second and later lines of a comment are left alone, as the parent left a block comment's.
        $tokens = $phpcsFile->getTokens();
        $code = $tokens[$first]['code'];
        $isCommentStart = preg_match('~^(//|#|/\*)~', $tokens[$first]['content']) === 1;
        if (isset(Tokens::COMMENT_TOKENS[$code]) && !$isCommentStart) {
            return null;
        }

        // Inside an attribute's arguments, a default array or a hook list, something else sets the indent.
        if (TokenHelper::findContainer($phpcsFile, $first) !== $opener) {
            return null;
        }

        // An item, or its attributes, follows the opening parenthesis, a comma or an attribute block.
        $previous = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($first - 1), $opener, true);
        $starts = [T_COMMA, T_ATTRIBUTE_END];

        return $previous === $opener || in_array($tokens[$previous]['code'] ?? null, $starts, true) ? 1 : null;
    }

    /**
     * Processes single-line declarations.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @param array<int, array<string, mixed>> $tokens The stack of tokens that make up the file.
     * @return void
     */
    public function processSingleLineDeclaration(File $phpcsFile, int $stackPtr, array $tokens): void
    {
        if (EmptyBodyHelper::isOnOneLine($phpcsFile, $stackPtr)) {
            return;
        }

        parent::processSingleLineDeclaration($phpcsFile, $stackPtr, $tokens);
    }

    /**
     * Processes multi-line declarations, then checks their blank lines.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     * @param array<int, array<string, mixed>> $tokens The stack of tokens that make up the file.
     * @return void
     */
    public function processMultiLineDeclaration(File $phpcsFile, int $stackPtr, array $tokens): void
    {
        parent::processMultiLineDeclaration($phpcsFile, $stackPtr, $tokens);
        $this->checkBlankLines($phpcsFile, $stackPtr);
    }

    /**
     * Report each blank line straight after the opening parenthesis of the parameter list or the `use` list, or after
     * an attribute, as BlankLine, and each one straight before its closing parenthesis, as BlankLineBeforeCloseBracket.
     * A blank line between two items is allowed.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the function keyword.
     */
    private function checkBlankLines(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $lists = [[$tokens[$stackPtr]['parenthesis_opener'] ?? null, $tokens[$stackPtr]['parenthesis_closer'] ?? null]];

        // A closure's use list is checked too, as the parent does.
        $use = $this->findUse($phpcsFile, $stackPtr);
        if ($use !== null) {
            $lists[] = [$tokens[$use]['parenthesis_opener'] ?? null, $tokens[$use]['parenthesis_closer'] ?? null];
        }

        foreach ($lists as [$opener, $closer]) {
            if (!is_int($opener) || !is_int($closer)) {
                continue;
            }

            $blankLines = [
                ...LayoutHelper::findBlankLinesAtStartAndEnd($phpcsFile, $opener, $closer),
                ...$this->findBlankLinesAfterAttributes($phpcsFile, $opener, $closer),
            ];

            // The text only puts the first item on the line after the opening parenthesis, so a blank line before
            // the closing one has its own code, which the PERCS31 ruleset excludes.
            $last = $phpcsFile->findPrevious(T_WHITESPACE, ($closer - 1), $opener, true);
            foreach (array_unique($blankLines) as $blankLine) {
                $isAtEnd = $last !== false && $last !== $opener && $blankLine > $last;
                $error = $isAtEnd
                    ? 'Blank line not allowed before the closing parenthesis of a multi-line function declaration'
                    : 'Blank line not allowed here in a multi-line function declaration';
                $code = $isAtEnd ? self::CODE_BLANK_LINE_BEFORE_CLOSE_BRACKET : self::CODE_BLANK_LINE;
                if ($phpcsFile->addFixableError($error, $blankLine, $code)) {
                    $phpcsFile->fixer->replaceToken($blankLine, '');
                }
            }
        }
    }

    /**
     * Find the blank lines straight after an attribute block in a parameter list. Section 12.2 says there MUST NOT be
     * any blank lines between the attributes and the structure, so one between an attribute and its parameter, or
     * between two attribute blocks, is reported.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $opener The position of the list's opening parenthesis.
     * @param int $closer The position of the list's closing parenthesis.
     * @return list<int> The position of each blank line's whitespace token.
     */
    private function findBlankLinesAfterAttributes(File $phpcsFile, int $opener, int $closer): array
    {
        $tokens = $phpcsFile->getTokens();
        $blankLines = [];
        for ($i = ($opener + 1); $i < $closer; $i++) {
            if (!LayoutHelper::isBlankLine($phpcsFile, $i)) {
                continue;
            }

            $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($i - 1), $opener, true);
            if ($previous !== false && $tokens[$previous]['code'] === T_ATTRIBUTE_END) {
                $blankLines[] = $i;
            }
        }

        return $blankLines;
    }
}
