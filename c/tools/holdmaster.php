<?PHP

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');

// -----------------------------------------------------------------------------
// collect GETs, POSTs, etc.
// -----------------------------------------------------------------------------

$projectid = ArgProjectId();
$projectid != ""
    or die("No Project ID.");


// -----------------------------------------------------------------------------
// Execution logic
// -----------------------------------------------------------------------------


// -----------------------------------------------------------------------------
// Data acquisition
// -----------------------------------------------------------------------------

$project = new DpProject($projectid);

$tbldata = array();

foreach($Context->Holds() as $hold) {
    foreach($Context->Phases() as $phase) {
        $tbldata[$hold['hold_code']][$phase['phase']] = "";
    }
}

$sql = "
    SELECT p.sequence, p.phase, ht.hold_code
    FROM phases p
    CROSS JOIN hold_types ht
    WHERE ht.hold_code != 'clearance'

    UNION ALL

    SELECT p.sequence, p.phase, ht.hold_code
    FROM phases p
    CROSS JOIN hold_types ht
    WHERE p.phase = 'PREP'
        AND ht.hold_code = 'clearance'

    ORDER BY sequence, hold_code";

$rows = $dpdb->SqlRows($sql);
$arows = array();
foreach($rows as $row) {
    $seq = $row['sequence'];
    $phase = $row['phase'];
    $code = $row['hold_code'];
    $arows['code'] = array(0 => $code, 1 => $phase);
}
dump($arows);





// -----------------------------------------------------------------------------
// Display preparation
// -----------------------------------------------------------------------------

$tbl = new DpTable("tblholds", "dptable");
$tbl->SetRows($arows);


// onload required for table sorts
$args = array();

$browsertab = "DPC: Project Hold Manager";
$title = "DPC: Manage Project Holds";

theme($browsertab, "header", $args);


echo "
    <p class='ph1'>$title</p>
    <p class='ph2'>{$project->Title()}</p>\n";

$tbl->EchoTable();

theme("", "footer");
exit;

