<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
require $relPath . "dpinit.php";


$pids = $dpdb->SqlValues(
	"SELECT projectid FROM projects
	 WHERE phase = 'P2'");

$title = "Regex Testing";

echo "<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<title>$title</title>
</head>

<body onload='init()'>
<div class='container left'>\n";

foreach($pids as $pid) {
	$proj = new DpProject( $pid );
	$txt  = $proj->RoundText( "OCR" );
	$n1    = RegexCount( '\s"\s', "uis", $txt );
	$txt  = $proj->RoundText( "P1" );
	$n2    = RegexCount( '\s"\s', "uis", $txt );
	say( "$pid  $n1 $n2" );
	ob_flush();
}

echo "</div>
</body></html>";






