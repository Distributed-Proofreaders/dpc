<?php
// $Id: user_is.inc,v 1.44 2006/03/29 05:19:05 jmdyck Exp $

// This file provides values (mostly permissions) for the logged-on user.

global $relPath;
//include_once($relPath.'page_tally.inc');


// -----------------------------------------------------------------------------

function that_user_is_a_sitemanager($username) {
    $usr = new DpUser($username);
    return $usr->IsSiteManager();
}

function user_is_a_sitemanager() {
    global $pguser;
    return that_user_is_a_sitemanager($pguser);
}

// ---------

function that_user_is_proj_facilitator($username) {
    $usr = new DpUser($username);
    return $usr->IsProjectFacilitator();
}

function user_is_proj_facilitator() {
    global $User;
    return $User->IsProjectFacilitator();
}

// ---------

function user_can_grant() {
    global $User;
    return $User->MayGrantAccess();
    // return     user_is_a_sitemanager()
            // || user_is_an_access_request_reviewer()
            // || user_is_proj_facilitator();
}

function user_can_revoke() {
    global $User;
    return $User->MayRevokeAccess();
    // return     user_is_a_sitemanager() ;
}

function user_can_modify_access() {
    global $User;
    return $User->MayModifyAccess();
//    return array( user_can_grant(), user_can_revoke() );
}

function user_is_PM() {
    global $User;
    return $User->IsProjectManager();
}

function user_is_site_news_editor() {
    global $User;
    return $User->HasRole("Editor");
}

function user_is_site_translator() {
    global $User;
    return $User->HasRole("Translator");
}

function user_can_work_on_beginner_pages_in_round($round) {
    global $User;
    $round_number = $round->round_number;
    return $User->BooleanSetting("see_BEGIN_R{$round_number}")
        || $User->IsSiteManager()
        || $User->IsProjectFacilitator()
        || $User->MayWorkInRound("{$round->id}Mentor");
}

function user_is_image_sources_manager() {
    global $User;
    return $User->HasRole("ImageSourcesManager");
}


// vim: sw=4 ts=4 expandtab
