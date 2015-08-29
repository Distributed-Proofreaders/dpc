<?PHP
/*
     Displays information useful to Mentors.
    (i.e. those who are second-round proofreading projects with difficulty = "BEGINNER")

    ************************************
*/


ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath='../..//pinc/';
include_once($relPath.'dpinit.php');

function project_sql() {
    return
        "SELECT
            projectid,
            nameofwork,
            authorsname
        FROM projects
        WHERE phase = 'P2'
        ORDER BY modifieddate" ;
}

function page_summary_sql($projectid) {
//    global $forums_url, $code_url, $mentored_round_id;

//    $round_tallyboard = new TallyBoard($mentored_round_id, 'U' );

//    list($joined_with_user_page_tallies,$user_page_tally_column) =
//            $round_tallyboard->get_sql_joinery_for_current_tallies('u.u_id');

    return "
        SELECT
            pv.username,
            SUM(1) p1count,
            SUM(urp.page_count) roundpages
        FROM page_versions pv
        JOIN user_round_pages urp
            ON pv.username = urp.username
	    WHERE pv.projectid = '$projectid'
	    	AND phase = 'P1'
        GROUP BY pv.username
        ORDER BY pv.username";

    /*
    return "SELECT
                CASE WHEN u.u_privacy = ".PRIVACY_ANONYMOUS." THEN 'Anonymous'
                ELSE CONCAT('<a href=\""
                    .$code_url . "/stats/members/member_stats.php?&id=',u.u_id,
                    '\">',u.username,'</a>')
                END AS " . _("Proofreader") . ",
                COUNT(1) AS '" . _("Pages this project") . "',
                $user_page_tally_column AS '" . sprintf(_("Total %s Pages"),$mentored_round_id) . "',
                DATE_FORMAT(FROM_UNIXTIME(u.date_created),'%M-%d-%y') AS Joined
            FROM $projectid  AS p
                INNER JOIN users AS u ON p.round1_user = u.username
                INNER JOIN phpbb_users AS bbu ON u.username = bbu.username
                $joined_with_user_page_tallies
            GROUP BY p.round1_user" ;
    */
}

function page_list_sql($projectid) {
    return "
    SELECT
        pv.pagename,
        pv.username
	page_last_versions pv
		ON p.projectid = pv.projectid
	JOIN users u
		ON pv.username = u.username
	WHERE pv.projectid = '$projectid'
    ORDER BY pagename";
}


// Collect the data.

// Project selection. ****************************************************************

// Collect the projects to report.
// Hold the result in an array
// and release the database locks.


    // Page header. **********************************************************************

    // Display page header.

    theme(_("For Mentors"), "header");

    // ---------------------------------------------------------------

    $round_id = Arg("round_id");
//    if ( $round_id != '' ) {
//        $mentoring_round = get_Round_for_round_id($round_id);
//    }
//    else
//    {
        // Consider the page they came from.
//        $referer = $_SERVER['HTTP_REFERER'];

        // If they're coming to this page from a MENTORS ONLY book in X2, 
        // referrer should contain &expected_state=X2.proj_avail.
//        foreach ( $Round_for_round_id_ as $round ) {
//            if ( strpos($referer, $round->project_available_state) ) {
//                $mentoring_round = $round;
//                break;
//            }
//        }

/*
        if ( !isset($mentoring_round) ) {
            // Just take the first.
            foreach ( $Round_for_round_id_ as $round ) {
                if ( $round->is_a_mentor_round() ) {
                    $mentoring_round = $round;
                    break;
                }
            }
            if ( !isset($mentoring_round) ) {
                die("There are no mentoring rounds!");
            }
        }
    }

    if ( !$mentoring_round->is_a_mentor_round() ) {
        die("$mentoring_round->id is not a mentoring round!");
    }

    // ---------------------------------------------------------------

    // Are there other mentoring rounds? If so, provide mentoring links for them.
    $other_mentoring_rounds = array();
    foreach ( $Round_for_round_id_ as $round ) {
        if ( $round->is_a_mentor_round() && $round->id != $mentoring_round->id ) {
            $other_mentoring_rounds[] = $round;
        }
    }
    if ( count($other_mentoring_rounds) > 0 ) {
        echo "<p>(" . _('Show this page for:');

        foreach( $other_mentoring_rounds as $other_round ) {
            $url = "$code_url/tools/proofers/for_mentors.php?round_id={$other_round->id}";
            echo " <a href='$url'>{$other_round->id}</a>";
        }
        echo ")</p>";
    }
*/

    // ---------------------------------------------------------------

    if(! $User->MayMentor()) {
        echo  _("You do not have access to 'Mentors Only' projects in P2.\n");
        theme("","footer");
        exit;
    }

    // ---------------------------------------------------------------

    echo _("<h2>Pages available to Mentors in round P2.</h2>
        <p>Oldest project listed first.</p>\n");

    $projobjs = $dpdb->SqlObjects(project_sql());

    foreach($projobjs as $proj) {
        echo "<p>{$proj->nameofwork}</p>
                <p>{$proj->authorsname}</p>\n";

        $tbl1 = new DpTable();
        $tbl1->SetRows( $dpdb->SqlRows(page_summary_sql($proj->projectid)));
        $tbl1->EchoTable();

        $tbl2 = new DpTable();
        $tbl2->SetRows( $dpdb->SqlRows(page_list_sql($proj->projectid)));
        $tbl2->EchoTable();
    }

    theme("","footer");

// vim: sw=4 ts=4 expandtab
?>
