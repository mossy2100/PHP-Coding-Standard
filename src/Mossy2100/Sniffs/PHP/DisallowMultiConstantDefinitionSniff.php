<?php

/**
 * Disallows defining multiple constants in a single statement, e.g. `const A = 1, B = 2;`.
 *
 * Forked from SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition to fix a false positive: the
 * upstream sniff scans for T_COMMA between `const` and the closing `;`, only skipping over commas inside a
 * short array (`[...]`) to avoid miscounting them as constant separators. It doesn't do the same for a
 * parenthesized list, so a single, valid constant declaration is misdetected as a multi-constant definition when its
 * value is a multi-argument `new` expression at file scope (e.g. `const I = new Complex(0, 1);`), a long-syntax array
 * (`array(1, 2)`) or a closure with more than one parameter (PHP 8.5+). This version also skips over parenthesized
 * expressions.
 *
 * Applies to constants declared at namespace/file scope as well as class constants, since T_CONST isn't
 * class-specific.
 *
 * Portions of this file are from Slevomat Coding Standard:
 *
 * @copyright 2015 Slevomat.cz, s.r.o.
 * @license   https://github.com/slevomat/coding-standard/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Mossy2100\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\FixerHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;

class DisallowMultiConstantDefinitionSniff implements Sniff
{
    public const string CODE_DISALLOWED_MULTI_CONSTANT_DEFINITION = 'DisallowedMultiConstantDefinition';

    /**
     * Modifier tokens that can come before `const`.
     *
     * @var array<int|string, int|string>
     */
    private const array MODIFIER_TOKENS = [
        T_FINAL => T_FINAL,
    ] + Tokens::SCOPE_MODIFIERS;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int, (int|string)>
     */
    public function register(): array
    {
        return [T_CONST];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $constantPointer The position of the current token in the stack passed in $tokens.
     * @return void
     */
    public function process(File $phpcsFile, int $constantPointer): void
    {
        $tokens = $phpcsFile->getTokens();

        $semicolonPointer = TokenHelper::findNext($phpcsFile, T_SEMICOLON, $constantPointer + 1);
        assert(is_int($semicolonPointer));

        $commaPointers = [];
        $nextPointer = $constantPointer;
        do {
            $nextPointer = TokenHelper::findNext(
                $phpcsFile,
                [T_COMMA, T_OPEN_SHORT_ARRAY, T_OPEN_PARENTHESIS],
                $nextPointer + 1,
                $semicolonPointer
            );

            if ($nextPointer === null) {
                break;
            }

            if ($tokens[$nextPointer]['code'] === T_OPEN_SHORT_ARRAY) {
                $nextPointer = $tokens[$nextPointer]['bracket_closer'];
                continue;
            }

            if ($tokens[$nextPointer]['code'] === T_OPEN_PARENTHESIS) {
                $nextPointer = $tokens[$nextPointer]['parenthesis_closer'];
                continue;
            }

            $commaPointers[] = $nextPointer;
        } while (true);

        if (count($commaPointers) === 0) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Use of multi constant definition is disallowed.',
            $constantPointer,
            self::CODE_DISALLOWED_MULTI_CONSTANT_DEFINITION
        );
        if (!$fix) {
            return;
        }

        // Each constant split off gets the same modifiers (e.g. `final public`) and type as the first one.
        [$indentation, $modifiers] = $this->getIndentationAndModifiers($phpcsFile, $constantPointer);

        $pointerAfterConst = TokenHelper::findNextEffective($phpcsFile, $constantPointer + 1);
        assert(is_int($pointerAfterConst));

        $type = $this->getType($phpcsFile, $pointerAfterConst, $semicolonPointer);

        $pointerBeforeSemicolon = TokenHelper::findPreviousEffective($phpcsFile, $semicolonPointer - 1);
        assert(is_int($pointerBeforeSemicolon));

        $docCommentPointer = DocCommentHelper::findDocCommentOpenPointer($phpcsFile, $constantPointer);
        $docComment = $docCommentPointer !== null
            ? trim(
                TokenHelper::getContent($phpcsFile, $docCommentPointer, $tokens[$docCommentPointer]['comment_closer'])
            )
            : null;

        $data = [];
        foreach ($commaPointers as $commaPointer) {
            $pointerBeforeComma = TokenHelper::findPreviousEffective($phpcsFile, $commaPointer - 1);
            assert(is_int($pointerBeforeComma));

            $pointerAfterComma = TokenHelper::findNextEffective($phpcsFile, $commaPointer + 1);
            assert(is_int($pointerAfterComma));

            $data[$commaPointer] = [
                'pointerBeforeComma' => $pointerBeforeComma,
                'pointerAfterComma'  => $pointerAfterComma,
            ];
        }

        $phpcsFile->fixer->beginChangeset();

        FixerHelper::add($phpcsFile, $constantPointer, ' ');

        FixerHelper::removeBetween($phpcsFile, $constantPointer, $pointerAfterConst);

        foreach ($commaPointers as $commaPointer) {
            FixerHelper::removeBetween($phpcsFile, $data[$commaPointer]['pointerBeforeComma'], $commaPointer);
            // Not FixerHelper::replace(), which converts leading spaces to tabs whenever tab-width is set (PSR-12
            // sets it to 4).
            $phpcsFile->fixer->replaceToken(
                $commaPointer,
                sprintf(
                    ';%s%s%s%sconst %s',
                    $phpcsFile->eolChar,
                    $docComment !== null
                        ? sprintf('%s%s%s', $indentation, $docComment, $phpcsFile->eolChar)
                        : '',
                    $indentation,
                    $modifiers,
                    $type
                )
            );

            FixerHelper::removeBetween($phpcsFile, $commaPointer, $data[$commaPointer]['pointerAfterComma']);
        }

        FixerHelper::removeBetween($phpcsFile, $pointerBeforeSemicolon, $semicolonPointer);

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Get the indentation of the declaration, and the modifiers before `const` (e.g. 'final public '), as written.
     *
     * The indentation is the whitespace at the start of the line. IndentationHelper::getIndentation() isn't used,
     * because it converts spaces to tabs and includes any tokens before the pointer on the line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $constantPointer The position of the `const` token.
     * @return array{string, string} The indentation, and the modifiers each followed by a space.
     */
    private function getIndentationAndModifiers(File $phpcsFile, int $constantPointer): array
    {
        $tokens = $phpcsFile->getTokens();
        $firstPointer = $constantPointer;
        $modifiers = '';

        $pointer = TokenHelper::findPreviousEffective($phpcsFile, $constantPointer - 1);
        while ($pointer !== null && isset(self::MODIFIER_TOKENS[$tokens[$pointer]['code']])) {
            $firstPointer = $pointer;
            $modifiers = $tokens[$pointer]['content'] . ' ' . $modifiers;
            $pointer = TokenHelper::findPreviousEffective($phpcsFile, $pointer - 1);
        }

        $firstPointerOnLine = TokenHelper::findFirstTokenOnLine($phpcsFile, $firstPointer);
        $isIndented = $firstPointerOnLine < $firstPointer && $tokens[$firstPointerOnLine]['code'] === T_WHITESPACE;
        $indentation = $isIndented ? $tokens[$firstPointerOnLine]['content'] : '';

        return [$indentation, $modifiers];
    }

    /**
     * Get the constant type, if any, followed by a space (e.g. 'int ').
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $pointerAfterConst The position of the first effective token after `const`.
     * @param int $semicolonPointer The position of the semicolon that ends the statement.
     * @return string The type followed by a space, or an empty string for an untyped constant.
     */
    private function getType(File $phpcsFile, int $pointerAfterConst, int $semicolonPointer): string
    {
        // The first constant's name is the last effective token before its `=`; anything before the name is the type.
        $equalsPointer = TokenHelper::findNext($phpcsFile, T_EQUAL, $pointerAfterConst, $semicolonPointer);
        assert(is_int($equalsPointer));
        $namePointer = TokenHelper::findPreviousEffective($phpcsFile, $equalsPointer - 1);
        assert(is_int($namePointer));

        if ($namePointer === $pointerAfterConst) {
            return '';
        }

        return trim(TokenHelper::getContent($phpcsFile, $pointerAfterConst, $namePointer - 1)) . ' ';
    }
}
