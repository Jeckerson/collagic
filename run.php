<?php

require 'vendor/autoload.php';
require 'src/Collagic.php';

use Collagic\Collagic;

(new Collagic())->generate([
    'input/1.jpg',
    'input/2.jpg',
    'input/3.jpg',
]);
