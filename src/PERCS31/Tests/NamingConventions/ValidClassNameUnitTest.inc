<?php

// === VALID ===

class Foo {}

class Http2 {}

class X509Certificate {}

// A single capital is an initial, and can end a name, or come before a digit or another word.
class ClassA {}

class Vector3D {}

class AClass {}

interface IAnimal {}

enum EDayOfWeek {}

interface JsonParser {}

trait Jsonable {}

enum HttpStatus {}

enum Suit: string {}

$anonymous = new class {};

// An acronym in capitals is still PascalCase. AcronymCasing warns about it.
class HTTPClient {}

class XMLParser {}

class IDs {}

interface XMLReader {}

trait JSONable {}

enum HTTPStatus {}

enum HTTPCode: int {}

// === INVALID ===

class bar {}

class Foo_Bar {}

interface lowerName {}

trait Under_Score {}

enum foo {}

enum foo_bar {}

// No lower-case letter.
class A {}

class C2 {}

class B2B {}

class H2O {}

class FOO {}

class HTTP2 {}

enum OK {}
