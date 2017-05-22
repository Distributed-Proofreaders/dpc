<?PHP
// DP includes
$relPath="./../../pinc/";
include_once $relPath.'dpinit.php';

global $Context;

// Which team?
$team_id = Arg("team");
$team = new DpTeam($team_id);

// Get info about team

// TODO: this needs to move into DpTeam
//Determine if there is an existing topic. If not create one
if(! $team->TopicId()) {
    $tname          = $team->TeamName();
    $towner_id      = $team->OwnerId();

    $message = "
    Team Name: {$team->TeamName()};
    Created By: {$team->OwnerName()};
    Info: {$eam->Info()}
    Team Page: [url]" . url_for_team_stats($team->Id()) . "[/url]

    Use this area to have a discussion with your fellow teammates! :-D

    ";


    // appropriate forum to create thread in
//    $forum_id = $teams_forum_idx;

//    $post_subject = $tname;


    $topic_id = $Context->CreateTeamThread($post_subject, $message, $towner_name);
    $dpdb->SqlExecute("UPDATE teams SET topic_id = $topic_id WHERE id = $team_id");


    /*
    $topic_id = create_topic(
            $forum_id,
            $post_subject,
            $message,
            $towner_name,
            TRUE,
            FALSE );

            //Update user_teams with topic_id so it won't be created again
            $dpdb->SqlExecute("UPDATE user_teams SET topic_id = $topic_id WHERE id = $team_id");
   */
}

// By here, either we had a topic or we've just created one, so redirect to it

$redirect_url = "$forums_url/viewtopic.php?t=$topic_id";
header("Location: $redirect_url");
