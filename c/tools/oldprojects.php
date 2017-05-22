<?php
/*
    process flow when responding to an uploaded project zip file
    - location of uploaded zip file: $uploadpath
    - original name of (presumably a zip) file - $filename 
    - directory where unzipped files end up - $project->UploadPath()

*/

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = "../pinc/";
require_once $relPath."dpinit.php";

global $User;

$User->IsLoggedIn()
	or die("Log in please.");

$username       = $User->Username();

$submits = ArgArray("submit");
$loads = array_keys($submits);

if(count($loads) > 0) {
	$pid = $loads[0];
	$ok = ! $dpdb->SqlExists("
				SELECT 1 FROM projects
				WHERE projectid = '$pid'");
	if($ok) {
		$sql = "
                INSERT INTO projects
                (
                    projectid , nameofwork , authorsname , language , seclanguage , username , comments
                    , modifieddate , postednum , clearance , genre , difficulty , postproofer , ppverifier
                    , postcomments , image_source , image_preparer , text_preparer , scannercredit
                    , extra_credits , smoothread_deadline , phase , createdby , createtime
                )
                SELECT
                  projectid , nameofwork , authorsname , language , seclanguage , username , comments
                  , modifieddate , postednum , clearance , genre , difficulty , postproofer , ppverifier
                  , postcomments , image_source , image_preparer , text_preparer , scannercredit
                  , extra_credits , smoothread_deadline , 'PP' , username , UNIX_TIMESTAMP()
                FROM oldprojects
                WHERE projectid = ?";
		$args = array(&$pid);
		$dpdb->SqlExecutePS($sql, $args);
	}

	$from  = glob("/var/sftp/dpscans/*/$pid/*");
	$todir = ProjectPath($pid);
    if(! file_exists($todir)) {
        @mkdir($todir);
        @chmod($todir, 0777);
    }
	foreach($from as $frompath) {
        $topath = build_path($todir, basename($frompath));
		copy($frompath, $topath);
	}

}


$tbl = new DpTable("tblproj", "dptable w100 lfloat");

$tbl->AddColumn("proj id", "projectid", null, "w15");
$tbl->AddColumn("<Title", "title");
$tbl->AddColumn("<Author", "author", null, "w15");
$tbl->AddColumn("<PM", "pm", null, "w10");
//$tbl->AddColumn("<Modified", "modified", null, "w05");
$tbl->AddColumn("<Posted #", "postednum", null, "w05");
$tbl->AddColumn("<PPer", "pper", null, "w05");
$tbl->AddColumn("<PPVer", "ppver", null, "w05");
$tbl->AddColumn("^Load", "projectid", "eload");
$sql = "
	SELECT
		projectid,
		REPLACE(nameofwork, '\"', '') title,
		authorsname author,
		username pm,
		from_unixtime(modifieddate) modified,
		phase,
		postednum,
		clearance,
		postproofer pper,
		ppverifier ppver,
		postcomments,
		image_preparer imager,
		text_preparer texter,
		extra_credits extras
	FROM oldprojects
	ORDER BY projectid";
$rows = $dpdb->SqlRows($sql);

$tbl->SetRows($rows);

$no_stats = 1;
theme("old projects", "header");
echo "<form name='frmload' method='POST'>\n";
$tbl->EchoTable();
echo "</form>\n";

theme("", "footer");
exit;

function eload($projectid) {
	return "<input type='submit' name='submit[{$projectid}]' value='load'>";
}
