<?PHP

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');


$sql = "
	SELECT p.projectid,
		p.phase,
		p.nameofwork title,
		p.authorsname author,
		p.username pm,
		ph.hold_code,
		ph.set_by,
		DATE(FROM_UNIXTIME(ph.set_time)) setdate
	FROM projects p
	JOIN project_holds ph
		ON p.projectid = ph.projectid
		AND p.phase = ph.phase
	JOIN phases
		ON p.phase = phases.phase
	WHERE p.phase IN ('P1', 'P2', 'P3', 'F1', 'F2')
		AND hold_code != 'queue'
	ORDER BY phases.sequence, setdate";

echo "<!--
$sql
-->\n";

$rows = $dpdb->SqlRows($sql);

$tbl = new DpTable("tblholds", "dptable w75");
$tbl->AddColumn("<Phase", "phase");
$tbl->AddColumn("<Title", "title", "etitle");
$tbl->AddColumn("<Author", "author");
$tbl->AddColumn("<PM", "pm", "epm");
$tbl->AddColumn("^Hold", "hold_code");
$tbl->AddColumn("^Set By", "set_by");
$tbl->AddColumn("^Date", "setdate");
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

$title = "DPC: Projects On Hold";
$browsertab = $title;

$no_stats = 1;
theme($browsertab, "header", $args);


echo "<p class='ph1'>$title</p>\n";

$tbl->EchoTable();

theme("", "footer");
exit;

