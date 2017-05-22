<?
$relPath="./../../pinc/";
include_once $relPath.'dpinit.php';
require_once $relPath.'DpQualProject.class.php';

// (User clicked on "Start Proofreading" link or
// one of the links in "Done" or "Save Draft" trays.)

$projectid      = Arg("projectid")
or die( "No project requested in proof.php." );

$pagename       = Arg("pagename");

$User->IsLoggedIn()
	or RedirectToLogin();


$project = new DpQualProject($projectid);

if(! $project->UserMayProof()) {
	die( "Not authorized for this qual project." );
}

// user can edit any page until submission. But show which have been saved and not.
display_pages($project);


exit;

/** @param $project DpQualProject */
function display_pages($project) {
	$pgs = $project->PageRows();
	$tbl  = new DpTable();
	$tbl->SetRows($pgs);
	$tbl->EchoTable();
}
