<?PHP
// Give information about a single round,
// including (most importantly) the list of projects available for work.

$relPath='../../pinc/';
include_once $relPath.'dpinit.php';

$User->IsLoggedIn()
	or RedirectToLogin();

$phase = Arg("round_id", Arg("roundid"));


if(! $phase) {
    die("round.php invoked with invalid round_id='$phase'.");
}
$User->IsLoggedIn()
	or die("Invalid attempt to access Round $phase");

/** @var Phase $phase */
$caption = $Context->PhaseDescription($phase);

theme( "$phase", 'header' );

$title = "Round: $caption";
echo "<h1 class='center'>$title</h1>\n";

$sql = "
    SELECT  p.projectid,
            CASE WHEN p.nameofwork LIKE '[BEGIN]%' THEN CONCAT('**', p.nameofwork) ELSE p.nameofwork END AS nameofwork,
            p.authorsname,
            p.language,
            p.genre,
            p.difficulty,
            p.username,
            LOWER(p.username) pmsort,
            SUM(1) n_pages,
            SUM(pv.state = 'A') n_available_pages,
            CASE WHEN p.nameofwork LIKE '[BEGIN]%' THEN 0 ELSE
            IFNULL(DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(MAX(pe.event_time))),
                   DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(p.phase_change_date))) END AS days_avail,
--            DATEDIFF(current_date(), FROM_UNIXTIME(MAX(pe.event_time))) AS since_hold,
--            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(p.phase_change_date)) AS days_avail,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(MAX(pv.version_time))) AS last_save_days
    FROM projects p

    LEFT JOIN page_versions pv
        ON p.projectid = pv.projectid

    LEFT JOIN project_holds ph
        ON p.projectid = ph.projectid
AND p.phase = ph.phase

    LEFT JOIN project_events pe
        ON p.projectid = pe.projectid
        AND p.phase = pe.phase
        AND pe.event_type = 'release_hold'


    WHERE p.phase = ?
        AND ph.id IS NULL

     AND NOT EXISTS (
        SELECT 1 FROM page_versions
        WHERE projectid = pv.projectid
            AND pagename = pv.pagename
            AND version > pv.version
     )
GROUP BY p.projectid
    ORDER BY days_avail, nameofwork
    ";
/*
    SELECT  p.projectid,
            p.nameofwork,
            p.authorsname,
            p.language,
            p.genre,
            p.difficulty,
            p.username,
            LOWER(p.username) pmsort,
            SUM(1) n_pages,
            SUM(plv.state = 'A') n_available_pages,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(p.phase_change_date)) AS days_avail,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(MAX(plv.version_time))) AS last_save_days
    FROM projects p

    LEFT JOIN page_last_versions plv
        ON p.projectid = plv.projectid

    LEFT JOIN project_holds ph
        ON p.projectid = ph.projectid
AND p.phase = ph.phase

    WHERE p.phase = ?
        AND ph.id IS NULL

    GROUP BY p.projectid
    ORDER BY days_avail";
*/

$args = [&$phase];
//echo html_comment($sql);
$rows = $dpdb->SqlRowsPS($sql, $args);

$n = count($rows);
echo "<h3 class='center'>$n projects available in this round.</h3>\n";

if($n < 1) {
	echo "<br><h4>No projects found.</h4>";
}
else {
	$tbl = new DpTable();
	$tbl->AddColumn("<Title", "nameofwork", "etitle");
	$tbl->AddColumn("<Author", "authorsname");
	$tbl->AddColumn("<Language", "language", "elang");
	$tbl->AddColumn("^Diff", "difficulty");
	$tbl->AddColumn("<Genre", "genre");
	$tbl->AddColumn("<Project<br>Mgr", "username", "epm", "sortkey=pmsort");
	$tbl->AddColumn("^Available<br>Pages", "n_available_pages", "enumber");
	$tbl->AddColumn("^Total<br>Pages", "n_pages", "enumber");
	$tbl->AddColumn(">Days in<br>Round", "days_avail", "enumber");
	$tbl->AddColumn(">Last Save", "last_save_days", "enumber");

	$tbl->SetRows($rows);
	$tbl->EchoTable();
}
theme('', 'footer');

exit;

// -----------------------------------------------------------------------------

function etitle($title, $row) {
    $projectid = $row["projectid"];
    return link_to_project($projectid, $title);
}

function elang($lang) {
	if(strlen($lang) < 4) {
		$lang = LanguageName($lang);
	}
	return $lang;
}

function epm($username) {
    return link_to_pm($username);
}

function enumber($val) {
    return $val;
}

// vim: sw=4 ts=4 expandtab
