<?PHP

ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../../pinc/";
include_once $relPath . 'dpinit.php';

$roundid    = Arg("roundid");
$tname      = Arg("tname");
$tname      = ($tname == ""
                    ? "%"
                    : "%tname%");

$sql = "
    SELECT  ut.id,
            SUM(usercount) AS usercount, 
            ut.teamname,
            ut.icon,
            b.page_count
    FROM user_teams ut
    LEFT JOIN (
        SELECT u.team_1 AS team_id, COUNT(1) usercount FROM users u
        WHERE u.team_1 > 0
        GROUP BY u.team_1

        UNION ALL

        SELECT u.team_2 AS team_id, COUNT(1) usercount FROM users u
        WHERE u.team_2 > 0
        GROUP BY u.team_2

        UNION ALL

        SELECT u.team_3 AS team_id, COUNT(1) usercount FROM users u
        WHERE u.team_3 > 0
        GROUP BY u.team_3
    ) AS a
    ON ut.id = a.team_id
    LEFT JOIN (
        SELECT team_id, SUM(page_count) page_count
        FROM team_round_pages 
        GROUP BY team_id
    ) b
    ON ut.id = b.team_id
    GROUP BY ut.id
    ORDER BY page_count DESC";
    

$rows = $dpdb->SqlRows($sql);

$tbl = new DpTable("tblteams", "dptable sortable w75");
// $tbl->AddColumn("^ID", "id");
$tbl->AddColumn("^Icon", "icon", "eIcon");
$tbl->AddColumn("<Team Name", "teamname", "eTeamname");
$tbl->AddColumn("^Members", "usercount");
$tbl->AddColumn(">Pages", "page_count", "eCount");
$tbl->AddColumn("^", "id", "eJoin");
$tbl->SetRows($rows);

theme(_("Teams"), "header");
// echo_head("teams");

echo _("<h1 class='center'>Teams</h1>\n");
$tbl->EchoTableNumbered();

echo "<h4 class='center'> " . link_to_create_team() . "</h4>\n";

theme("", "footer");
exit;
//  html_end();

function eIcon($iconfile) {
    global $team_icons_url;
    return "<img src='{$team_icons_url}/{$iconfile}' alt=''>";
}

function eTeamname($val, $row) {
    $id = $row['id'];
    return "<a href='tdetail.php?tid={$id}'>$val</a>\n";
}

function eCount($count) {
    return number_format($count);
}

function eJoin($id) {
    global $User;
    if($User->Team1() ==  $id || $User->Team2() == $id || $User->Team3() == $id) {
        $quit = _("Quit");
        return "<a href='../members/jointeam.php?tid={$id}'>$quit</a>\n";
    }
    else {
        $join = _("Join");
        return "<a href='../members/jointeam.php?tid={$id}'>$join</a>\n";
    }
}
            

