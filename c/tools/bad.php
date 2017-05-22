<?PHP

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');

// -----------------------------------------------------------------------------
// collect GETs, POSTs, etc.
// -----------------------------------------------------------------------------




// -----------------------------------------------------------------------------
// Execution logic
// -----------------------------------------------------------------------------


// -----------------------------------------------------------------------------
// Data acquisition
// -----------------------------------------------------------------------------

$rows = $dpdb->SqlRows("
        SELECT pv.projectid,
			   pv.pagename,
               p.username pm,
               p.nameofwork title,
               p.authorsname author
        FROM page_last_versions pv
        JOIN projects p
        ON pv.projectid = p.projectid
        WHERE pv.state = 'B'
        ORDER BY p.nameofwork, pv.pagename");

$tbl = new DpTable("tblbad", "dptable");
$tbl->AddColumn("<Title", "title", "etitle");
$tbl->AddColumn("<Author", "author");
$tbl->AddColumn("<PM", "pm", "epm");
$tbl->AddColumn("<Page", "pagename");
$tbl->SetRows($rows);

function etitle($title, $row) {
    return link_to_project($row["projectid"], $title);
}

function epm($pm) {
    return link_to_pm($pm);
}

// -----------------------------------------------------------------------------
// Display preparation
// -----------------------------------------------------------------------------


// onload required for table sorts
$args = array();

$browsertab = "DPC: Bad Pages";
$title = "DPC: Bad Pages";

$no_stats = 1;
theme($browsertab, "header", $args);


echo "<p class='ph1'>$title</p>\n";

$tbl->EchoTable();

theme("", "footer");
exit;

