<?php

// === VALID ===

class HttpClient
{
    public function getHttpResponse(): void {}

    // A single capital that isn't followed by another.
    public function getX(): void {}

    public function getX2Value(): void {}

    public function __construct() {}

    public function &getId(): array {}
}

function HttpStatus(): void {}

function A(): void {}

function parseJson(): void {}

function parse_url2(): void {}

$anonymous = new class {};
$closure = function (): void {};

// === NOT CHECKED HERE ===

// PascalCase is required of these and enums, so ValidClassName reports a run of capitals.
class HTTPClientTwo {}

interface XMLParser {}

trait JSONable {}

// As are enum cases and constants, which ValidEnumCaseName and UpperCaseConstantName report.
enum HTTPReported
{
    case HTTPOk;
    const HTTPCode = 1;
    const string XMLType = 'xml';
    const HTTP_OK = 200;
    const OK = 2;
}

class Constants
{
    const HTTP_OK = 200;
}

const HTTP_OK = 200;

// === FUNCTIONS AND METHODS, INVALID ===

class Invalid
{
    public function getHTTPResponse(): void {}

    public function parseURL(): void {}

    public function getID(): void {}

    public function &getURLs(): array {}

    // A one-letter word before a capital isn't allowed.
    public function getIDs(): array {}

    public function getXAxis(): void {}

    public function whenXIs(): void {}
}

function ID(): void {}

function AStatus(): void {}

function HTTPStatus(): void {}

function parseJSON(): void {}

// === OTHER NAMES, VALID ===

namespace App\Http;

class ValidVariables
{
    private int $userId = 0;

    private static int $count = 0;

    public function __construct(public int $orderId, int $itemId)
    {
        $total = $itemId;
        $server = $_SERVER['x'] . $_GET['x'] . $_POST['x'] . $GLOBALS['x'] . $_ENV['x'];

        // A property where it's used isn't checked, as it may belong to a class we don't control.
        $other = $this->userID + self::$userID + Other::$userID + $total;
    }
}

// === OTHER NAMES, INVALID ===

namespace App\HTTP;

namespace HTTP;

class InvalidVariables
{
    private int $userID = 0;

    private static int $countID = 0;

    public function __construct(public int $orderID, int $itemID)
    {
        $parseURL = $itemID;

        foreach ($parseURL as $itemURL) {
            echo $itemURL;
        }
    }
}

$globalID = 1;

// === IMPORT ALIASES, VALID ===

use Foo\Bar;
use Foo\Baz as HttpBaz;
use function Foo\bar as getId;
use const Foo\BAR as HTTP_OK_TWO;
use Foo\{Qux as HttpQux, Quux};
use Foo\{const BAZ as HTTP_BAZ};

class UsesTrait
{
    // A trait's `as` names a method, which isn't an import alias.
    use SomeTrait {
        run as getURL;
    }
}

function loop(array $items): void
{
    foreach ($items as $itemId) {
        echo $itemId;
    }
}

// === IMPORT ALIASES, INVALID ===

use Foo\Bar as HTTPBar;
use function Foo\bar as getID;
use Foo\{Baz as XMLBaz, Qux as JSONQux};
use Foo\One as OneID, Foo\Two as URLPath;

// === EXCLUSIONS ===

// phpcs:set PERCS31.NamingConventions.AcronymCasing excludedNames PERCS31,LegacyID,$otherID
namespace PERCS31\Sniffs\Arrays;

function LegacyID(): void {}

$otherID = 1;

// A part of a namespace that isn't excluded, and a name that only nearly matches.
namespace PERCS31\HTTP;

function OtherLegacyID(): void {}

// phpcs:set PERCS31.NamingConventions.AcronymCasing excludedNames
// phpcs:set PERCS31.NamingConventions.AcronymCasing excludedAcronyms HTTP, XML
function HTTPCode(): void {}

$xmlValue = parseXML();

function getHTTPResponse(): void {}

use Foo\Bar as HTTPBarTwo;

function XMLHTTPRequest(): void {}

// A plural isn't an acronym, a different acronym, a longer one, and a name with an excluded and another.
function loadHTTPs(): void {}

function URLClient(): void {}

function HTTPSClient(): void {}

function HTTPClientForURL(): void {}

// A single letter can be excluded too, such as a prefix.
// phpcs:set PERCS31.NamingConventions.AcronymCasing excludedAcronyms T, O
function TTypeTwo(): void {}

function OAuthTwo(): void {}

function ATypeTwo(): void {}

// phpcs:set PERCS31.NamingConventions.AcronymCasing excludedAcronyms HTTP, HTTPS, ID, URL
// The last capital before a lower-case letter starts a word, so these are `HTTP`, `HTTPS` and `HTTPS`.
function HTTPSecurity(): void {}

function HTTPSClientTwo(): void {}

function getHTTPS(): void {}

// These are `HTT` and `Pz`, `I` and `Ds`, `I` and `Dea`, and `UR` and `Ls`, none of which is approved.
function loadHTTPz(): void {}

function getIDs(): array {}

function IDea(): void {}

function getURLs(): array {}

// phpcs:set PERCS31.NamingConventions.AcronymCasing excludedAcronyms HTTPS
// This is `HTTP` and `Security`, and only `HTTPS` is approved.
function OtherHTTPSecurity(): void {}

function HTTPSecure(): void {}

// phpcs:set PERCS31.NamingConventions.AcronymCasing excludedAcronyms

// A digit separates capitals, and stays with the word on its left.
function B2B(): void {}

function H2O(): void {}

function MP3Player(): void {}

function user_ID(): void {}

function HTTP_client(): void {}
