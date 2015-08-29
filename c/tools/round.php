<?PHP
$relPath = "../pinc/";
include_once($relPath.'dpinit.php');

$User->IsLoggedIn()
	or RedirectToLogin();

$roundid = Arg("roundid");

$User->MayWorkInRound($roundid)
	or die("Trying to access unauthorized page.");

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
                   DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(p.modifieddate)) AS days_avail,
				   DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(MAX(pe.timestamp))) AS last_save_days
        FROM projects p
        LEFT JOIN page_events pe
        	ON p.projectid = pe.projectid
        	AND pe.event_type = 'saveAsDone'
        WHERE p.phase = '$roundid'
            AND NOT p.projectid IN
                   (    SELECT projectid FROM project_holds
                        WHERE phase = p.phase
                   )
        ORDER BY days_avail, p.nameofwork");


$tbl = new DpTable();
$tbl->AddColumn("<Title", "title", "etitle");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("<Language", "language");
$tbl->AddColumn("<Genre", "genre");
$tbl->AddColumn("^Project<br>Manager", "username", "epm");
$tbl->AddColumn("^Avail<br>pages", "n_available_pages");
$tbl->AddColumn("^Total<br>pages", "n_pages");
$tbl->AddColumn(">Days in<br>Round", "days_avail");
$tbl->AddColumn(">Days<br>Last Save", "last_save_days");
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
