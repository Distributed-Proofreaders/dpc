<?php
/*
 * Args are username
 */

$relPath="./../../pinc/";
include_once $relPath.'dpinit.php';
include_once '../members/member.inc';

global $User;
$roundid = Arg('round_id', Arg('roundid'));
$usrname = Arg ('username', $User->Username());
$usr = new DpUser($usrname);
//$usr->FetchUser();
if($usr->Privacy() != 0 && $usr->Username() != $User->Username()) {
	die("No such user, or user is Private.");
}
if(! $usr->Exists()) {
    die("Invalid name - $usrname");
}

theme("DPC: Profile for $usrname", "header");

echo _("<h1 class='center'>Details for {$usr->PrivateUsername()}</h1>\n");

if ( $usr->Privacy() > 0 && ! $User->IsAdmin()) {
	echo _( "<p class='center'>This user has requested their statistics be private.</p>\n" );
	theme( "", "footer" );
	exit;
}

EchoMemberDpProfile($usr);
if( $roundid == "all") {
    EchoMemberAllStats($usr);
}
else if ( $roundid ) {
	EchoMemberRoundStats($usr, $roundid);
	EchoMemberTeams($usr, $roundid);
	EchoMemberNeighbors($usr, $roundid);
	if($usr->AgeDays() > 1 ) {
		EchoMemberHistory($usrname, $roundid);
	}
}
else {
	EchoMemberAllStats($usr);
}

theme("", "footer");

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/*
function EchoMemberStats($usrname) {
    global $dpdb;
    $sql = "SELECT 	DATE(FROM_UNIXTIME(MIN(count_time))) first_date,
					round_id,
					SUM(page_count) page_count
            FROM user_round_pages
            WHERE username = '{$usrname}'
            GROUP BY round_id";
    $objs = $dpdb->SqlObjects($sql);

    $t = new DpTable("tblall", "w75 dptable minitab", _("Total Page Statistics"));
    $t->NoColumnHeadings();

	$rows = array();
    $rows[] = array( _("Active since"), $objs[0]->first_date);
	$tot = 0;
	foreach($objs as $obj) {
		$rows[] = array($obj->round_id, $obj->page_count);
		$tot += $obj->page_count;
	}
	$rows[] = array( _("Total Pages"), $tot);
    $t->SetRows($rows);
    $t->EchoTable();
}
*/

/**
 * @param DpUser $usr
 * @param string $roundid
 */
function EchoMemberRoundStats( $usr, $roundid ) {
    if($roundid == "ALL") {
        EchoMemberAllStats($usr);
        return;
    }

    $round_start        = $usr->FirstRoundDate($roundid);
    $roundPageCount     = number_format($usr->RoundPageCount($roundid)) . _(" pages");
    $round_rank         = number_format($usr->RoundRank($roundid));

    $t = new DpTable("tblstats", "w75 dptable minitab", _("$roundid Page Statistics"));
    $t->NoColumnHeadings();
    $rows = array(
        array( _("Active in $roundid since"), $round_start),
        array( _("Total Round Pages"), $roundPageCount),
        array( _("Overall Round Rank"), $round_rank)
    );
    $t->AddColumn("<", 0);
    $t->AddColumn("^", 1);
    $t->SetRows($rows);
    $t->EchoTable();
}

/** @var DpUser $usr */
function EchoMemberAllStats($usr) {
	global $dpdb;

	$username = $usr->Username();

	$sql = "SELECT username, round_id, page_count
			FROM total_user_round_pages urpt
			JOIN phases p ON urpt.phase = p.phase
			WHERE username = '$username'
			ORDER BY p.sequence";

	$rows = $dpdb->SqlRows($sql);
	$total = 0;
	foreach($rows as $row) {
		$total += $row['page_count'];
	}
	$rows[] = array("username" => $username, "round_id" => "Total", "page_count" => $total);

	$t = new DpTable("tblstats", "w75 dptable minitab", _("Total Page Statistics"));
	$t->NoColumnHeadings();
//	$rows = array(
//		array( _("Active since"), $start),
//		array( _("Total Pages"), $pagecount),
//	);
	$t->AddColumn("<", 'round_id');
	$t->AddColumn(">", 'page_count');
	$t->SetRows($rows);
	$t->EchoTable();

}

function EchoMemberNeighbors($usr, $roundid) {
    /** @var DpUser $usr */
    $neighbors = $usr->RoundNeighborhood($roundid, 10);

    $ntbl = new DpTable("tblneighbors", "dptable minitab w75", "$roundid Neighborhood");
//    $ntbl->NoColumnHeadings();
    $ntbl->AddColumn("^Rank", "rank");
    $ntbl->AddColumn("<Name", "username", "ename");
    $ntbl->AddColumn(">Pages", "page_count");
    $ntbl->SetRows($neighbors);
    $ntbl->EchoTable();
}

function EchoMemberTeams($usr, $roundid) {
    /** @var DpUser $usr */

    $teams = $usr->Teams();
    if(count($teams) == 0) {
        return;
    }

    $t = new DpTable("tblmyteams", "dptable minitab w75", "User's Teams");
    $t->AddColumn("<Team Name", "teamname");
    $t->AddColumn(">Team Pages", "teampages");
    $rows = array();
    /** @var DpTeam $team */
    foreach($teams as $team) {
        $rows[] = array( "teamname" => $team->Name(), "id" => $team->Id(), "phase" => $roundid,
            "teampages" => $team->RoundPageCount($roundid));

    }
    $t->SetRows($rows);
    $t->EchoTable();
}

function eTeamLink($team_id, $roundid) {
    return link_to_team_stats($team_id, $roundid);
}

function EchoMemberHistory($usrname, $roundid) {
    /** @var DpUser $usr */
    $range = Arg("range", 30);
    $choices = array();
    foreach( array( 7, 14, 30, 60, 365, 'all' ) as $range ) {
        $text = ($range == 'all')
            ? _('Lifetime')
            : sprintf( _('Last %d Days'), $range );
        $choices[] = link_to_member_stats($usrname, $roundid, $text);
    }
    $choices_str = join( $choices, ' | ' );
    $history_link = link_to_user_history($usrname, $roundid, $range, "User count history");

    echo "<p>$history_link</p>\n";
    echo "<p>$choices_str</p>\n";
}

function link_to_user_history($username, $roundid, $range, $prompt) {
    return link_to_url(url_for_user_history($username, $roundid, $range), $prompt);
}

function url_for_user_history($username, $roundid, $range) {
    global $code_url;
    return "$code_url/stats/members/daily_counts.php"
    ."?username=$username"
    ."&amp;roundid=$roundid"
    ."&amp;range=$range";
}
