<?php

// A braced case body, with the next label on its closing brace's line. The original sniff never finished on this.
switch ($a) {
    case 2: {
        echo 2;
    } default:
        echo 3;
}

switch ($a) {
    case 1: {
        echo 1;
    } case 2:
        echo 2;
        break;
}
