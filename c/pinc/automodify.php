<?PHP

/* 

    1. If not completed but no available pages, try to release pages.
    2. If all the pages are complete, set state to completed for same round.
    3. If state is complete, advance to next round,
       either uavailable if hold between rounds,
       or waiting if not.
    4. If waiting, process release rules.

*/

$relPath = "./";
require "dpinit.php";
var_dump("automodify");

require "Stage.inc";
require "RoundDescriptor.inc";
require "Pool.inc";
require "stages.inc";
// dump(Round_for_round_id_);
dump(MAX_NUM_PAGE_EDITING_ROUNDS);

// require 'stages.inc';
// require 'projectinfo.inc';
// require 'project_trans.inc';
// require 'DPage.inc';
require 'project_states.inc';
require 'Project.inc'; // project_get_auto_PPer
// require 'ProjectTransition.inc'; // project_get_auto_PPer

include('autorelease.inc');

define("PF_AUTO", "[AUTO]");

$refresh_url = Arg('return_uri', 'projectmgr.php');

// -----------------------------------------------------------------------------


global $dpdb;

// 0. Build array of available projects

{
    $ids = $dpdb->SqlValues("
            SELECT projectid FROM projects
            WHERE state LIKE '%_proj_avail'");

    $avail_projects = array();
    foreach ($ids as $projectid) {
        $projects[] = new Project($projectid);
    }
    dump("projects available: ".count($ids));
}

// --------------------------------------------------
//
// 1. If available but not all pages are complete, try to release pages.

{
    foreach ($avail_projects as $project) {
        /** @var $project Project */
        if ($project->PageCount() == $project->CompletedPageCount()) {
            continue;
        }
        dump("check ".$project->projectid() . " for releasable pages");
        $round              = $project->Round();
        $reclaimable_images = $dpdb->SqlValues("
        SELECT image FROM {$this->projectid()}
            WHERE (state IN ('$round->page_out_state', '$round->page_temp_state') 
                AND $round->time_column_name <= (CURRENT_TIMESTAMP() - 4 * 60 * 60");

        dump("pages found. ".count($reclaimable_pages));
        die();
        foreach ($reclaimable_images as $image) {
            Page_reclaim($this->projectid(), $image, $round, '[automodify.php]');
        }
    }
}

//  2. If all the pages are complete, set state to completed for same round.

{
    foreach ($avail_projects as $project) {
        if ($project->CompletedPageCount() != $project->PageCount()) {
            continue;
        }
        $round     = $project->Round();
        $state     = $round->project_complete_state;
        $error_msg = project_transition($this->projectid(), $state, PF_AUTO);
        if ($error_msg) {
            echo "$error_msg\n";
            continue;
        }
    }
}

//  3. If state is complete, advance to next round,
//     a. first post-round state if final round,
//     b. next round uavailable if hold between rounds,
//     c. next round waiting otherwose.

{
    $ids = $dpdb->SqlValues("
        SELECT projectid FROM projects
        WHERE state LIKE '%_proj_complete'");

    $compl_projects = array();
    foreach ($ids as $projectid) {
        $compl_projects[] = new Project($projectid);
    }

    foreach ($compl_projects as $project) {
        $round      = $project->Round();
        $next_round = get_Round_for_round_number(1 + $round->round_number);

        if ($round->round_number == MAX_NUM_PAGE_EDITING_ROUNDS) {
            if ($this->username() == "") {
                $new_state = "proj_post_first_available";
            }
            else {
                $new_state = "proj_post_first_checked_out";
            }
        }
        // else if( $project->hold_project_between_rounds()) {
        // $new_state = $next_round->project_unavailable_state
        // }
        // else {
        $new_state = $next_round->project_waiting_state;
        // }
        $error_msg = project_transition($this->projectid(), $new_state, PT_AUTO);
        if ($error_msg) {
            echo "$error_msg\n";
            continue;
        }
        /*
        if ( $project->hold_between_rounds ) {
            maybe_mail_project_manager( $project,
                "This project is being held between rounds $round->round_number
                    and $next_round->round_number.",
                "DP Project Held Between Rounds");
        }
        */
    }
}

autorelease();

echo "</pre>\n";


// vim: sw=4 ts=4 expandtab
