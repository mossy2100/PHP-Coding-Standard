<?php

/**
 * Disallows defining multiple constants in a single statement, e.g. `const A = 1, B = 2;`.
 *
 * Forked from SlevomatCodingStandard.Classes.DisallowMultiConstantDefinition to fix a false positive: the
 * upstream sniff scans for T_COMMA between `const` and the closing `;`, only skipping over commas inside a
 * short array (`[...]`) to avoid miscounting them as constant separators. It doesn't do the same for a
 * parenthesized argument list, so a single, valid constant declaration whose value is a multi-argument `new`
 * expression or function call (e.g. `const I = new Complex(0, 1);`) is misdetected as a multi-constant
 * definition. This version also skips over parenthesized expressions.
 *
 * Applies to constants declared at namespace/file scope as well as class constants -- T_CONST isn't
 * class-specific.
 */

declare(strict_types=1);

namespace OceanMoon\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\FixerHelper;
use SlevomatCodingStandard\Helpers\IndentationHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;

class DisallowMultiConstantDefinitionSniff implements Sniff
{
    public const string CODE_DISALLOWED_MULTI_CONSTANT_DEFINITION = 'DisallowedMultiConstantDefinition';

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

        $possibleVisibilityPointer = TokenHelper::findPreviousEffective($phpcsFile, $constantPointer - 1);
        $visibilityPointer = in_array($tokens[$possibleVisibilityPointer]['code'], Tokens::SCOPE_MODIFIERS, true)
            ? $possibleVisibilityPointer
            : null;
        $visibility = $visibilityPointer !== null ? $tokens[$possibleVisibilityPointer]['content'] : null;

        $pointerAfterConst = TokenHelper::findNextEffective($phpcsFile, $constantPointer + 1);
        assert(is_int($pointerAfterConst));

        $pointerBeforeSemicolon = TokenHelper::findPreviousEffective($phpcsFile, $semicolonPointer - 1);
        assert(is_int($pointerBeforeSemicolon));

        $indentation = IndentationHelper::getIndentation($phpcsFile, $visibilityPointer ?? $constantPointer);

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
            FixerHelper::replace(
                $phpcsFile,
                $commaPointer,
                sprintf(
                    ';%s%s%s%sconst ',
                    $phpcsFile->eolChar,
                    $docComment !== null
                        ? sprintf('%s%s%s', $indentation, $docComment, $phpcsFile->eolChar)
                        : '',
                    $indentation,
                    $visibility !== null ? sprintf('%s ', $visibility) : ''
                )
            );

            FixerHelper::removeBetween($phpcsFile, $commaPointer, $data[$commaPointer]['pointerAfterComma']);
        }

        FixerHelper::removeBetween($phpcsFile, $pointerBeforeSemicolon, $semicolonPointer);

        $phpcsFile->fixer->endChangeset();
    }
}
