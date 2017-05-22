<?PHP
$relPath="./../../pinc/";
include_once $relPath.'dpinit.php';

$User->IsLoggedIn()
	or RedirectToLogin();

$projectid      = ArgProjectid()
    or die( "No project requested in proof.php." );

$pagename       = ArgPageName();

$project = new DpProject($projectid);
$roundid = $project->RoundId();

if(! $User->MayWorkInRound($roundid))
    die("Not authorized for Round $roundid.");


divert("../../pennask/proofpage.php?projectid={$projectid}&pagename={$pagename}");

