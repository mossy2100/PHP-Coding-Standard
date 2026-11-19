<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Formatting;

use Jumbo\Helpers\ExpressionHelper;
use Jumbo\Helpers\ExpressionNode;
use PERCS31\Helpers\FixerHelper;
use PERCS31\Helpers\LayoutHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Requires an expression to be split across lines only where it must be, at its loosest operators.
 *
 * PER Coding Style 3.1, section 6.4, lets an expression be split before any operator, so this is an extra rule. The
 * sniff works out the one layout an expression should have, and reports it if it's written any other way:
 * - If the expression fits within the line length on one line, it's written on one line.
 * - Otherwise, it's split before the operators that bind most loosely, outside any grouping parentheses, such as the
 *   `||` in `$a && $b || $c`. Each line holds as many operands as fit, so a line only breaks before the operator whose
 *   operand wouldn't fit on it. Each new line is indented once past the line the expression starts on, or lined up
 *   with the first line of a control structure's condition, when that starts its own line.
 * - An operand too long for a line of its own is split the same way, at its own loosest operators. One without
 *   parentheses keeps the same indent, as `$b` then `&& $c` does in `$a` then `|| $b` then `&& $c`. One in grouping
 *   parentheses keeps them with its contents, and its new lines are indented once past the line with its `(`.
 *
 * An expression ends at anything that isn't part of it: a `;`, `,`, `=>`, assignment, ternary `?` or `:`, or the
 * brackets of a call, array or control structure. Each argument of a call is its own expression. An operand isn't
 * changed inside, so the sniff skips an expression with a line break inside an operand, such as a multi-line call or
 * closure, and one with a comment, a heredoc, a nowdoc, a multi-line string or an arrow function. It also skips a
 * `case` condition. It never splits a ternary's condition or value, which share their lines with the rest of the
 * ternary, or an expression written on one line that's too long, unless it's a whole statement, a value after an
 * assignment, `return`, `=>` or the like, or a control structure's condition on its own line, where a split can't
 * clash with the layout of the code around it.
 *
 * It covers every binary operator except the assignment operators and `and`, `or` and `xor`, which bind more loosely
 * than assignment. Like PERCS31.Operators.OperatorPlacement, it puts each operator at the start of its line, and it
 * reports a `.` at the end of a line, which that sniff leaves to Universal.Operators.ConcatPosition.
 */
class ExpressionFormatSniff implements Sniff
{
    public const string CODE_SHOULD_BE_SINGLE_LINE = 'ShouldBeSingleLine';

    public const string CODE_INCORRECT_SPLIT = 'IncorrectSplit';

    /**
     * Tokens after which an expression on one line that's too long may be split.
     *
     * @var list<int|string>
     */
    private const array SPLITTABLE_AFTER = [
        T_SEMICOLON,
        T_OPEN_CURLY_BRACKET,
        T_CLOSE_CURLY_BRACKET,
        T_OPEN_TAG,
        T_RETURN,
        T_ECHO,
        T_PRINT,
        T_THROW,
        T_YIELD,
        T_YIELD_FROM,
        T_DOUBLE_ARROW,
        T_MATCH_ARROW,
        T_FN_ARROW,
    ];

    /**
     * Control structures whose condition's lines line up with its first line.
     *
     * @var list<int|string>
     */
    private const array CONDITION_OWNERS = [T_IF, T_ELSEIF, T_WHILE, T_SWITCH, T_MATCH];

    /**
     * Maximum line length.
     */
    public int $maxLineLength = 120;

    /**
     * The file whose expressions are in $checked.
     */
    private string $checkedFile = '';

    /**
     * The first token of each expression already checked in the current file, as each of its operators is registered.
     *
     * @var array<int, true>
     */
    private array $checked = [];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return array_keys(ExpressionHelper::precedences());
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the operator.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        if (!ExpressionHelper::isBinary($phpcsFile, $stackPtr)) {
            return;
        }

        if ($phpcsFile->getFilename() !== $this->checkedFile) {
            $this->checkedFile = $phpcsFile->getFilename();
            $this->checked = [];
        }

        $region = ExpressionHelper::findExpression($phpcsFile, $stackPtr);
        if ($region === null || isset($this->checked[$region[0]])) {
            return;
        }

        $this->checked[$region[0]] = true;
        $expression = ExpressionHelper::read($phpcsFile, $stackPtr);
        if ($expression === null) {
            return;
        }

        [$root, $before, $after] = $expression;
        $start = $root->start;
        $end = $root->end;
        $tokens = $phpcsFile->getTokens();
        $column = $tokens[$start]['column'] - 1;
        $lineIndent = LayoutHelper::lineIndent($phpcsFile, $start);
        $isCondition = $this->isConditionOnOwnLine($phpcsFile, $start, $before);
        $continuationIndent = $isCondition ? $column : ($lineIndent + LayoutHelper::INDENT);
        $breaks = [];
        $trailing = $this->trailingLength($phpcsFile, $end);
        $this->layout($phpcsFile, $root, $column, $lineIndent, $continuationIndent, $trailing, $breaks);

        $current = [];
        $misplaced = false;
        $this->findCurrentBreaks($phpcsFile, $root, $current, $misplaced);
        ksort($current);
        ksort($breaks);
        if (!$misplaced && array_keys($current) === array_keys($breaks)) {
            return;
        }

        // A ternary's condition or value shares its lines with the rest of the ternary, so it's never split.
        $ternary = [T_INLINE_THEN, T_INLINE_ELSE];
        $isInTernary = in_array($tokens[$after]['code'] ?? null, $ternary, true)
            || ($before >= 0 && in_array($tokens[$before]['code'], $ternary, true));
        if ($breaks !== [] && $isInTernary) {
            return;
        }

        // An expression on one line is only split where that can't clash with the layout around it.
        $isSplittable = $isCondition || $before < 0 || in_array($tokens[$before]['code'], self::SPLITTABLE_AFTER, true)
            || isset(Tokens::ASSIGNMENT_TOKENS[$tokens[$before]['code']]);
        if ($current === [] && !$misplaced && !$isSplittable) {
            return;
        }

        $error = 'An expression too long for %s characters must be split only before its loosest operators, with as '
            . 'many operands on each line as fit.';
        $code = self::CODE_INCORRECT_SPLIT;
        if ($breaks === []) {
            $error = 'An expression split across lines fits within %s characters, so it must be written on one line.';
            $code = self::CODE_SHOULD_BE_SINGLE_LINE;
        }

        if ($phpcsFile->addFixableError($error, $start, $code, [$this->maxLineLength])) {
            $phpcsFile->fixer->beginChangeset();
            $this->applyLayout($phpcsFile, $root, $breaks);
            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * Check whether an expression is a control structure's condition, starting its own line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $start The position of the expression's first token.
     * @param int $before The position of the token before it, or -1.
     * @return bool True if it is.
     */
    private function isConditionOnOwnLine(File $phpcsFile, int $start, int $before): bool
    {
        $tokens = $phpcsFile->getTokens();
        $owner = $before >= 0 ? ($tokens[$before]['parenthesis_owner'] ?? null) : null;

        return is_int($owner) && $tokens[$before]['code'] === T_OPEN_PARENTHESIS
            && in_array($tokens[$owner]['code'], self::CONDITION_OWNERS, true)
            && LayoutHelper::startsLine($phpcsFile, $start);
    }

    /**
     * Get the length of the code after an expression on its last line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $end The position of the expression's last token.
     * @return int The number of characters, without trailing whitespace.
     */
    private function trailingLength(File $phpcsFile, int $end): int
    {
        $tokens = $phpcsFile->getTokens();
        $text = '';
        for ($i = ($end + 1); isset($tokens[$i]); $i++) {
            $content = $tokens[$i]['content'];
            $newline = strpos($content, "\n");
            if ($newline !== false) {
                $text .= substr($content, 0, $newline);
                break;
            }

            $text .= $content;
        }

        return mb_strlen(rtrim($text));
    }

    /**
     * Work out where a node's lines break, splitting it only if it doesn't fit, and as few times as it can.
     *
     * @param File $phpcsFile The file being scanned.
     * @param ExpressionNode $node The node.
     * @param int $column The number of characters before the node on its first line.
     * @param int $lineIndent The indent of the node's first line.
     * @param int $indent The indent for a level's new lines.
     * @param int $trailing The number of characters after the node on its last line.
     * @param array<int, int> $breaks The operators to break the line before, with each one's indent.
     * @return int The number of characters before the node's end, on its last line.
     */
    private function layout(
        File $phpcsFile,
        ExpressionNode $node,
        int $column,
        int $lineIndent,
        int $indent,
        int $trailing,
        array &$breaks,
    ): int {
        $flatEnd = $column + $node->flatLength;
        if ($node->kind === ExpressionNode::OPERAND || ($flatEnd + $trailing) <= $this->maxLineLength) {
            return $flatEnd;
        }

        // A group's contents go on its first line, and any new lines inside it are indented once past the line with
        // its `(`, as PERCS31.Operators.OperatorPlacement requires.
        if ($node->kind === ExpressionNode::GROUP) {
            $inner = $node->children[0];
            $innerColumn = $column + ($node->flatLength - $inner->flatLength - 1);
            $innerIndent = $lineIndent + LayoutHelper::INDENT;

            return $this->layout(
                $phpcsFile,
                $inner,
                $innerColumn,
                $lineIndent,
                $innerIndent,
                ($trailing + 1),
                $breaks,
            ) + 1;
        }

        // Each operand joins the line it follows if it fits, and starts a new line, with its operator, if it doesn't.
        $tokens = $phpcsFile->getTokens();
        $lastIndex = count($node->operators);
        $end = $this->layout($phpcsFile, $node->children[0], $column, $lineIndent, $indent, 0, $breaks);
        foreach ($node->operators as $index => $operator) {
            $child = $node->children[($index + 1)];
            $childTrailing = ($index + 1) === $lastIndex ? $trailing : 0;
            $operatorLength = mb_strlen($tokens[$operator]['content']);
            $joinedEnd = $end + $operatorLength + 2 + $child->flatLength;
            if (($joinedEnd + $childTrailing) <= $this->maxLineLength) {
                $end = $joinedEnd;
                continue;
            }

            $breaks[$operator] = $indent;
            $childColumn = $indent + $operatorLength + 1;
            $end = $this->layout($phpcsFile, $child, $childColumn, $indent, $indent, $childTrailing, $breaks);
        }

        return $end;
    }

    /**
     * Find the operators a node's lines break at now, and whether any line break is somewhere else.
     *
     * @param File $phpcsFile The file being scanned.
     * @param ExpressionNode $node The node.
     * @param array<int, true> $current The operators with a line break before or after them.
     * @param bool $misplaced Set if a `.` ends a line, or a group's parentheses don't hug its contents.
     */
    private function findCurrentBreaks(File $phpcsFile, ExpressionNode $node, array &$current, bool &$misplaced): void
    {
        $tokens = $phpcsFile->getTokens();
        foreach ($node->operators as $operator) {
            $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($operator - 1), null, true);
            $next = $phpcsFile->findNext(T_WHITESPACE, ($operator + 1), null, true);
            if ($previous !== false && $tokens[$previous]['line'] !== $tokens[$operator]['line']) {
                $current[$operator] = true;
            }

            if ($next !== false && $tokens[$next]['line'] !== $tokens[$operator]['line']) {
                $current[$operator] = true;
                $misplaced = $misplaced || $tokens[$operator]['code'] === T_STRING_CONCAT;
            }
        }

        if ($node->kind === ExpressionNode::GROUP && is_int($node->opener) && is_int($node->closer)) {
            $inner = $node->children[0];
            $misplaced = $misplaced || $tokens[$node->opener]['line'] !== $tokens[$inner->start]['line']
                || $tokens[$node->closer]['line'] !== $tokens[$inner->end]['line'];
        }

        foreach ($node->children as $child) {
            $this->findCurrentBreaks($phpcsFile, $child, $current, $misplaced);
        }
    }

    /**
     * Set the whitespace around each operator and inside each group's parentheses to match the layout.
     *
     * @param File $phpcsFile The file being fixed.
     * @param ExpressionNode $node The node.
     * @param array<int, int> $breaks The operators to break the line before, with each one's indent.
     */
    private function applyLayout(File $phpcsFile, ExpressionNode $node, array $breaks): void
    {
        foreach ($node->operators as $index => $operator) {
            $before = isset($breaks[$operator]) ? $phpcsFile->eolChar . str_repeat(' ', $breaks[$operator]) : ' ';
            $this->setGap($phpcsFile, $node->children[$index]->end, $operator, $before);
            $this->setGap($phpcsFile, $operator, $node->children[($index + 1)]->start, ' ');
        }

        if ($node->kind === ExpressionNode::GROUP && is_int($node->opener) && is_int($node->closer)) {
            $this->setGap($phpcsFile, $node->opener, $node->children[0]->start, '');
            $this->setGap($phpcsFile, $node->children[0]->end, $node->closer, '');
        }

        foreach ($node->children as $child) {
            $this->applyLayout($phpcsFile, $child, $breaks);
        }
    }

    /**
     * Replace the whitespace between two tokens, if it isn't what's wanted already.
     *
     * @param File $phpcsFile The file being fixed.
     * @param int $from The token before the gap.
     * @param int $to The token after the gap.
     * @param string $content The whitespace wanted.
     */
    private function setGap(File $phpcsFile, int $from, int $to, string $content): void
    {
        if ($phpcsFile->getTokensAsString(($from + 1), ($to - $from - 1)) !== $content) {
            FixerHelper::setBetween($phpcsFile, $from, $to, $content);
        }
    }
}
