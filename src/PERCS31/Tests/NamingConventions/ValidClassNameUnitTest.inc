<?php

// === VALID ===

class Foo {}

class A {}

class C2 {}

class B2B {}

class H2O {}

class Http2 {}

class X509Certificate {}

interface JsonParser {}

trait Jsonable {}

enum HttpStatus {}

enum Suit: string {}

$anonymous = new class {};

// === INVALID ===

class bar {}

class Foo_Bar {}

class HTTPClient {}

class XMLParser {}

class AClass {}

class FOO {}

class HTTP2 {}

class IDs {}

interface XMLReader {}

trait JSONable {}

interface lowerName {}

trait Under_Score {}

enum foo {}

enum foo_bar {}

enum HTTPStatus {}

enum HTTPCode: int {}

enum OK {}
