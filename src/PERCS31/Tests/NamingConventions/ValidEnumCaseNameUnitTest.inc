<?php

// === VALID ===

enum Valid
{
    case Hearts;
    case BigJoker;
    case Option2;
    case Http2;
    case X509Certificate;

    // A single capital is an initial, and can end a name, or come before a digit or another word.
    case ClassA;
    case Vector3D;
    case AClass;

    // An acronym in capitals is still PascalCase. AcronymCasing warns about it.
    case HTTPStatus;
    case XMLParser;
    case IDs;
}

// === INVALID ===

enum Invalid: string
{
    case hearts = 'h';
    case SPADES = 'S';
    case big_joker = 'j';
    case Big_Joker = 'J';
    case bigJoker = 'b';
    case OK = 'o';
    case HTTP2 = '2';
    case A = 'a';
    case C2 = 'c';
    case B2B = 'b2';
    case H2O = 'w';
}
