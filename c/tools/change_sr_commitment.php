<?PHP
$relPath="../pinc/";
include_once($relPath.'dpinit.php');
//include_once($relPath.'smoothread.inc');

/***************************************************************************************
*
* transient page to register or revoke a users commitment to smoothread a project.
* inputs: projectid, username, action, next_url (per POST-method)
* output: none
* Remarks:
* Transient page executing the action "commit" or "withdraw".
* It will automatically call the page adressed by next_url.
*
****************************************************************************************/
/*

$projectid      = Arg("projectid");
$action         = Arg("action");
$refresh_url    = Arg("next_url");

switch ($action) {
    case "commit":
        sr_commit($projectid, $User->Username());
        break;

    case "withdraw":
        sr_withdraw_commitment($projectid, $User->Username());
        break;
}

divert($refresh_url);
*/
?>
