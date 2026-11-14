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

// The :: in a trait use block has no space around it. Elsewhere it isn't checked.
class Chatter
{
    use A, B {
        A::smallTalk insteadof B;
        B :: bigTalk as loudTalk;
        A ::mediumTalk as protected;
        B:: quietTalk as hush;
        A
            ::longTalk as speech;
    }

    public function talk(): string
    {
        return self :: NAME . static :: greet();
    }
}
