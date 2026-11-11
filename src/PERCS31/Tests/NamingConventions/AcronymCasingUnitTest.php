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
            // Names the exclusion lists don't cover.
            197 => 1,
            199 => 1,
            223 => 1,
            225 => 1,
            227 => 1,
            229 => 1,
            231 => 1,
            242 => 1,
            244 => 1,
            253 => 1,
            255 => 1,
            // Capitals next to a digit or an underscore.
            264 => 1,
            266 => 1,
            268 => 1,
            // Trait aliases.
            279 => 1,
            280 => 1,
            281 => 1,
            282 => 1,
            // Global constants with a lower-case letter.
            311 => 1,
            312 => 1,
            313 => 1,
            314 => 1,
            315 => 1,
            316 => 1,
            // A trait alias in an anonymous class in a call, and a foreach's variable, not its `as`.
            321 => 1,
            325 => 1,
        ];
    }
}
