<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);
$relPath = "../../pinc/";
include_once($relPath.'dpinit.php');

$roundid = Arg("roundid");

theme("Round $roundid", "header");

$rows = $dpdb->SqlRows("
            SELECT p.projectid,
                   p.nameofwork title,
                   p.authorsname,
                   p.genre,
                   p.language,
                   p.difficulty,
                   p.username,
                   p.n_available_pages,
                   p.n_pages,
                   DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(p.modifieddate)) AS days_avail
        FROM projects p
        LEFT JOIN project_holds ph
        ON p.projectid = ph.projectid AND p.phase = ph.phase
        WHERE p.phase = '$roundid'
            AND ph.id IS NULL
        ORDER BY days_avail, p.nameofwork");


$tbl = new DpTable();
$tbl->AddColumn("<Title", "title", "etitle");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("<Language", "language");
$tbl->AddColumn("<Genre", "genre");
$tbl->AddColumn("^Project<br>Manager", "username", "epm");
$tbl->AddColumn("^Avail<br>pages", "n_available_pages");
$tbl->AddColumn("^Total<br>pages", "n_pages");
$tbl->AddColumn(">Days", "days_avail");
$tbl->SetRows($rows);
$tbl->EchoTable();
// $tbl->AddCaption("<h2>Projects Currently Avaailable in $roundid</h2>");

theme("", "footer");
exit;

function etitle($title, $row) {
    $projectid = $row['projectid'];
    return link_to_project($projectid, $title);
}

function epm($username) {
    return link_to_pm($username);
}
