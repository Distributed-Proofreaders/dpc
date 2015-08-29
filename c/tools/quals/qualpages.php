<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 3/12/2015
 * Time: 7:15 PM
 */
$relPath="./../pinc/";
include_once $relPath . 'dpinit.php';
require_once $relPath . 'DpQualProject.class.php';

// (User clicked on "Start Proofreading" link or
// one of the links in "Done" or "Save Draft" trays.)

$projectid      = Arg("projectid")
	or die( "No project requested in proof.php." );

$sql = "SELECT pagename,
 			   DATE(FROM_UNIXTIME(savedate)) savedate
		FROM qual_page_versions
		WHERE qual_projectid = '$projectid'
		ORDER BY pagename";

$rows = $dpdb->SqlRows($sql);

echo "<form name='frmpages' id='frmpages' action='' method='POST'>
		<input type='hidden' name='pgname' id='pgname' value=''>\n";
$tbl = new DpTable();
$tbl->AddColumn("^", "pagename");
$tbl->AddColumn("<Saved", "savedate");
$tbl->AddColumn("^Image", "pagename", "eimage");
$tbl->AddColumn("^Text", "pagename", "etext");
$tbl->SetRows($rows);
echo "</form>\n";

echo html_head("Qual Pages for $projectid");
echo "<script type='text/javascript'>
	function esubmit(pg) {
		document.getElementById('pgname').value = pg;
		document.frmpages.submit();
	}
</script>\n";

$tbl->EchoTable();
exit;

//function epage($pagename) {
//	return "<span class='likealink' id='page_{$pagename}' onclick='esubmit()'>$pagename</span>\n";
//}

function eimage($pagename) {
	return "<span class='likealink' id='img[$pagename]' onclick='esubmit()'>Image</span>\n";
}
function etext($pagename) {
		return "<span class='likealink' id='txt[$pagename]' onclick='esubmit()'>Text</span>\n";
}
