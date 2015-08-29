<?PHP

ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../../pinc/";
include_once $relPath . 'dpinit.php';

$sql = "
    SELECT t.id,
            t.teamname,
            trpt.round_id,
            trpt.page_count,
            ct.tally_value
    FROM user_teams t
    LEFT JOIN team_round_pages_total trpt
        ON t.id = trpt.team_id
    LEFT JOIN current_tallies ct
        ON trpt.round_id = ct.tally_name 
            AND ct.holder_type = 'T'
            AND trpt.team_id = ct.holder_id
    LEFT JOIN rounds r ON trpt.round_id = r.roundid
GROUP BY t.id, t.teamname, trpt.round_id
ORDER BY t.teamname, r.round_index";

$rows = $dpdb->SqlRows($sql);

$tbl = new DpTable("tblteams", "dptable sortable w75");
$tbl->AddColumn("<Team Name", "teamname");
$tbl->AddColumn("<Round", "round_id");
$tbl->AddColumn(">Old 2013-10-05 Pages", "tally_value");
$tbl->AddColumn(">New 2013-10-05 Pages", "page_count");
$tbl->SetRows($rows);

theme(_("Teams"), "header");

echo _("<h1 class='center'>Teams</h1>\n");
$tbl->EchoTable();

theme("", "footer");
exit;

