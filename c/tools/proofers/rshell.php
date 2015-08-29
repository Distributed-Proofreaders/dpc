<?PHP
// Give information about a single round,
// including (most importantly) the list of projects available for work.

$relPath='../../pinc/';
include_once($relPath.'dpinit.php');
include_once($relPath.'site_news.inc');

$roundid = Arg("round_id", "P2");
if (! $roundid) {
    die("round.php invoked without round_id parameter.");
}
$round = get_Round_for_round_id($roundid);
if(! $round) {
    die("round.php invoked with invalid round_id='$roundid'.");
}

$username = "stygiania";
$usermay = $User->MayWorkInRound($roundid);
$pagesproofed = $User->PageCount();

theme( "$round->id: $round->name", 'header' );

$title = "$round->id: $round->name";
echo "<h1 class='center'>$title</h1>\n";

if(! $User->MayWorkInRound($roundid)) {
    echo "<p align='center'>
    " . sprintf( _("Welcome to %s!"), $roundid ) . "
    ". _("Feel free to explore this stage.
    You can find out what happens here, and follow the progress of projects
    from earlier rounds. If you're interested in working in this stage, see
    below to find out how you can qualify.") . "</p>\n";
}

echo "<p>"._('What happens in this stage'). ":<br>$round->description</p>\n";


// What guideline document are we needing?
$round_doc_url = "$code_url/faq/$round->document";

$phase = $round->id;
$rows = $dpdb->SqlRows("
    SELECT  p.projectid,
            p.nameofwork,
            p.authorsname,
            p.language,
            p.genre,
            p.difficulty,
            p.username,
            p.n_pages,
            p.n_available_pages,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(p.modifieddate)) AS days_avail
    FROM projects p
    LEFT JOIN project_holds ph ON p.projectid = ph.projectid
        AND p.phase = ph.phase
    WHERE p.phase = '$phase'
        AND p.state LIKE '%proj_avail'
        AND ph.id IS NULL
    ORDER BY days_avail");

$tbl = new DpTable();
$tbl->AddColumn("<Title", "nameofwork", "etitle");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("<Language", "language");
$tbl->AddColumn("<Genre", "genre");
$tbl->AddColumn("<Project<br>Mgr", "username", "epm");
$tbl->AddColumn("^Available<br>Pages", "n_available_pages");
$tbl->AddColumn("^Total<br>Pages", "n_pages");
$tbl->AddColumn(">Days", "days_avail", "enumber");

$tbl->SetRows($rows);
$tbl->EchoTable();


theme('', 'footer');
exit;
// -----------------------------------------------------------------------------

function etitle($title, $row) {
    $projectid = $row["projectid"];
    return link_to_project($projectid, $title);
}

function epm($username) {
    return link_to_pm($username);
}

function enumber($val) {
    return number_format($val);
}
// vim: sw=4 ts=4 expandtab
?>
