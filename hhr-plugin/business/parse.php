<?php
error_reporting(E_ALL);

require 'Hand.php';
require 'TablePosition.php';
require 'Parser/Abstract.php';
require 'Parser/Winamax/Engine.php';

$filename = 'sample.txt';
$file = file($filename);

//$header = $file[0];
//var_dump($file);

$engine = new Parser_Winamax_Engine(new Hand(), $file);
$hand = $engine->parse();
//var_dump($hand);
print_r($hand);
