<?PHP
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');

$roundid = Arg("roundid", Arg("tally_name"));
$tid     = Arg("tid");
$range   = Arg("range");

$team = new DpTeam($tid);

if(! $team->Exists()) {
    die("No existing team selected.");
}

$stats = _("Statistics");

theme($team->TeamName() . " " . $stats, "header");
echo "<br>
<div class='center'>";


showTeamProfile($team);

if ( $roundid != "") {
    showTeamStats($team, $roundid);
    showTeamMembers($team, $roundid);

    // Only show the team history if they are more than a day old
    if($team->CreatedDaysAgo() > 1 && (int) $roundid != 0) {
        showTeamHistoryChart($team, $roundid, $range);
    }
}

echo "</div>";
theme("", "footer");
exit;

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function showTeamStats($team, $roundid) {
    /** @var DpTeam $team */

    echo "<div>
        <h4 class='center'>"
        . _("Total $roundid Pages: {$team->RoundPageCount($roundid)} - $roundid Rank: {$team->RoundRank($roundid)}
        </h4></div>\n");
}

function showTeamMembers($team, $roundid) {
    global $dpdb;
    /** @var DpTeam $team */

    $sql = "
    SELECT
        ut.username,
        SUM(urp.page_count) page_count,
        ut.joined_time
    FROM user_team ut

    JOIN users u ON ut.username = u.username

    JOIN user_round_pages urp ON ut.username = urp.username AND urp.phase = '$roundid'

    WHERE ut.team_id = {$team->Id()}
    GROUP BY ut.username
    ORDER BY page_count DESC";

    $rows = $dpdb->SqlRows($sql);

    $tbl = new Dptable("tblteampages", "dptable sortable minitab w50", "Team Member Details");
    $tbl->AddColumn("<Username", "username");
    $tbl->AddColumn(">Pages", "page_count");
    $tbl->AddColumn(">Date joined", "joined");

    $tbl->SetRows($rows);
    $tbl->Echotable();
}

function showTeamHistoryChart($team, $roundid, $range = 30) {
    /** @var DpTeam $team */

    $choices = array();
    foreach ( array( 7, 14, 30, 60, 365 ) as $d ) {
        $text = sprintf( _('Last %d Days'), $d );
//			($d == 'all')
//			? _('Lifetime')
//			: sprintf( _('Last %d Days'), $d );
        $choices[] = "<a href='tdetail.php"
            ."?tid=".$team->Id()
            ."&roundid=$roundid"
            ."&range=$d'>$text</a>";
    }
    $choices_str = join( $choices, ' | ' );

    echo_team_count_chart($team->Id(), $roundid, $range);

    echo "<div>\n";
    echo  "<p>$choices_str</p>
	    </div>\n";
}

function echo_team_count_chart($teamid, $roundid, $range) {
    echo "<div id='divteamchart' class='dpchart'> </div>\n";
    $data = TeamPagesNDays($teamid, $roundid, $range);
    $title = _("Pages Saved-as-Done in Round $roundid");
//        $roundid == "ALL"
//        ? _("Pages Saved-as-Done in All Rounds")
//        : _("Pages Saved-as-Done in Round $roundid");
    makeColumnChart($data, $title, "divteamchart");
}

function makeColumnChart($data, $caption, $div) {

    echo "
<script type='text/javascript' src='https://www.google.com/jsapi'></script>
<script type='text/javascript'>
  google.load('visualization', '1', {packages:['corechart']});
  google.setOnLoadCallback(drawChart);
  function drawChart() {
    // var data = new google.visualization.DataTable();
    var data = new google.visualization.arrayToDataTable(
        {$data}
    );
    var options = {
        title: '$caption',
        width: 900,
        height: 450,
        vAxis: {baseline: 0},
        legend: {position: 'none'}
    };

    var chart = new google.visualization.ColumnChart(document.getElementById('$div'));
    chart.draw(data, options);
}
</script>\n";
}

function TeamPagesNDays($teamid, $roundid, $range) {
    global $dpdb;
    $sql = "
        SELECT  DATE_FORMAT(d.dateval, '%b %e') dateval,
                SUM(IFNULL(page_count, 0)) pages
        FROM days d
        LEFT JOIN team_round_pages trp
        ON d.min_unixtime = trp.count_time
        WHERE trp.phase = '$roundid'
            AND team_id = $teamid
            AND d.dateval >=  DATE_SUB(CURRENT_DATE(), INTERVAL {$range} DAY)
        GROUP BY d.dateval
        ORDER BY d.dateval";
    $rows = $dpdb->SqlRows($sql);
    $ary = array(array("date", "pages"));
    foreach($rows as $row) {
        $ary[] = array($row["dateval"], (int) $row["pages"]);
    }

    return json_encode($ary);
}

function showTeamProfile($team) {
    /** @var DpTeam $team */
    global $code_url;

    $creator_url    = "$code_url/stats/members/member_stats.php?username=" . $team->CreatedBy();
    $creator_link   = link_to_url($creator_url, $team->CreatedBy());
//    $topic_id          = $team->TopicId();

//    if(! $topic_id) {
//        $team->CreateTeamTopic();
//    }

    $link_to_topic     = $team->TopicLink("Forum Discussion");

    $links_to_stats = $team->StatsLink("P1")
                        . $team->StatsLink( "P2")
                        . $team->StatsLink( "P3")
                        . $team->StatsLink( "F1")
                        . $team->StatsLink( "F2");

    $tbl = new DpTable("tblteam", "dptable center minitab w75");
    $tbl->NoColumnHeadings();
    $tbl->SetTitle($team->TeamName());
    $rows = array();
    $rows[] = array( _("Created"), $team->CreatedDate() . _(" ({$team->CreatedDaysAgo()} days ago)"));
    $rows[] = array( _("Leader"), $creator_link);
    $rows[] = array( _("Description"), $team->Info());
    $rows[] = array( _("Forum"), $link_to_topic);
    // $rows[] = array( _("Members") . " <i>("._("Rank").")</i>",
        // number_format($team->MemberCount()) . "&nbsp;<i>(#{$team->MemberRank()})</i>" );
    $rows[] = array( _("Current Members"), number_format(count($team->MemberCount())));
//    $rows[] = array( _("Retired Members"), number_format($team->RetiredMembers()));

    $tbl->AddColumn("<", 0);    // caption
    $tbl->AddColumn("<", 1);    // data
    $tbl->SetRows($rows);


    echo "
        <div class='center w75'>\n";

    $tbl->EchoTable();

    echo "</div>
        <div class='w100'>
        View team stats for $links_to_stats
        </div>\n";
}
