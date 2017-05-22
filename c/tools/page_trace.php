<?PHP

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');

$User->IsLoggedIn()
or RedirectToLogin();

$User->IsSiteManager() || $User->IsProjectFacilitator() || $User->IsProjectManager()
or die("Unauthorized");

$projectid = Arg("projectid");
$projectid != ""
    or die("No project identified");


$Context->IsProjectId($projectid)
    or die("No such project id - $projectid");

$project = new DpProject($projectid);
$project_title = $project->Title();

if(! $project->UserMayManage()) {
    say("Not your project");
    theme("", "footer");
    exit;
}

$title = $project->NameOfWork();

$sql = "
SELECT  pp.projectid,
        pp.pagename,
        pe1.username,
        pe1.phase,
        FROM_UNIXTIME(MIN(pe1.event_time)) time_start,
        FROM_UNIXTIME(MAX(pe2.event_time)) time_end,
        MAX(pe2.event_time) - MIN(pe1.event_time) sec_diff,
        ROUND((MAX(pe2.event_time) - MIN(pe1.event_time)) / 60) minute_diff,
        (MAX(pe2.event_time) - MIN(pe1.event_time)) % 60 sec_remain

FROM pages pp

LEFT JOIN page_events pe1
ON pp.projectid = pe1.projectid
    AND pp.pagename = pe1.pagename
    AND pe1.event_type = 'checkout'

LEFT JOIN page_events pe2
ON pp.projectid = pe2.projectid
    AND pp.pagename = pe2.pagename
    AND pe2.phase = pe1.phase
    AND pe2.username = pe1.username
    AND pe2.event_type IN ('saveAsDone', 'returnToRound')

LEFT JOIN phases p
ON pe1.phase = p.phase

WHERE pp.projectid = ?
GROUP BY pp.pagename, pe1.phase, pe1.username
ORDER BY p.sequence, pp.pagename, MIN(pe1.event_time)";

$args = array(&$projectid);

$rows = $dpdb->SqlRowsPS($sql, $args);

$tbl = new DpTable();
$tbl->AddColumn("<Page", "pagename", "ePage");
$tbl->AddColumn("^Round", "phase");
$tbl->AddColumn("<User", "username");
$tbl->AddColumn("<Start", "time_start");
$tbl->AddColumn("^mn sec", null, "eMinSec");

$tbl->SetRows($rows);

$no_stats = true;
theme("Page Trace", "header");

echo "<h2>$project_title</h2>\n";
$tbl->EchoTable();

theme("", "footer");
exit;

function ePage($pagename, $row) {
    $projectid = $row["projectid"];
    return link_to_page_image($projectid, $pagename);
}
function eMinSec($row) {
    $minutes = $row['minute_diff'];
    $seconds = $row['sec_remain'];
    return "{$minutes} m {$seconds} s";
}





