<?php

declare(strict_types=1);

namespace Jumbo\Helpers;

use PERCS31\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;

/**
 * Skips a function with a hook on a promoted constructor parameter, which a Slevomat sniff misreads.
 *
 * PHP_CodeSniffer's tokenizer doesn't know about property hooks, so Slevomat's function sniffs read a hook's code as
 * part of the parameter list. PERCS31.Classes.PromotedPropertyHooks checks those parameters instead.
 */
trait SkipsPromotedPropertyHookTrait
{
    /**
     * Processes this test, when one of its tokens is encountered, unless a promoted parameter has a hook.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $functionPointer The position of the function keyword.
     */
    public function process(File $phpcsFile, int $functionPointer): void
    {
        if (PropertyHookHelper::hasPromotedPropertyHook($phpcsFile, $functionPointer)) {
            return;
        }

        parent::process($phpcsFile, $functionPointer);
    }
}
