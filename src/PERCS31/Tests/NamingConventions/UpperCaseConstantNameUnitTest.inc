<?php

// === VALID ===

const GLOBAL_LIMIT = 1;
define('DEFINED_LIMIT', 2);

class Holder
{
    public const int CLASS_LIMIT = 3;
}

enum Suit: string
{
    case Hearts = 'h';

    // PascalCase, as PER Coding Style 3.1 recommends.
    public const string DefaultColour = 'red';

    // UPPER_CASE, which PER allows: a severity 4 warning, hidden at the default severity, which these tests run at.
    public const string FALLBACK_COLOUR = 'black';

    // PascalCase with a digit, or capitals separated by one.
    public const string C2 = 'c';
    public const string B2B = 'b';
}

// === INVALID ===

// PSR-1 only requires class constants in UPPER_CASE, so a global constant isn't checked by default.
const globalLimit = 1;
define('definedLimit', 2);
use const Foo\BAR as lowerAlias;

class LowerHolder
{
    public const int classLimit = 3;
}

enum LowerSuit: string
{
    case Hearts = 'h';

    // Neither PascalCase nor UPPER_CASE.
    public const string defaultColour = 'red';

    // A run of capitals, so not PascalCase.
    public const string HTTPStatus = 's';

    // UPPER_CASE with no underscore, which gets the warning.
    public const string OK = 'o';
}

// === GLOBAL CONSTANTS, WHEN CHECKED ===

// phpcs:set PERCS31.NamingConventions.UpperCaseConstantName checkGlobalConstants true
const CHECKED_LIMIT = 1;
define('CHECKED_DEFINED', 2);
const checkedLimit = 1;
define('checkedDefined', 2);
\define('checkedQualified', 3);
use const Foo\BAR as CHECKED_ALIAS;
use Foo\Bar as checkedClassAlias;
use function Foo\bar as checkedFunctionAlias;

foreach ($items as $checkedItem) {
    echo $checkedItem;
}

class CheckedTrait
{
    use SomeTrait {
        run as checkedMethod;
    }
}

use const Foo\BAR as checkedAlias;
use Foo\{const BAZ as checkedGroupAlias, Qux};
// phpcs:set PERCS31.NamingConventions.UpperCaseConstantName checkGlobalConstants false
