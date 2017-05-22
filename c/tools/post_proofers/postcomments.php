<?
$relPath="../../pinc/";
include_once($relPath.'dpinit.php');

$projectid = Arg("projectid");
$projectid != ""
    or die("No projectid.");

$project = new DpProject($projectid);
($project->UserIsPPer() 
    && $project->State() == PROJ_POST_FIRST_CHECKED_OUT)
  or die( _("The project is not checked out to you."));

$sql = "
	UPDATE projects 
	SET postcomments = ?
    	WHERE projectid = '$projectid'";

$args = array(&$postcomments);
$dpdb->SqlExecutePS($sql, $args);
divert(url_for_project($projectid));

?>
