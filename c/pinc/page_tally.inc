<?PHP

// There are two different ways of counting saved-as-done pages in the DP code:
//
// 1) As each page is saved, increment a counter associated with the user who
//    saved the page (and decrement it if the page is un-saved).
// 2) Look in each projectID* table for pages saved in a given interval.
// 3) Recalc counts in "projects" table when an action occurs affecting them.
//
// Lacking concise terminology for this distinction, I propose the term
// "page tally" for (1), and leave "page count" for (2).
//
// This file is for code dealing with page tallies.

global $relPath;
include_once($relPath.'TallyBoard.inc');
include_once($relPath.'stages.inc');
include_once($relPath.'privacy.inc');

// -----------------------------------------------------------------------------

$page_tally_names = array();


/*
$page_tally_names['R*'] =
        _('Pages saved-as-done in old rounds R1+R2');
*/

foreach ( $Round_for_round_id_ as $round ) {
    $page_tally_names[$round->id] =
        sprintf( _('Pages saved-as-done in round %s'), $round->id );
}

// -----------------------------------------------------------------------------

// The following variables assume that the site has been configured with an
// "Entry-Level Round" (ELR): a single round where new users start out and
// become familiar with the site.  The page-tallies from this round are
// distinguished from others in that they are (deemed to be) a fair indicator
// of the user's experience.

// This assignment particularly assumes that the ELR is the first round
$ELR_round = get_Round_for_round_number(1);

$users_ELR_page_tallyboard = new TallyBoard( $ELR_round->id, 'U' );
$teams_ELR_page_tallyboard = new TallyBoard( $ELR_round->id, 'T' );

// See TallyBoard.inc for explanation of these two strings:
//
list($joined_with_user_ELR_page_tallies,$user_ELR_page_tally_column) =
    $users_ELR_page_tallyboard->get_sql_joinery_for_current_tallies( 'u_id' );

// -----------------------------------------------------------------------------

/*
// Add $amount to the user's page tally,
// and to the page tally of each team that the user currently belongs to.
function page_tallies_add( $tally_name, $username, $amount ) {
    // get the user's u_id, and the teams that he/she belongs to
    $result = mysql_query("
        SELECT u_id, team_1, team_2, team_3
        FROM users
        WHERE username = '$username'");
    list($u_id, $team_1, $team_2, $team_3) = mysql_fetch_row($result);

    // update page tally for user
    $user_tallyboard = new TallyBoard( $tally_name, 'U' );
    $user_tallyboard->add_to_tally( $u_id, $amount );

    // update page tally for site
    $site_tallyboard = new TallyBoard( $tally_name, 'S' );
    $site_tallyboard->add_to_tally( 1, $amount );

    // update page tally for each team
    $team_tallyboard = new TallyBoard( $tally_name, 'T' );
    // (The 'array_unique' shouldn't be necessary, but just in case.)
    foreach (array_unique(array($team_1, $team_2, $team_3)) as $team_id) {
        if ( $team_id != 0 ) {
            $team_tallyboard->add_to_tally( $team_id, $amount );
        }
    }
}
*/

// -----------------------------------------------------------------------------

// Not actually tally-specific, but that's all it's used for.
/*
function get_daily_average( $start_time, $total ) {
    $now = time();
    $seconds_since_start = $now - $start_time;
    $days_since_start = $seconds_since_start / 86400;
    return $total / $days_since_start;
}
*/

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// Return the user's page tally for the Entry-Level Round.
/*
function user_get_ELR_page_tally( $username ) {
    global $joined_with_user_ELR_page_tallies, $user_ELR_page_tally_column;

    $res = mysql_query("
        SELECT $user_ELR_page_tally_column
        FROM users $joined_with_user_ELR_page_tallies
        WHERE username='$username'") or die(mysql_error());

    if ( mysql_num_rows($res) == 0 ) {
        // No row matched username='$username'.
        // This probably shouldn't happen.
        return 0;
    }

    return mysql_result($res,0);
}
*/

// -----------------------------------------------------------------------------

/*
//
// $radius is the (maximum) number of neighbors (on each side) to include in
// the neighborhood. (It will include fewer that the maximum iff the target
// user is within $radius of the corresponding end of the ranked list.)
//
// Return the page-tally neighborhood of $username.
//    This is an array:
//    The keys are integers from the range [-$radius, +$radius],
//    indicating a user's position relative to the target user (w.r.t. page tally).
//    (So key=0 refers to the target user.)
//    For a given key, the corresponding value is a PageTally_Neighbor object
//    supplying various information about the page-tally neighbor.
function user_get_page_tally_neighborhood( $tally_name, $username, $radius ) {
    $result = mysql_query("
        SELECT u_id FROM users
        WHERE username='$username'");
    if ( mysql_num_rows($result) == 0 ) {
        // The target user does not appear in the 'users' table.
        // This shouldn't ever happen.
        die( "User '$username' does not appear in the 'users' table." );
    }
    else if ( mysql_num_rows($result) > 1 ) {
        // This shouldn't ever happen either.
        die( "username='$username' matched more than one row in 'users' table." );
    }
    list($u_id) = mysql_fetch_row($result);

    $tallyboard = new TallyBoard( $tally_name, 'U' );
    $nb = $tallyboard->get_neighborhood(
            $u_id, $radius,
            'users',
            'u_id',
            'username, u_privacy, date_created, u_id',
            'current_tally',
            'current_rank');

    $neighbors = array();
    foreach ( $nb as $rel_posn => $row ) {
        if ( should_anonymize( $row['username'], $row['u_privacy'] ) ) {
            $neighbor_is_anonymized = TRUE;
            $neighbor_username = NULL;
            $neighbor_date_joined = NULL;
        }
        else {
            $neighbor_is_anonymized = FALSE;
            $neighbor_username = $row['username'];
            $neighbor_date_joined = $row['date_created'];
        }

        $neighbors[$rel_posn] =
            new PageTally_Neighbor(
                $tallyboard,
                $neighbor_is_anonymized,
                $neighbor_username,
                $neighbor_date_joined,
                $row['u_id'],
                $row['current_tally'],
                $row['current_rank']);
    }

    return $neighbors;
}

class RoundNeighbor
{
    private $_username;
    private $_count;
    private $_rank;
    private $_user;

    function __construct($row) {
        $this->_username    = $row['username'];
        $this->_count       = $row['page_count'];
        $this->_rank        = $row['rank'];
        $this->_user        = new DpUser($this->_username);
    }

    public function PageCount() {
        return $this->_count;
    }

    public function Rank() {
        return $this->_rank;
    }

    public function Username() {
        return $this->_username;
    }
    public function PrivateUsername() {
        return $this->_user->PrivateUsername();
    }
    public function IsAnonymous() {
        return $this->_user->Privacy() > 0;
    }
    public function DateCreatedInt() {
        return $this->_user->DateCreatedInt();
    }
    public function AgeDays() {
        return $this->_user->AgeDays();
    }
}

class PageTally_Neighbor
{
    function __construct( $tallyboard, $is_anonymized, $username, $date_joined, $u_id, $tally, $rank ) {
        $this->tallyboard = $tallyboard;
        $this->is_anonymized = $is_anonymized;
        $this->username = $username;
        $this->date_joined = $date_joined;
        $this->u_id = $u_id;
        $this->current_page_tally = $tally;
        $this->current_page_tally_rank = $rank;
    }

    function is_anonymized()               { return $this->is_anonymized; }
    function get_username()                { return $this->username; }
    function get_date_joined()             { return $this->date_joined; }
    function get_u_id()                    { return $this->u_id; }
    public function get_current_page_tally() {
        return $this->current_page_tally;
    }
    public function get_current_page_tally_rank() {
        return $this->current_page_tally_rank;
    }
}

// -----------------------------------------------------------------------------

// Should we anonymize information about the given user?
function should_anonymize( $username, $user_privacy_setting ) {
    return !can_reveal_details_about( $username, $user_privacy_setting );
}
*/

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// Return an object whose attributes are various useful statistics re
// the site page tally:
//     curr_day_{goal,actual}
//     prev_day_{goal,actual}
//     curr_month_{goal,actual}

function get_site_page_round_summary( $roundid) {
    return get_site_page_tally_summary( $roundid);
}

function get_site_page_tally_summary( $roundid ) {
    global $dpdb;
    $site_stats = new StdClass;

    // Goals

//    $site_stats->curr_day_goal = $dpdb->SqlOneValue("
    $row = $dpdb->SqlOneRow("
            SELECT  pg.goal,
                    pg1.goal ygoal
            FROM phase_goals pg
            JOIN phase_goals pg1
            ON pg.phase = pg1.phase
                AND pg1.goal_date = DATE_ADD(pg.goal_date, INTERVAL -1 DAY)
            WHERE pg.phase = '$roundid'
                AND pg.goal_date = CURRENT_DATE()");
    $site_stats->curr_day_goal = $row["goal"];
    $site_stats->prev_day_goal = $row["goal"];

//    $site_stats->prev_day_goal = $dpdb->SqlOneValue("
//            SELECT goal FROM site_tally_goals
//            WHERE tally_name = '$roundid'
//                AND date = DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)" );
        // get_site_tally_goal_summed(
            // $tally_name,
            // "date = (CURRENT_DATE - INTERVAL 1 DAY)" );

    $site_stats->curr_month_goal = $dpdb->SqlOneValue("
            SELECT SUM(goal) FROM phase_goals
            WHERE phase = '$roundid'
                AND goal_date >= DATE(DATE_FORMAT(NOW() ,'%Y-%m-01'))
                AND goal_date < DATE_ADD(DATE(DATE_FORMAT(NOW() ,'%Y-%m-01')), INTERVAL 1 MONTH)");
        // get_site_tally_goal_summed(
            // $tally_name,
            // "YEAR(date) = YEAR(CURRENT_DATE) AND
            // MONTH(date) = MONTH(CURRENT_DATE)" );


    // Actuals

    // $tallyboard = new TallyBoard( $tally_name, 'S' );

//    $holder_id = 1;

    $site_stats->curr_day_actual = $dpdb->SqlOneValue("
            SELECT COUNT(1) FROM page_events_save
            WHERE round_id = '$roundid'
                AND timestamp >= UNIX_TIMESTAMP(CURRENT_DATE())");

    $site_stats->prev_day_actual = $dpdb->SqlOneValue("
            SELECT COUNT(1) FROM page_events_save
            WHERE round_id = '$roundid'
                AND timestamp >= UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE(), INTERVAL 1 day))
                AND timestamp < UNIX_TIMESTAMP(CURRENT_DATE())");

    $site_stats->curr_month_actual = $dpdb->SqlOneValue("
        SELECT SUM(pagecount)
        FROM
        (   SELECT SUM(urp.page_count) pagecount
            FROM user_round_pages urp
            WHERE round_id = '$roundid'
                AND count_time >= UNIX_TIMESTAMP(DATE(DATE_FORMAT(NOW() ,'%Y-%m-01')))
            UNION ALL
            SELECT COUNT(1) pagecount
            FROM page_events_save
            WHERE round_id = '$roundid'
                AND TIMESTAMP > UNIX_TIMESTAMP(CURRENT_DATE())
        ) a");

    return $site_stats;
}

/*
function get_site_count_goal_summed( $roundid, $date_condition ) {
    $res = mysql_query("
        SELECT SUM(goal) FROM site_count_goals
        WHERE count_name = '$roundid' 
            AND ($date_condition)") or die(mysql_error());
    return mysql_result($res,0);
}
*/

// -----------------------------------------------------------------------------

// Return a string containing an SQL 'select' statement
// dealing with site-specific rows from the past_tallies table,
// and corresponding rows from the site_count_goals table.
function select_from_site_past_tallies_and_goals( $tally_name, $select,
                                        $where, $groupby, $orderby, $limit ) {
    if (empty($where)) {
        $where_addition = '';
    }
    else {
        $where_addition =
            preg_replace( '/^\s*WHERE\s+(.*)$/i', 'AND (\1)', $where );
    }

    $s = "
        $select
        FROM past_tallies
        LEFT JOIN site_tally_goals
        ON past_tallies.tally_name = site_tally_goals.tally_name
            AND {date} = site_tally_goals.date
        WHERE past_tallies.tally_name='$tally_name'
            AND holder_type='S'
            AND holder_id=1
            $where_addition
        $groupby
        $orderby
        $limit";

    $date_expr = "FROM_UNIXTIME(past_tallies.timestamp-1,'%Y-%m-%d')";
    $ym_expr   = "FROM_UNIXTIME(past_tallies.timestamp-1,'%Y-%m')";
    $y_expr    = "FROM_UNIXTIME(past_tallies.timestamp-1,'%Y')";

    $is_curr_month_expr = "$ym_expr = DATE_FORMAT(NOW(),'%Y-%m')";
    $is_curr_year_expr  = "$y_expr  = YEAR(NOW())";

    $s = preg_replace( '/{date}/',          $date_expr,          $s );
    $s = preg_replace( '/{year_month}/',    $ym_expr,            $s );
    $s = preg_replace( '/{is_curr_month}/', $is_curr_month_expr, $s );
    $s = preg_replace( '/{is_curr_year}/',  $is_curr_year_expr,  $s );

    return $s;
}

// vim: sw=4 ts=4 expandtab
