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
SELECT projectid,
    DATE_FORMAT(FROM_UNIXTIME(event_time), '%Y-%m-%d %H:%i:%s') ts,
    pagename, version, event_type, username, phase
FROM page_events
WHERE projectid = ?";

$args = array(&$projectid);

$rows = $dpdb->SqlRowsPS($sql, $args);

$tbl = new DpTable();
$tbl->AddColumn("<Page", "pagename", "ePage");
$tbl->AddColumn("^Round", "phase");
$tbl->AddColumn("^Version", "version");
$tbl->AddColumn("<User", "username");
$tbl->AddColumn("<Start", "ts");
$tbl->AddColumn("<Type", "event_type");

$tbl->SetRows($rows);

$no_stats = true;
theme("Page Event Trace", "header");

echo "<h2>Page Event Trace for $project_title</h2>\n";
$tbl->EchoTable();

theme("", "footer");
exit;

function ePage($pagename, $row) {
    $projectid = $row["projectid"];
    return link_to_page_image($projectid, $pagename);
}
