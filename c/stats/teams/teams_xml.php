<?PHP
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'prefs_options.inc');
include_once($relPath.'xml.inc');
include_once($relPath.'page_tally.inc');
include_once('../teams/team.inc');
include_once('../members/member.inc');

$id = Arg("id");
if(! $id) {
	include_once($relPath.'theme.inc');
	theme("Error!", "header");
	echo "<br><center>A team id must specified in the following format:<br>$code_url/stats/teams/teams_xml.php?id=****</center>";
	theme("", "footer");
	exit();
}

//Try our best to make sure no browser caches the page
header("Content-Type: text/xml");
header("Expires: Sat, 1 Jan 2000 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$team = new DpTeam($id);

//Team info portion of $data

	$totalTeams = $dpdb->SqlOneValue("
	    SELECT COUNT(1) FROM user_teams");

	$data = "<teaminfo id='$team_id'>
			<teamname>".xmlencode($team->TeamName())."</teamname>
			<datecreated>".$team->CreatedStr()."</datecreated>
			<leader>".xmlencode($team->Owner())."</leader>
			<description>".xmlencode($team->Info())."</description>
			<website>".xmlencode($team->WebPage())."</website>
			<forums>".xmlencode($GLOBALS['forums_url']."/viewtopic.php?t=".$team->TopicId())."</forums>
			<totalmembers>".$team->MemberCount()."</totalmembers>
			<currentmembers>".$team->ActiveMembers()."</currentmembers>
			<retiredmembers>".$team->RetiredMembers()."</retiredmembers>";

	// foreach ( $page_tally_names as $tally_name => $tally_title ) {
		// $teams_tallyboard = new TallyBoard( $tally_name, 'T' );

		// $pageCount = $teams_tallyboard->get_current_tally( $team_id );
		// $pageCountRank = $teams_tallyboard->get_rank( $team_id );

//		$avg_pages_per_day = get_daily_average( $curTeam['created'], $pageCount );

//		list($bestDayCount, $bestDayTimestamp) =
//			$teams_tallyboard->get_info_re_largest_delta( $team_id );
//		$bestDayTime = date("M. jS, Y", ($bestDayTimestamp-1));

		$data .= "
			<roundinfo id='$tally_name'>
				<totalpages>$pageCount</totalpages>
				<rank>".$pageCountRank."/".$totalTeams."</rank>
			</roundinfo>";
//	}

	$data .= "
		</teaminfo>
	";

//Team members portion of $data
	$data .= "<teammembers>";
	$rows = $dpdb->SqlRows("
		SELECT username, date_created, u_id, u_privacy
		FROM users
		WHERE $team_id IN (team_1, team_2, team_3)
		ORDER BY username
	");
    foreach($rows as $curMbr) {
		if ($curMbr['u_privacy'] == PRIVACY_PUBLIC) {
			$data .= "<member id=\"".$curMbr['u_id']."\">
				<username>".xmlencode($curMbr['username'])."</username>
				<datejoined>".date("m/d/Y", $curMbr['date_created'])."</datejoined>
			</member>
			";
		}
	}
	$data .= "</teammembers>";


$xmlpage = "<"."?"."xml version=\"1.0\" encoding=\"$charset\" ?".">
<teamstats xmlns:xsi=\"http://www.w3.org/2000/10/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"teamstats.xsd\">
$data
</teamstats>";

echo $xmlpage;
exit;

function get_daily_average( $start_time, $total ) {
    $now = time();
    $seconds_since_start = $now - $start_time;
    $days_since_start = $seconds_since_start / 86400;
    return $total / $days_since_start;
}


