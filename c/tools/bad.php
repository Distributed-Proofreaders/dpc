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
        SELECT projectid,
               username pm,
               nameofwork title,
               authorsname author,
               n_bad_pages nbad
        FROM projects
        WHERE n_bad_pages > 0");

$tbl = new DpTable("tblbad", "dptable");
$tbl->AddColumn("<Title", "title", "etitle");
$tbl->AddColumn("<Author", "author");
$tbl->AddColumn("<PM", "pm", "epm");
$tbl->AddColumn("^Bad Pages", "nbad");
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

