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
