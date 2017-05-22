<?

error_reporting(E_ALL);
// DP includes
$relPath = "./../../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'Project.inc');

// Which project?
$projectid = Arg("project");
if($projectid == "") {
    $projectid = Arg("projectid");
}

$project = new Project($projectid);
$topic_id = $project->ensure_topic();

$redirect_url = "$forums_url/viewtopic.php?t=$topic_id";
header("Location: $redirect_url");
?>
