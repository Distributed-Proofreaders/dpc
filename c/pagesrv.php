<?php
$relPath = "./pinc/";
require_once "pinc/dpinit.php";

$action    = Arg("action");
$projectid = ArgProjectid();
$pagename  = ArgPageName();

$pg = new DpPage($projectid, $pagename);


header("Content-Type: text");
header("Content-Length: " .mb_strlen($text));

echo $text;
?>
