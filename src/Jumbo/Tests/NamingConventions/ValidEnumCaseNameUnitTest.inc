<?php

// === VALID ===

enum Valid
{
    case Hearts;
    case BigJoker;
    case A;
    case Option2;
    case HTTPStatus;
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
}
