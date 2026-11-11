<?php

// === VALID ===

class HttpClient
{
    public function getHttpResponse(): void {}

    // A single capital is an initial, not an acronym, wherever it is in the name.
    public function getX(): void {}

    public function getX2Value(): void {}

    public function getXAxis(): void {}

    public function whenXIs(): void {}

    // This is `get`, `I` and `Ds`.
    public function getIDs(): array {}

    public function __construct() {}

    public function &getId(): array {}
}

function HttpStatus(): void {}

function A(): void {}

function AStatus(): void {}

function parseJson(): void {}

function parse_url2(): void {}

$anonymous = new class {};
$closure = function (): void {};

// === CLASSES AND ENUMS, VALID ===

// A single capital is an initial, not an acronym.
class ClassA {}

interface IAnimal {}

enum EDayOfWeek
{
    case OptionA;
    const VersionB = 1;
}

// No lower-case letter, so there's no acronym to write like a word. ValidClassName and ValidEnumCaseName report these,
// and UPPER_CASE is allowed for an enum constant.
class FOO {}

enum HTTP
{
    case OK;
    const HTTP_OK = 200;
    const ID = 2;
}

// A class constant outside an enum must be in UPPER_CASE, so it isn't checked here.
class Constants
{
    const HTTP_OK = 200;
}

const HTTP_OK = 200;

// A class where it's used isn't checked, as it may belong to a package we don't control.
$client = new HTTPClient();

// === CLASSES AND ENUMS, INVALID ===

class HTTPClientTwo {}

interface XMLParser {}

trait JSONable {}

class AICodingStandard {}

class IHeartAI {}

enum HTTPReported
{
    case HTTPOk;
    const HTTPCode = 1;
    const string XMLType = 'xml';
}

// === FUNCTIONS AND METHODS, INVALID ===

class Invalid
{
    public function getHTTPResponse(): void {}

    public function parseURL(): void {}

    public function getID(): void {}

    public function &getURLs(): array {}
}

function ID(): void {}

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
    // A trait's alias, which is checked further down too.
    use SomeTrait {
        run as getUrl;
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

// A class, an enum case and an enum constant can be excluded too.
// phpcs:set PERCS31.NamingConventions.AcronymCasing excludedNames HTTPKernel,HTTPPost,XMLVersion
class HTTPKernel {}

enum Format
{
    case HTTPPost;
    const XMLVersion = 1;
}

class HTTPKernelTwo {}

// phpcs:set PERCS31.NamingConventions.AcronymCasing excludedNames

// === WORDS ===

// The last capital before a lower-case letter starts a word, so these have `HTTP`, `HTTPS`, `HTT` and `UR`.
function HTTPSecurity(): void {}

function getHTTPS(): void {}

function loadHTTPs(): void {}

function getURLs(): array {}

// These are `I` and `Ds`, and `I` and `Dea`. A single capital isn't an acronym.
function getIDs(): array {}

function IDea(): void {}

// A digit separates capitals, and stays with the word on its left.
function B2B(): void {}

function H2O(): void {}

function MP3Player(): void {}

function user_ID(): void {}

function HTTP_client(): void {}

// === TRAIT ALIASES ===

class TraitAliases
{
    use OneTrait, TwoTrait {
        first as getId;
        second as protected getName;
        third as protected;
        OneTrait::fourth insteadof TwoTrait;
        fifth as getID;
        sixth as protected getURL;
        OneTrait::seventh as private parseXML;
        eighth as final parseJSON;
    }

    // phpcs:set PERCS31.NamingConventions.AcronymCasing excludedNames parseXML
    use ThreeTrait {
        ninth as parseXML;
    }
    // phpcs:set PERCS31.NamingConventions.AcronymCasing excludedNames
}

// === GLOBAL CONSTANTS ===

const HTTP_LIMIT = 1;
const URLID = 2;
const userId = 3;
define('HTTP_TIMEOUT', 4);
define('userId', 5);
\define('XML_LIMIT', 6);
define('Foo\Bar\URL_LIMIT', 7);
$object->define('userID', 8);
Foo::define('userID', 9);
define($userName, 10);
use const Foo\Bar as bazId;

class ClassConstant
{
    const userID = 11;
}

const userID = 12;
const HTTP_id = 13;
define('parseURL', 14);
\define("getID", 15);
define('Foo\Bar\userID', 16);
use const Foo\Bar as otherID;

// A trait alias in an anonymous class that's a call's argument. Its `as` is in parentheses, but isn't a foreach's.
register(new class {
    use Greets {
        hello as protected sayHTTPHello;
    }
});

foreach ($callURLs as $callId) {
}
