<?php

// === VALID ===

class HttpClient
{
    public function getHttpResponse(): void {}

    public function getXAxis(): void {}

    public function __construct() {}

    public function &getId(): array {}
}

class A {}

class AClass {}

class ASync {}

class X509Certificate {}

class OAuthToken {}

interface JsonParser {}

trait Jsonable {}

enum HttpStatus
{
    case NotFound;
    case A;
}

function parseJson(): void {}

function parse_url2(): void {}

$anonymous = new class {};
$closure = function (): void {};

// === INVALID ===

class HTTPClient
{
    public function getHTTPResponse(): void {}

    public function parseURL(): void {}

    public function getID(): void {}

    public function &getURLs(): array {}

    public function getIDs(): array {}
}

class ID {}

class IOStream {}

interface XMLParser {}

trait JSONable {}

enum HTTPStatus
{
    case HTTPOk;
}

function parseJSON(): void {}

// === OTHER NAMES, VALID ===

namespace App\Http;

const HTTP_OK = 200;

enum ValidNames
{
    const VALID_CONSTANT = 1;
    const NotFound = 2;
    const string OkStatus = 'ok';
}

class ValidVariables
{
    private int $userId = 0;

    private static int $count = 0;

    const HTTP_OK = 200;

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

enum InvalidNames
{
    const HTTPOk = 1;
    const string XMLType = 'xml';
}

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

// === EXCLUSIONS ===

// phpcs:set PERCS31.NamingConventions.AcronymCasing excludedNames PERCS31,LegacyID,$otherID
namespace PERCS31\Sniffs\Arrays;

class LegacyID {}

$otherID = 1;

// A part of a namespace that isn't excluded, and a name that only nearly matches.
namespace PERCS31\HTTP;

class LegacyIDs {}

// phpcs:set PERCS31.NamingConventions.AcronymCasing excludedNames
// phpcs:set PERCS31.NamingConventions.AcronymCasing excludedAcronyms HTTP, XML
class HTTPClient {}

$xmlValue = parseXML();

function getHTTPResponse(): void {}

function loadHTTPs(): void {}

class XMLHTTPRequest {}

// A different acronym, a longer one, and a name with an excluded and another.
class URLClient {}

class HTTPSClient {}

class HTTPClientForURL {}
// phpcs:set PERCS31.NamingConventions.AcronymCasing excludedAcronyms
