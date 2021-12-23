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

/*
if (!$User->MayWorkInRound($phase)) {
    $yes = "<span style='font-size: larger; color:green'>✔</span>";
    $no = "❌";
    $msg = "Some requirements are not yet satisfied, you may not work in this phase.";
    if ($phase == "P2") {
        $r1 = "P1+F1";
        $r1n = 300;
        $r1mine = $User->PageCount();
        $days = 21;
    } else if ($phase == "P3") {
        $r1 = "P1+P2";
        $r1n = 400;
        $r1mine = $User->PageCount();
        $r2 = "P2";
        $r2n = 50;
        $r2mine = $User->RoundPageCount("P2");
        $s2 = ($r2mine >= $r2n) ? $yes : $no;
        $r3 = "F1";
        $r3n = 50;
        $r3mine = $User->RoundPageCount("F1");
        $s3 = ($r3mine >= $r3n) ? $yes : $no;
        $days = 42;
        if ($r1mine >= $r1n && $r2mine >= $r2n && $r3mine >= $r3n &&
            $User->DpAge() >= $days)
            $msg = "You have satisfied all requirements.  To be allowed to work in P3, you must send a PM to IonaV requesting access.";
    } else if ($phase == "F1") {
        $r1 = "P1+P2";
        $r1n = 300;
        $r1mine = $User->PageCount();
        $days = 21;
    } else if ($phase == "F2") {
        $r1 = "F1";
        $r1n = 400;
        $r1mine = $User->RoundPageCount("F1");
        $days = 91;
        if ($r1mine >= $r1n && $User->DpAge() >= $days)
            $msg = "You have satisfied all requirements.  To be allowed to work in P3, you must send a PM to cmspence requesting access.";
    }
    $s1 = ($r1mine >= $r1n) ? $yes : $no;
    $daysOK = ($User->DpAge() >= $days) ? $yes : $no;
    echo "
        <div style='margin: 1em auto;'>
        <p class='center'>Entrance Requirements for working in $phase:</p>
        <table class='dptable'>
            <thead><td>Criterion</td><td>Minimum</td><td>You</td></thead>
            <tr>
                <td class='padded'>'$r1' pages completed</td>
                <td>$r1n</td>
                <td>$r1mine</td>
                <td>$s1</td>
            </tr>
    ";
    if (!empty($r2)) {
        echo "
            <tr>
                <td class='padded'>'$r2' pages completed</td>
                <td>$r2n</td>
                <td>$r2mine</td>
                <td>$s2</td>
            </tr>
        ";
    }
    if (!empty($r3)) {
        echo "
            <tr>
                <td class='padded'>'$r3' pages completed</td>
                <td>$r3n</td>
                <td>$r3mine</td>
                <td>$s3</td>
            </tr>
        ";
    }
    echo "
            <tr>
                <td>Days since registration</td>
                <td>$days</td>
                <td>{$User->DpAge()}</td>
                <td>$daysOK</td>
            </tr>
            <tr>
                <td>proofreading quiz pass</td>
                <td>n/a</td>
                <td>n/a</td>
                <td>n/a</td>
            </tr>
        </table>
        <p class='center'>$msg</p>
        </div>
    ";
}
 */

$rows = getProjects($phase, "ORDER BY days_avail DESC, nameofwork");

$n = count($rows);
echo "<h3 class='center'><span class='dptablefilter_total'>$n</span> projects available in this round; <span class='dptablefilter_count'>$n</span> displayed.</h3>\n";

if($n < 1) {
	echo "<br><h4>No projects found.</h4>";
} else {
    // Move the Begin projects out into their own table.
    $begin = [];
    $i = 0;
    foreach ($rows as $row) {
        if (strpos($row['nameofwork'], '[BEGIN]') !== false
        ||  $row['username'] === 'BEGIN') {
            $begin[] = $row;
            unset($rows[$i]);
        }
        $i += 1;
    }
    $rows = array_values($rows);
    if (!empty($begin))
        echoProjects($begin);

    echoProjects($rows, $class = NULL, $filters = true);
}
theme('', 'footer');

exit;

// vim: sw=4 ts=4 expandtab
