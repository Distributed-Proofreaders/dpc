<?PHP
// This script is actually 4 scripts in one file:
//   - Cleanup Files: Removes duplicates and checks in missing pages after 3 hours
//   - Promote Level: If a project is ready to be promoted, it sends it to round 2
//   - Complete Project: If a project has completed round 2, it sends it to post-processing or assign to the project manager
//   - Release Projects: If there are not enough projects available to end users, it will release projects waiting to be released
$relPath = "/sharehome/htdocs/c/pinc/";

include_once($relPath.'dpinit.php');
include_once($relPath.'stages.inc');
include($relPath.'projectinfo.inc');
include($relPath.'project_trans.inc');
include_once($relPath.'DPage.inc');
include_once($relPath.'DpProject.class.php');
include_once($relPath.'project_states.inc');
include_once($relPath.'Project.inc'); // project_get_auto_PPer

// declare a bunch of functions
include('autorelease.inc');

$trace = FALSE;

// -----------------------------------------------------------------------------

$have_echoed_blurb_for_this_project = 0;

// -----------------------------------------------------------------------------

echo "<pre>\n";

$one_project = isset($_GET['project'])
    ? $_GET['project']
    : 0;

// if only single project, drop a log entry
if ($one_project) {
    $verbose = 0;
    $WHERE = "projectid = '$one_project'";

    // log tracetimes
    $tracetime = time();
    $dpdb->SqlExecute("
        INSERT INTO job_logs (filename, tracetime, event, comments)
        VALUES 
            ('automodify.php', UNIX_TIMESTAMP(), 'BEGIN', 
                'running for single proj $one_project')");
}
else {  // auto selection
    $verbose = 1;

    // log tracetimes
    $tracetime = time();
    $dpdb->SqlExecute("
        INSERT INTO job_logs 
            (filename, tracetime, event, comments)
        VALUES 
            ('automodify.php', UNIX_TIMESTAMP(), 'BEGIN', 
                'running for all eligible projects')");

    // create list of projects that are available, complete, or bad
    $WHERE = "0";
    for ( $rn = 1; $rn <= MAX_NUM_PAGE_EDITING_ROUNDS; $rn++ ) {
        $r = get_Round_for_round_number($rn);
        $WHERE .= "
            OR state = '{$r->project_available_state}'
            OR state = '{$r->project_complete_state}'
            OR state = '{$r->project_bad_state}'";
    }
}


$rows = $dpdb->SqlRows("
    SELECT projectid, state, username, nameofwork
    FROM projects
    WHERE {$WHERE}");

// for each movable project
foreach($rows as $project) {
    $have_echoed_blurb_for_this_project = 0;

    $projectid  = $project["projectid"];
    $state      = $project["state"];
    $username   = $project["username"];
    $nameofwork = $project["nameofwork"];

    if ($trace) {
        echo_project_info( $project );
    }

    // Decide which round the project is in
    $proj_round = get_Round_for_project_state($state);
    if ( ! $proj_round ) {
        echo "    automodify.php: unexpected state $state for project $projectid\n";
        continue;
    }

    //Bad Page Error Check

    if ( ($state == $proj_round->project_available_state) 
      || ($state == $proj_round->project_bad_state) ) {
        if ( pages_indicate_bad_project( $projectid, $proj_round ) ) {
            // This project's pages indicate that it's bad.
            // If it isn't marked as such, make it so.
            if ($trace) echo "project looks bad.\n";
            $appropriate_state = $proj_round->project_bad_state;
        }
        else {
            // Pages don't indicate that the project is bad.
            // (Although it could be bad for some other reason. Hmmm.)
            if ($trace) echo "project looks okay.\n";
            $appropriate_state = $proj_round->project_available_state;
        }

        if ($state != $appropriate_state) {
            if ($verbose) {
                echo_project_info( $project );
                echo "    Re badness, changing state to $appropriate_state\n";
            }
            if ($trace) 
                echo "changing its state to $appropriate_state\n";
            $error_msg = project_transition( $projectid, $appropriate_state, PT_AUTO );
            if ($error_msg) {
                echo "$error_msg\n";
                assert(false);
                StackDump();
            }
            $state = $appropriate_state;
        }
    }


    // if forcing a project, or the project is available but no pages are available
    $pgs_avail = $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM $projectid WHERE state LIKE '%.page_avail'");
    if ( ($one_project) 
            || ($state == $proj_round->project_available_state 
                && $pgs_avail == 0)) {

        // Reclaim MIA pages
        if ($verbose) {
            echo_project_info( $project );
            echo "    Reclaiming any MIA pages\n";
        }

        $n_hours_to_wait = 4;
        $max_reclaimable_time = time() - $n_hours_to_wait * 60 * 60;

        $images = $dpdb->SqlValues("
            SELECT image FROM $projectid
            WHERE state IN ('$proj_round->page_out_state','$proj_round->page_temp_state')
                AND $proj_round->time_column_name <= $max_reclaimable_time
            ORDER BY image ASC");

        $n_reclaimable_pages = count($images);
        if ($verbose) 
            echo "        reclaiming $n_reclaimable_pages pages\n";

        foreach($images as $image) {
            Page_reclaim( $projectid, $image, $proj_round, '[automodify.php]' );
        }


        // Decide whether the project is finished its current round.
        if ( $state == $proj_round->project_available_state ) {
            echo "Project is available";
            $num_done_pages = getNumPagesInState($projectid, $proj_round->page_save_state);
            $num_total_pages = getNumPages($projectid);

            if ($num_done_pages == $num_total_pages) {
                if ($verbose) 
                    echo "    All $num_total_pages pages are in '$proj_round->page_save_state'.\n";
                $state = $proj_round->project_complete_state;
            }
            else {
                if ($verbose) 
                    echo "    Only $num_done_pages of $num_total_pages pages 
                            are in '$proj_round->page_save_state'.\n";
                $state = $proj_round->project_available_state;
            }
        }

        // if ($verbose) {
            // echo_project_info( $project );
            // echo "    Advancing \"$nameofwork\" to $state\n";
        // }
        // $project = new DpProject($projectid);
        // $project->AdvanceRound();
        // $error_msg = project_transition( $projectid, $state, PT_AUTO );
        // if ($error_msg) {
            // echo "$error_msg\n";
            // assert(false);
            // StackDump();
            // continue;
        // }
    }


    // Promote Level
    if ($state == $proj_round->project_complete_state
        && $proj_round->round_number < MAX_NUM_PAGE_EDITING_ROUNDS) {
        $next_round = get_Round_for_round_number( 1 + $proj_round->round_number );

        $next_round_state = $next_round->project_waiting_state;

        if ($verbose) {
            echo_project_info( $project );
            echo "    Promoting \"$nameofwork\" to $next_round_state\n";
        }

        $error_msg = project_transition( $projectid, $next_round_state, PT_AUTO );
        if ($error_msg) {
            echo "$error_msg\n";
            assert(false);
            continue;
        }

        if ( $next_round_state == $next_round->project_unavailable_state ) {
            maybe_mail_project_manager(
                $project,
                "This project is being held between rounds $proj_round->round_number 
                    and $next_round->round_number.",
                "DP Project Held Between Rounds"); 
        }
    }

    // Completed Level
    if ($state == $proj_round->project_complete_state
        && $proj_round->round_number == MAX_NUM_PAGE_EDITING_ROUNDS) {
        // Prepare a project for post-processing.

        if ( is_null(project_get_auto_PPer($projectid)) ) {
            $new_state = PROJ_POST_FIRST_AVAILABLE;
        }
        else {
            $new_state = PROJ_POST_FIRST_CHECKED_OUT;
        }
        $error_msg = project_transition( $projectid, $new_state, PT_AUTO );
        if ( $error_msg ) {
            assert(false);
            StackDump();
            echo "$error_msg\n";
        }
    }
}

if ($trace) 
    echo "\n";

if ($verbose) {
    echo "\n";
    echo "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX\n";
    echo "\n";
}

echo "</pre>\n";

if (!$one_project) {
    // log tracetimes
    $tracetimea = time();
    $tooktime = $tracetimea - $tracetime;
    mysql_query("
        INSERT INTO job_logs 
            (filename, tracetime, event, comments)
        VALUES 
            ('automodify.php',
             $tracetimea,
             'MIDDLE',
             'pre autorelease,
             $tooktime seconds so far')");

    autorelease();

    // log tracetimes
    $tracetimea = time();
    $tooktime = $tracetimea - $tracetime;
    mysql_query("
        INSERT INTO job_logs 
            (filename, tracetime, event, comments)
        VALUES 
            ('automodify.php',
             $tracetimea,
             'END',
             'post autorelease, started at $tracetime, took $tooktime seconds')");

}
else {
    // log tracetimes
    $tracetimea = time();
    $tooktime = $tracetimea - $tracetime;
    mysql_query("
        INSERT INTO job_logs 
            (filename, tracetime, event, comments)
        VALUES 
            ('automodify.php',
             $tracetimea,
             'END',
             'end single, started at $tracetime, took $tooktime seconds')");


    $refresh_url = @$_GET['return_uri'];
    if ( empty($refresh_url) ) 
        $refresh_url = 'projectmgr.php';
    echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"0 ;URL=$refresh_url\">";
}

// -----------------------------------------------------------------------------

// function hold_project_between_rounds( $project ) {
    // return false;
    // return ( $project['nameofwork'] == 'Copyright Renewals 1950' );

    // If holding between rounds becomes popular, we'll obviously
    // want a more flexible way to answer this question.
// }

// -----------------------------------------------------------------------------

// Do the states of the project's pages (in the given round)
// indicate that the project is bad?
function pages_indicate_bad_project( $projectid, $round ) {
    global $trace;

    // If it has no bad pages, it's good.
    //
    $n_bad_pages = getNumPagesInState($projectid,$round->page_bad_state);
    if ($trace) echo "n_bad_pages = $n_bad_pages\n";
    //
    if ($n_bad_pages == 0) 
        return false;


    // If it has at least 10 bad pages,
    // reported by at least 3 different users, it's bad.
    //
    $n_unique_reporters = getNumPagesInState($projectid,$round->page_bad_state,"DISTINCT(b_user)");
    if ($trace) 
        echo "n_unique_reporters = $n_unique_reporters\n";
    //
    if ($n_bad_pages >= 10 && $n_unique_reporters >= 3) 
        return true;


    // In round 2, if it has any bad pages
    // and no available pages, it's bad.
    //
    if ($round->round_number == 2) {
        $n_avail_pages = getNumPagesInState($projectid,$round->page_avail_state);
        if ($trace) 
            echo "n_avail_pages = $n_avail_pages\n";
        if ($n_avail_pages == 0) 
            return true;
    }

    // Otherwise, it's good.
    //
    return false;
}

function echo_project_info( $project ) {
    global $have_echoed_blurb_for_this_project;

    if ( !$have_echoed_blurb_for_this_project ) {
        echo "\n";
        echo "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX\n";
        echo "projectid  = {$project['projectid']}\n";
        echo "nameofwork = \"{$project['nameofwork']}\"\n";
        echo "state      = {$project['state']}\n";
        echo "\n";
        $have_echoed_blurb_for_this_project = 1;
    }
}

function getNumPages($projectid) {
    global $dpdb;
    return $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM $projectid");
}
function getNumPagesInState($projectid, $state) {
    global $dpdb;
    return $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM $projectid
        WHERE state = '$state'");
}

// vim: sw=4 ts=4 expandtab
?>
