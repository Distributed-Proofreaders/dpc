<?PHP

// ubermaster.php

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath . 'dpinit.php');

$sql = "
		SELECT
			p.projectid,
			p.nameofwork,
			p.authorsname,
			p.phase,
			p.username AS pm,
			p.postproofer,
			p.ppverifier,
			pe.event_type,
			DATE(FROM_UNIXTIME(pe.event_time)) created
		FROM
			projects p
			LEFT JOIN project_events pe
				ON p.projectid = pe.projectid
				   AND pe.event_type = 'create'
		WHERE p.phase IN ('PREP', 'P1', 'P2', 'P3', 'F1', 'F2', 'PP', 'PPV')
		GROUP BY p.projectid
		ORDER BY
			MAX(pe.event_time) DESC";

$rows = $dpdb->SqlRows($sql);
$nproj = count($rows);

$tbl = new DPTable("ubertable", "dptable sortable right em80");
$tbl->AddColumn("<Title", "nameofwork", "etitle");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("^Phase", "phase");
$tbl->AddColumn("^PM", "pm", "euser");
$tbl->AddColumn("<PPer", "postproofer", "euser");
//$tbl->AddColumn("<Verifier", "ppverifier", "euser");
$tbl->AddColumn("<Created", "created");

$tbl->SetRows($rows);
// -----------------------------------------------------------------------------

$no_stats = 1;

theme("PP: Post Processing", "header");

echo "<div id='divreport' class='w90'>\n";

echo "<div class='left w100'>Total number of projects reported: $nproj</div>\n";

$tbl->EchoTable();

echo "</div></div>\n";
// -----------------------------------------------------------------------------

theme("", "footer");
exit;

function etitle($title, $row) {
	//    $title = maybe_convert($title);
	$projectid = $row['projectid'];
	return link_to_project($projectid, $title);
}

function euser($username) {
	return link_to_pm($username);
}

// vim: sw=4 ts=4 expandtab

