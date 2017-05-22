<?

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');

echo "<h1>PP Pool Difficulty Info</h1>\n";

$descr = "Counts of artifacts in projects available to PP";
$sql = "SELECT projectid
        FROM projects
        WHERE phase = 'PP'
            AND IFNULL(postproofer, '') = ''";

$projects = array();
foreach($dpdb->SqlValues($sql) as $projectid) {
	$projects[] = new DpProject($projectid);
}

$rows = array();
foreach($projects as $project) {
	$rows[] = analyze($project);
}

$tbl = new DpTable();
$tbl->SetRows($rows);
$tbl->EchoTable();


// ----------------------


/** @var DpProject $project */
function analyze($project) {
	$ary = array();
	$ary['projectid'] = $project->ProjectId();
	$ary['title'] = $project->NameOfWork();
	$text = $project->ActiveText();
	$ary['footnotes'] = RegexCount("\[Footnote", "ui", $text);
	$ary['sidenotes'] = RegexCount("\[Sidenote", "ui", $text);
	$ary['illos'] = RegexCount("\[Illustration", "ui", $text);
	$ary['nowrap'] = RegexCount("\n/\*\n", "uis", $text);
	$ary['blkqt'] = RegexCount("\n/#\n", "uis", $text);
	$ary['quotes'] = RegexCount('"', "ui", $text) / 2;
	$ary['spacey'] = RegexCount('\s"\s', "uis", $text);
	$ary['drama'] = RegexCount("\n\s*<i>\w+</i>\s*\w", "ui", $text);
	$ary['**Notes'] = RegexCount("\[\*\*", "ui", $text);



	return $ary;
}
