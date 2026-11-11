<?php

declare(strict_types=1);

namespace PERCS31\Tests\NamingConventions;

use PHP_CodeSniffer\Tests\Standards\AbstractSniffTestCase;

/**
 * Unit tests for PERCS31.NamingConventions.AcronymCasing sniff.
 */
class AcronymCasingUnitTest extends AbstractSniffTestCase
{
    /**
     * Returns the lines where errors should occur.
     *
     * @return array<int, int> Line number => error count.
     */
    protected function getErrorList(): array
    {
        return [];
    }

    /**
     * Returns the lines where warnings should occur.
     *
     * @return array<int, int> Line number => warning count.
     */
    protected function getWarningList(): array
    {
        return [
            // Classes, interfaces, traits, enums, enum cases and enum constants.
            76 => 1,
            78 => 1,
            80 => 1,
            82 => 1,
            84 => 1,
            86 => 1,
            88 => 1,
            89 => 1,
            90 => 1,
            // Functions and methods.
            97 => 1,
            99 => 1,
            101 => 1,
            103 => 1,
            106 => 1,
            108 => 1,
            110 => 1,
            // Namespaces, properties, parameters and variables.
            134 => 1,
            136 => 1,
            140 => 1,
            142 => 1,
            144 => 2,
            146 => 2,
            148 => 2,
            149 => 1,
            154 => 1,
            // Import aliases.
            182 => 1,
            183 => 1,
            184 => 2,
            185 => 2,
            // Names that excludedNames doesn't cover.
            197 => 1,
            199 => 1,
            211 => 1,
            // Acronyms that end before the last capital of a run.
            218 => 1,
            220 => 1,
            222 => 1,
            224 => 1,
            // Capitals next to a digit or an underscore.
            236 => 1,
            238 => 1,
            240 => 1,
            // Trait aliases.
            251 => 1,
            252 => 1,
            253 => 1,
            254 => 1,
            // Global constants with a lower-case letter.
            283 => 1,
            284 => 1,
            285 => 1,
            286 => 1,
            287 => 1,
            288 => 1,
            // A trait alias in an anonymous class in a call, and a foreach's variable, not its `as`.
            293 => 1,
            297 => 1,
        ];
    }
}
