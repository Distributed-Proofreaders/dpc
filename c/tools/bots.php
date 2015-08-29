<?PHP

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath . 'dpinit.php');

echo "<!DOCTYPE php>
<html>
<head>
<title>
DPC Bots
</title>
</head>
<body>\n";

$bots = $dpdb->SqlRows("
			SELECT username FROM forum.bb_users
			WHERE user_type = 2
			ORDER BY username");

$tbl = new DpTable();
$tbl->SetRows($bots);
$tbl->EchoTableNumbered();

