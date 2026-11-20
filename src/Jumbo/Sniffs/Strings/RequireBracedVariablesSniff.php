<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\Strings;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Requires braces around every variable interpolated in a double-quoted string or a heredoc, as in `"Hi {$name}"`.
 *
 * A variable written without braces, `$name`, takes any property read or array access after it, so `"$user->name"`
 * becomes `"{$user->name}"`. An unquoted array key is a string there, so `"$row[id]"` becomes `"{$row['id']}"`. A
 * `${name}` is left alone, as SlevomatCodingStandard.Strings.DisallowVariableParsing reports it.
 */
class RequireBracedVariablesSniff implements Sniff
{
    public const string CODE_MISSING_BRACES = 'MissingBraces';

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_DOUBLE_QUOTED_STRING, T_START_HEREDOC];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the string's first token, or the heredoc's opener.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $isHeredoc = $tokens[$stackPtr]['code'] === T_START_HEREDOC;
        $bodyCode = $isHeredoc ? T_HEREDOC : T_DOUBLE_QUOTED_STRING;

        // A string over several lines is several tokens, which are checked together, from the first.
        if (!$isHeredoc && ($tokens[($stackPtr - 1)]['code'] ?? null) === T_DOUBLE_QUOTED_STRING) {
            return;
        }

        $first = $isHeredoc ? $stackPtr + 1 : $stackPtr;
        $last = $first - 1;
        $body = '';
        while (($tokens[($last + 1)]['code'] ?? null) === $bodyCode) {
            $last++;
            $body .= $tokens[$last]['content'];
        }

        if ($last < $first) {
            return;
        }

        $closer = $isHeredoc ? $tokens[($last + 1)]['content'] ?? '' : '';
        $prefix = '<?php ' . ($isHeredoc ? $tokens[$stackPtr]['content'] : '');
        $suffix = $isHeredoc ? "{$closer};" : '';
        $parts = token_get_all("{$prefix}{$body}{$suffix}");
        [$fixed, $variables] = $this->brace($parts);
        if ($variables === []) {
            return;
        }

        $fixed = substr($fixed, strlen($prefix), (strlen($fixed) - strlen($prefix) - strlen($suffix)));
        $fix = false;
        foreach ($variables as $variable) {
            $fix = $phpcsFile->addFixableError(
                'The variable %s in a string must be in braces.',
                $first,
                self::CODE_MISSING_BRACES,
                [$variable],
            ) || $fix;
        }

        if ($fix) {
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($first, $fixed);
            for ($i = ($first + 1); $i <= $last; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }

            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * Rewrite code with braces around each variable interpolated without them.
     *
     * @param list<array{int, string, int}|string> $parts The code's PHP tokens.
     * @return array{string, list<string>} The code, and the variables written without braces, as they're written.
     */
    private function brace(array $parts): array
    {
        $variables = [];
        $code = '';
        $depth = 0;
        $count = count($parts);
        for ($i = 0; $i < $count; $i++) {
            $part = $parts[$i];
            $text = is_array($part) ? $part[1] : $part;
            if (is_array($part) && in_array($part[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)) {
                $depth++;
            } elseif ($depth > 0 && $text === '{') {
                $depth++;
            } elseif ($depth > 0 && $text === '}') {
                $depth--;
            }

            if ($depth > 0 || !is_array($part) || $part[0] !== T_VARIABLE) {
                $code .= $text;
                continue;
            }

            [$written, $braced, $i] = $this->readVariable($parts, $i);
            $variables[] = $written;
            $code .= "{{$braced}}";
        }

        return [$code, $variables];
    }

    /**
     * Read a variable written without braces, with any property read or array access after it.
     *
     * @param list<array{int, string, int}|string> $parts The code's PHP tokens.
     * @param int $index The index of the variable.
     * @return array{string, string, int} The variable as it's written, as it's written in braces, and the index of its
     * last token.
     */
    private function readVariable(array $parts, int $index): array
    {
        $written = $parts[$index][1];
        $braced = $written;
        $next = $parts[($index + 1)] ?? null;
        if (is_array($next) && in_array($next[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) {
            $name = is_array($parts[($index + 2)] ?? null) ? $parts[($index + 2)][1] : '';
            $written .= "{$next[1]}{$name}";
            $braced .= "{$next[1]}{$name}";

            return [$written, $braced, ($index + 2)];
        }

        if ($next !== '[') {
            return [$written, $braced, $index];
        }

        $key = '';
        $writtenKey = '';
        for ($i = ($index + 2); isset($parts[$i]) && $parts[$i] !== ']'; $i++) {
            $part = $parts[$i];
            $text = is_array($part) ? $part[1] : $part;
            $writtenKey .= $text;
            $key .= is_array($part) && $part[0] === T_STRING ? "'{$text}'" : $text;
        }

        return ["{$written}[{$writtenKey}]", "{$braced}[{$key}]", $i];
    }
}
