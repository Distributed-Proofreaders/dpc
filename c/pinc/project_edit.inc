<?php
global $relPath;
include_once($relPath.'user_is.inc');
include_once($relPath.'project_states.inc');


define('PROJECT_DOES_NOT_EXIST',  1);
define('USER_CANNOT_EDIT_PROJECT',2);
define('USER_CAN_EDIT_PROJECT',   3);

// Ascertain whether the current user ($pguser)
// is allowed to edit the specified project
// (i.e., is either the project's manager or a site manager).
function user_can_edit_project( $projectid ) {
    global $pguser;
    global $dpdb;

    $project_manager = $dpdb->SqlOneValue("
        SELECT username FROM projects WHERE projectid = '$projectid'");
    if ($pguser == $project_manager) {
        // The current user is the project manager for the project.
        return USER_CAN_EDIT_PROJECT;
    }

    if ( user_is_a_sitemanager() || user_is_proj_facilitator() ) {
        return USER_CAN_EDIT_PROJECT;
    }

    return USER_CANNOT_EDIT_PROJECT;
}

// Politely abort if the current user ($pguser)
// is not allowed to edit the specified project
function abort_if_cant_edit_project( $projectid ) {
    global $site_manager_email_addr;

    $result = user_can_edit_project($projectid);

    if ( $result == PROJECT_DOES_NOT_EXIST ) {
        echo "
            <P>
            "._("There appears to be no such project")." ($projectid).
            <P>
            "._("If this message is an error, contact the")." <a href=\"mailto:$site_manager_email_addr\">"._("site manager")."</a>.
            <P><a href=\"projectmgr.php\">"._("Back")."</a>";
        theme( "", "footer" );
        exit;
    }
    else if ( $result == USER_CANNOT_EDIT_PROJECT ) {
        echo "
            <P>
            "._("You are not allowed to change this project")." ($projectid).
            <P>
            "._("If this message is an error, contact the")." <a href=\"mailto:$site_manager_email_addr\">"._("site manager")."</a>.
            <P><a href=\"projectmgr.php\">"._("Back")."</a>";
        theme( "", "footer" );
        exit;
    }
    else if ( $result == USER_CAN_EDIT_PROJECT ) {
        return;
    }
    else {
        echo "unexpected return value from user_can_edit_project: '$result'";
        exit;
    }
}

// Can the current user delete a project in the given state?
// (assuming that the current user can edit/manage the project)
function user_can_delete_project_in_state( $project_state ) {
    // PM/PF/SA can delete a project when it's new.
    // SA can 'delete' a project when it's in any state but already-deleted.
    // (There's a difference to what 'deletion' means in the latter case.)
    return (
        $project_state == PROJ_NEW
        || $project_state != PROJ_DELETE && user_is_a_sitemanager());
}

// vim: sw=4 ts=4 expandtab
?>
