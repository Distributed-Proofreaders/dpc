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

$User->IsLoggedIn()
    or redirect_to_home();

$joinid     = ArgInt("join");
$quitid     = ArgInt("quit");

if($joinid) {
    $User->AddTeamId($joinid);
}

if($quitid) {
    $User->QuitTeamId($quitid);
}


$where = $roundid == ""
    ? ""
    : "AND urp.phase = ?";

$username = $User->Username();

$sql = "
        SELECT
            t.teamname,
            t.team_id,
            COUNT(DISTINCT ut1.username) usercount,
            SUM(user_round_pages.page_count) pagecount,
            ut2.id ismember

        FROM
        teams t

        LEFT JOIN users_teams ut0
            ON t.team_id = ut0.team_id

        LEFT JOIN users_teams ut1
            ON ut0.team_id = ut1.team_id

        LEFT JOIN users_teams ut2
            ON t.team_id = ut2.team_id
            AND ? = ut2.username

        LEFT JOIN user_round_pages
            ON ut1.username = user_round_pages.username
            $where

        GROUP BY t.teamname
        ORDER BY t.teamname";

if($roundid) {
    $args = array(&$roundid, &$username);
}
else {
    $args = array(&$username);
}
$rows = $dpdb->SqlRowsPS($sql, $args);


$tbl = new DpTable("tblteams", "dptable sortable w75");
// $tbl->AddColumn("^ID", "id");
$tbl->AddColumn("<Team Name", "teamname", "eTeamname");
$tbl->AddColumn("^Members", "usercount");
$tbl->AddColumn(">Pages", "pagecount", "eCount");
$tbl->AddColumn("^", "team_id", "eJoin");
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

function eTeamname($name, $row) {
    $team_id = $row['team_id'];
    $ismember = $row['ismember'];
    return link_to_team($team_id, $name, true, $ismember);
}

function eCount($count) {
    return number_format($count);
}

function eJoin($id)
{
    global $User;
    return $User->IsTeamMemberOf($id)
        ? link_to_quit_team($id)
        : link_to_join_team($id);
}

function link_to_join_team($id) {
    $url = url_for_team_list() . "?join=$id";
    return link_to_url($url, "Join");
}
function link_to_quit_team($id) {
    $url = url_for_team_list() . "?quit=$id";
    return link_to_url($url, "Quit");
}
//    if($User->Team1() ==  $id || $User->Team2() == $id || $User->Team3() == $id) {
//        $quit = _("Quit");
//        return "<a href='../teams/jointeam.php?tid={$id}'>$quit</a>\n";
//    }
//    else {
//        $join = _("Join");
//        return "<a href='../teams/jointeam.php?tid={$id}'>$join</a>\n";
//    }


