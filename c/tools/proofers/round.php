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

$rows = getProjects($phase, "ORDER BY days_avail DESC, nameofwork");

$n = count($rows);
echo "<h3 class='center'>$n projects available in this round.</h3>\n";

if($n < 1) {
	echo "<br><h4>No projects found.</h4>";
} else {
    // Move the Begin projects out into their own table.
    $begin = [];
    $i = 0;
    foreach ($rows as $row) {
        if (strpos($row['nameofwork'], '[BEGIN]') !== false) {
            $begin[] = $row;
            unset($rows[$i]);
        }
        $i += 1;
    }
    $rows = array_values($rows);
    if (!empty($begin))
        echoProjects($begin);

    echoProjects($rows);
}
theme('', 'footer');

exit;

// vim: sw=4 ts=4 expandtab
