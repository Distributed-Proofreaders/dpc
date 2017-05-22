<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
include_once $relPath . 'dpinit.php';

$txt = file_get_contents("https://archive.org/stream/historyofstandar00tarbuoft#page/n3/mode/1up");
dump($txt);
