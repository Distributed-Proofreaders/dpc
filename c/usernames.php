<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
include_once $relPath . 'dpinit.php';

echo html_head("DPC Query");
echo "<h1 class='center'>DPC Query</h1>\n";

$sql = "SELECT username, real_name from users
        ORDER BY username";

$rows = $dpdb->SqlRows($sql);

$tbl = new DpTable("left dptable");
$tbl->AddColumn("<Username", "username");
$tbl->AddColumn("<Real name", "real_name");
$tbl->SetRows($rows);

$tbl->EchoTable();
echo html_end();
?>
