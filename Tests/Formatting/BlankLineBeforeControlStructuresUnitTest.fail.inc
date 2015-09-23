<?php

$a = [1];
foreach ($a as $i) {
    $c = $i;
}

$b = true;
if ($b) {
    for ($j = 0; $j < 2; $j++) {
        $b = $j;
    }
} else {
    if (false) {
        $e = 2;
    } elseif (1 < 2) {
        $f = 'foo';
    }

    $g = 'bar';
}
/*
 * this is a very long comment
 */
while (true) {
    if ($a) {
        //do stuff
    }

    //another comment
    $f = false;
}
//a comment
/** @var bool $b */
switch ($b) {
    case true:
        /** @var bool $b */
        $b = false;
        break;
    default:
        $b = true;
}

$h = 'foobar';

$i = 1;
while ($i < $j) {
    // ...

    $i++;
}
//comment here
if ($b) {
    //and another one here
    for ($j = 0; $j < 2; $j++) {
        //and here
        $b = $j;
    }
} else { //or here
    if (false) {
        $e = 2;
    } elseif (1 < 2) { //here too
        /*
         * and at last...
         */
        $f = 'foo';
    }

    /* ...this one */
    $g = 'bar';
}
