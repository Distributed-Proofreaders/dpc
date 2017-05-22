<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');
require_once $relPath . "DpTable.class.php";

$rows = $dpdb->SqlRows("
    SELECT genre, 
        SUM(n_available_pages) available,
        SUM(n_pages) - sum(n_available_pages) unavailable
    FROM projects
    WHERE phase LIKE 'P1%'
    GROUP BY genre");

if(count($rows) == 0) {
	echo "<h3>No projects available.</h3>";
}

$tbl = new DpTable();
$tbl->SetRows($rows);

echo html_head("P1 Genres");
echo "<h1 class='center'>P1 Genres</h1>\n";
$tbl->EchoTable();
echo html_end();

