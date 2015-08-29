<?PHP
// dancer.php
// dance the project through the phases
// <div><input type='submit' name='smt_phase' value='from phase'/></div>

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
$sbmtP1 = IsArg("sbmtP1");
$sbmtP2 = IsArg("sbmtP2");
$sbmtP3 = IsArg("sbmtP3");
$sbmtF1 = IsArg("sbmtF1");
$sbmtF2 = IsArg("sbmtF2");
$sbmtPP = IsArg("sbmtPP");
$sbmtPPV = IsArg("sbmtPPV");


// -----------------------------------------------------------------------------
// Execution logic
// -----------------------------------------------------------------------------


// -----------------------------------------------------------------------------
// Data acquisition
// -----------------------------------------------------------------------------

$project = new DpProject($projectid);


// -----------------------------------------------------------------------------
// Display
// -----------------------------------------------------------------------------

echo "<!DOCTYPE HTML>
<head>
<title>Dancer</title>
<script type='text/javascript'>
</script>
<style type='text/css'>
</style>
</head>
<body>\n";

dump($Context->Phases());
foreach($Context->Phases() as $phase) {
    $p = $phase['phase'];
    echo "<h1>$p</h1>\n";
    echo "<div><input type='submit' name='sbmt{$p}' value='from $p'/></div>\n";
}

echo "</body></html>";
exit;


