<?PHP

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

$project = new DpProject($projectid);
$topic_id = ensure_topic($project);

$redirect_url = "$forums_url/viewtopic.php?t=$topic_id";
header("Location: $redirect_url");

function ensure_topic($project) {
    global $dpdb;
    global $code_url;
    /** @var DpProject $project */

    if ( $project->ForumTopicId()) {
        return $project->ForumTopicId();
    }

    $post_subject = "\"{$project->Title()}\"    by {$project->Author()}";
    $link = link_to_project($project->ProjectId(), "Project Comments");

    $post_body = "
This thread is for discussion of \"{$project->Title()}\" by {$project->Author()}.

Please review the $link comments[/url] before posting, as well as any posts below, as your question may already be answered there.

(This post is automatically generated.)\n";


    $bb = new DpPhpbb3();
    $topic_id = $bb->CreateThread($post_subject, $post_body);
        
    $project->SetTopicId();
    return $topic_id;
}

