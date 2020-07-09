<?PHP
// Give information about a single round,
// including (most importantly) the list of projects available for work.
// Most of the work is in round.inc

$relPath='../../pinc/';
include_once $relPath.'dpinit.php';
include_once $relPath.'round.inc';

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

show_news_for_page($phase);

$title = "Round: $caption";
echo "<h1 class='center'>$title</h1>\n";

$rows = getProjects($phase);

$n = count($rows);
echo "<h3 class='center'>$n projects available in this round.</h3>\n";

if($n < 1) {
	echo "<br><h4>No projects found.</h4>";
} else {
    echoProjects($rows);
}
theme('', 'footer');

exit;

// vim: sw=4 ts=4 expandtab
