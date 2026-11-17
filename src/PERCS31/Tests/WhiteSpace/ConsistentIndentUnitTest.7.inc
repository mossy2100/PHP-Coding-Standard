<?php

 // The first statement in a file, indented too far. The line before it holds the opening tag, which ends no statement,
 // so the parent never checked it. Its later lines are in an array, which this sniff leaves to other sniffs.
 $first = [
     1,
 ];

$second = 2;
