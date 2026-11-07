<?php

// === VALID ===

enum Valid
{
    case Hearts;
    case BigJoker;
    case A;
    case Option2;
    case C2;
    case H1;
    case V8;
    case X10;
    case Http2;
    case B2B;
    case H2O;
    case X509Certificate;
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
    case HTTPStatus = 's';
    case XMLParser = 'x';
    case AClass = 'a';
    case IDs = 'i';
    case HTTP2 = '2';
}
