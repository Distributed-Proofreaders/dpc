<?php
$relPath="./../../pinc/";
require_once $relPath."dpinit.php";

/** @var $User DpThisUser */
$User->IsLoggedIn()
	or redirect_to_home();

$projectid          = ArgProjectId();
$projectid != ""
    or die("No project id");

$project            = new DpProject($projectid);
$project->Exists() && $project->UserMayManage()
    or die("Not authorized.");

$pagenum        = Arg("pagenum", "1");
$rowsperpage    = Arg("rowsperpage", "100");
$cmdPgUp        = IsArg("cmdPgUp");
$cmdPgDn        = IsArg("cmdPgDn");


$sql = "
    SELECT
        p.nameofwork AS title,
        p.authorsname AS author,
        FROM_UNIXTIME(pe.event_time) AS etime,
        pe.event_type	 AS etype,
        pe.projectid,
        pe.pagename,
        pe.version,
        pe.phase,
        pe.username
    FROM page_events pe
    JOIN projects p
        ON pe.projectid = p.projectid
    WHERE pe.projectid = '$projectid'
    ORDER BY pe.pagename, pe.version, pe.event_time";

echo "\n<!-- \n$sql\n -->\n";
/** @var $dpdb DpDb */
$rows = $dpdb->SqlRows($sql);

if(count($rows) == 0) {
    die("No events.");
}

if($cmdPgUp) {
	$pagenum = max($pagenum - 1, 1);
}
if($cmdPgDn) {
	$pagenum = min($pagenum + 1, ceil(count($rows) / $rowsperpage));
}
$title = $rows[0]['title'];
$author = $rows[0]['author'];

$tbl = new DpTable();
$tbl->SetClass("dptable tbl-page-log");

$tbl->AddColumn("^"._("Page"),  "pagename");
$tbl->AddColumn("^"._("Phase"),  "phase");
$tbl->AddColumn("^"._("Version"),  "version");
$tbl->AddColumn("^"._("Time"),  "etime");
$tbl->AddColumn("<"._("Event"), "etype");
$tbl->AddColumn("<"._("User"),  "username");

$no_stats = 1;

theme(_("Page log"), "header");

echo "<div class='center'>
        <h3>"._("Page log for")."</h3>
        <h2>$title</h2>
        <h3>$author</h3>
      </div>\n";
echo "<form name='frmlog' id='frmlog' method='POST'>
	<input type='hidden' name='rowsperpage' value='$rowsperpage'>
    <input type='hidden' name='pagenum' value='$pagenum'>
	<input type='hidden' name='projectid' value='$projectid'>\n";

$tbl->SetRowCount(count($rows));
$tbl->SetPaging($pagenum, $rowsperpage);
$tbl->SetRows($rows);

$tbl->EchoTable();
echo "
</form>\n";

theme("", "footer");

?>
