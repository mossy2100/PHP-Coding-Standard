<?php

class Talker
{
    use Greeter {
        // Valid.
        sayHello as protected;
        sayBye as private myBye;
        sayHi as final;
        sayYo as final myYo;
        Greeter::wave as myWave;

        // Two spaces, and a line break, after the visibility.
        sayOne as private  myOne;
        sayTwo as protected
            myTwo;

        // After final.
        sayThree as final  myThree;

        // A comment in the way isn't fixed.
        sayFour as private /* Why. */  myFour;
    }
}

// An as elsewhere isn't a trait's.
foreach ($items as $key => $value) {
    echo $key, $value;
}
