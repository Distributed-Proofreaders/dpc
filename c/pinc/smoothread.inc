<?php

/***************************************************************************************
*
* Functions for smoothreading commitment functionality
*
* At this point the functionality does not require the usage of a class
* nor make it more convenient. Future extensions might change this assessment.
*
****************************************************************************************/

/***************************************************************************************
*
* Data access functions
*
****************************************************************************************/

/***************************************************************************************
*
* function sr_users_is_commited: checks the association table for existing commitment
* inputs: projectid, username
* output: true/false regarding existence of commitment
* Remarks:
* Currently only a check for existence of record and field being <> 0 is done. The latter
* would allow the extension for a timestamp and the resetting to 0 if revoked in order
* to still show users that have once committed but then revoked that commitment.
*
****************************************************************************************/

/*
function sr_user_is_committed($projectid, $username) {
    global $dpdb;
    return $dpdb->SqlExists("
        SELECT 1 FROM smoothread
        WHERE projectid = '$projectid' 
            AND username = '$username'");
}
*/


/***************************************************************************************
*
* function sr_commit: inserts record into the association table for commitment
* inputs: projectid, username
* output: none
* Remarks:
* Currently the record is only inserted with the committed field being set to one.
* This allows the future extension for a timestamp and the resetting to 0 if revoked in oder
* to still show users that have once committed but then revoked that commitment.
*
****************************************************************************************/

/*
function sr_commit($projectid, $username) {
    global $dpdb;
    $dpdb->SqlExecute("
        REPLACE INTO smoothread
        SET projectid='$projectid',  
            username = '$username'");

}
*/

/***************************************************************************************
*
* function sr_withdraw_commitment: deletes record from the association table for commitment
* inputs: projectid, username
* output: none
*
* Remarks:
* Currently the record is of commitment is deleted.
*
****************************************************************************************/

/*
function sr_withdraw_commitment($projectid, $username) {
    global $dpdb;
    $dpdb->SqlExecute("
        DELETE FROM smoothread
        WHERE projectid = '$projectid' 
            AND username = '$username'");

}
*/

/***************************************************************************************
*
* function sr_get_committed_users: provide list of users with sr-commitment to project
* inputs: projectid
* output: Array of usernames
*
* Remarks:
*
****************************************************************************************/

/*
function sr_get_committed_users($projectid) {
    global $dpdb;
    return $dpdb->SqlValues("
        SELECT username FROM smoothread
        WHERE projectid = '$projectid'");
}
*/

/***************************************************************************************
*
* function sr_number_users_committed: provide nnumber of users with sr-commitment to project
* inputs: projectid
* output: number of users
*
* Remarks: 
*
****************************************************************************************/

/*
function sr_number_users_committed($projectid) {
    global $dpdb;
    return $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM smoothread
        WHERE projectid = '$projectid'");
}
*/

/***************************************************************************************
*
* User interface functions
*
****************************************************************************************/

/***************************************************************************************
*
* function sr_echo_commitment_form: create button and call page for database access
* inputs: projectid
* output: none
*
* Remarks:
* This calls a transient page executing the database function for inserting commitment 
* and provides the current URI for return to current page.
*
****************************************************************************************/

/*

function sr_echo_commitment_form($projectid) {
    global $code_url;

    $next_url = $_SERVER['REQUEST_URI'];
    $button_text = _("Commit to SR");

    echo "
    <form method='post' action='$code_url/tools/change_sr_commitment.php'>
        <input type='hidden' name='projectid' value='$projectid'>
        <input type='hidden' name='action' value='commit'>
        <input type='hidden' name='next_url' value='$next_url'>
        <input type='submit' value='$button_text'>
    </form>\n";
}
*/

/***************************************************************************************
*
* function sr_echo_withdrawal_form: create button and call page for database access
* inputs: projectid
* output: none
*
* Remarks:
* This calls a transient page executing the database function for revoking a commitment 
* and provides the current URI for return to current page.
*
****************************************************************************************/

/*
function sr_echo_withdrawal_form($projectid) {
    global $code_url;

    $next_url = $_SERVER['REQUEST_URI'];
    $button_text = _("Withdraw SR commitment");

    echo "
    <form method='post' action='$code_url/tools/change_sr_commitment.php'>
        <input type='hidden' name='projectid' value='$projectid'>
        <input type='hidden' name='action' value='withdraw'>
        <input type='hidden' name='next_url' value='$next_url'>
        <input type='submit' value='$button_text'>
    </form>\n";

}
*/


?>
