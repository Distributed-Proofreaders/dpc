<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
require $relPath . "dpinit.php";

$sql = "SELECT projectid, nameofwork, post_comments FROM projects
		WHERE phase IN ('PREP', 'P1', 'P2', 'P3', 'F1', 'F2', 'PP', 'PPV')
		AND ISNULL(post_comments, '') != ''";

$rows = $dpdb->SqlRows($sql);

$tbl = new DpTable();
$tbl->SetRows($rows);
$tbl->EchoTable();
exit;

