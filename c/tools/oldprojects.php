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

$tbl = new DpTable("tblproj", "dptable w100 lfloat");

$tbl->AddColumn("proj id", "projectid", "w25");
$tbl->AddColumn("<Title", "title", "w20");
$tbl->AddColumn("<Author", "author", "w10");
$tbl->AddColumn("<PM", "pm", "w05");
$tbl->AddColumn("<modified", "modified", "w05");
$tbl->AddColumn("<state", "state");
$tbl->AddColumn("<Posted #", "postednum", "w05");
$tbl->AddColumn("<Clearance", "clearance", "w05");
$tbl->AddColumn("<PPer", "pper", "w05");
$tbl->AddColumn("<PPVer", "ppver", "w05");
$tbl->AddColumn("<imager", "imager", "w05");
$tbl->AddColumn("<texter", "texter", "w05");
$tbl->AddColumn("<extra", "extra", "w05");
$sql = "
	SELECT
		projectid,
		REPLACE(nameofwork, '\"', '') title,
		authorsname author,
		username pm,
		from_unixtime(modifieddate) modified,
		state,
		postednum,
		clearance,
		postproofer pper,
		ppverifier ppver,
		postcomments,
		image_preparer imager,
		text_preparer texter,
		extra_credits extras
	FROM oldprojects ORDER BY nameofwork";
$rows = $dpdb->SqlRows($sql);

$tbl->SetRows($rows);

$no_stats = 1;
theme("old projects", "header");
$tbl->EchoTable();

theme("", "footer");
