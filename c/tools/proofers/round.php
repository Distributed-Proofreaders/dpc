<?PHP
// Give information about a single round,
// including (most importantly) the list of projects available for work.

$relPath='../../pinc/';
include_once $relPath.'dpinit.php';
include_once $relPath.'site_news.inc';
include_once $relPath.'mentorbanner.inc';

//$roundid = Arg('round_id', Arg('roundid'));
$phase_code = Arg("round_id", Arg("roundid"));

if (!$phase_code) {
    die("round.php invoked without round_id parameter.");
}

$User->IsLoggedIn()
	or RedirectToLogin();


$phase = $Context->GetPhase($phase_code);
//$round = $Context->GetRound($roundid);

//$round = get_Round_for_round_id($roundid);
if(! $phase) {
    die("round.php invoked with invalid round_id='$phase_code'.");
}
$User->IsLoggedIn()
	or die("Invalid attempt to access Round $phase_code");

$username = $User->Username();
$pagesproofed = $User->PageCount();

//if($User->IsNewWindow()) {
//    $newProofWin_js = include($relPath.'js_newwin.inc');
//    $theme_extras = array( 'js_data' => $newProofWin_js );
//}
//else {
    $theme_extras = array();
//}

/** @var Phase $phase */
$caption = $phase->Caption();
theme( "$phase_code: $caption", 'header', $theme_extras );
//theme( "{$round->RoundId()}: {$round->Caption()}", 'header', $theme_extras );

//$title = "{$round->RoundId()}: {$round->Caption()}";
$title = "Round: $caption";
echo "<h1 class='center'>$title</h1>\n";

if(! $User->MayWorkInRound($phase_code)) {
    echo "<p align='center'>
    " . sprintf( _("Welcome to %s!"), $phase_code ) . "
    ". _("Feel free to explore this stage.
    You can find out what happens here, and follow the progress of projects
    from earlier rounds. If you're interested in working in this stage, see
    below to find out how you can qualify.") . "</p>\n";
}

echo "<p>"._('What happens in this stage'). ":<br>{$phase->Description()}</p>\n";

show_news_for_page($phase_code);
//$round_doc_url = "$code_url/faq/$round->document";

//if ($pagesproofed >= 15 && $pagesproofed < 200) {
//    echo "
//        <hr class='w75'>
//        <p>". _("New Proofreaders:")."
//        <a href='$forums_url/viewtopic.php?t=388'>
//        ". _("What did you think of the Mentor feedback you received?")."
//        </a></p>\n";
//}
//
//if ($pagesproofed <= 20 && $User->MayWorkInRound($roundid)) {
//    echo "
//    <hr class='w75'>
//    <p class='mainfont'>
//    ". _("Click on the name of a book in the list below to start proofreading.")."
//    </p>\n";
//}

//$phase = $round->RoundId();
$sql = "
    SELECT  p.projectid,
            p.nameofwork,
            p.authorsname,
            p.language,
            p.genre,
            p.difficulty,
            p.username,
            LOWER(p.username) pmsort,
            SUM(1) n_pages,
            SUM(pv.state = 'A') n_available_pages,
            DATEDIFF(CURRENT_DATE(),
                FROM_UNIXTIME(p.modifieddate)) AS days_avail,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(MAX(pv.version_time))) AS last_save_days
    FROM projects p
    LEFT JOIN page_last_versions pv
        ON p.projectid = pv.projectid
    WHERE p.phase = '$phase_code'
        AND p.projectid NOT IN (
            SELECT projectid FROM project_holds
            WHERE phase = p.phase
        )
    GROUP BY p.projectid
    ORDER BY days_avail";
//echo html_comment($sql);
$rows = $dpdb->SqlRows($sql);

//echo "<td></td> <td></td> <td></td> <td></td> <td></td> <td></td>";
if(count($rows) < 1) {
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
