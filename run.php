<?php

require 'vendor/autoload.php';

use Collagic\Collagic;

(new Collagic())->generate([
    'input/file_1.jpg',
    'input/file_2.jpg',
    'input/file_3.jpg',
    'input/file_4.jpg',
    'input/file_6.jpg',
]);
