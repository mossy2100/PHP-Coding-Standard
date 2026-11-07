<?php

declare(strict_types=1);

namespace Jumbo\Sniffs\ControlStructures;

use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\ControlStructures\RequireShortTernaryOperatorSniff as SlevomatSniff;

/**
 * Requires `$a ?: $b` instead of `$a ? $a : $b`, without crashing on a nullable type in a `set` hook's parameter.
 *
 * PHP_CodeSniffer doesn't know about hooks, so it reads the `?` in `set(?int $value)` as a ternary's. The parent then
 * looks for a `:` that isn't there, and the error stops the file being checked. This sniff skips that `?`, and leaves
 * everything else to the parent.
 */
class RequireShortTernaryOperatorSniff extends SlevomatSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $inlineThenPointer The position of the `?`.
     */
    public function process(File $phpcsFile, int $inlineThenPointer): void
    {
        if (PropertyHookHelper::isHookParameterType($phpcsFile, $inlineThenPointer)) {
            return;
        }

        parent::process($phpcsFile, $inlineThenPointer);
    }
}
