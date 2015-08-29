<?PHP
$relPath='./pinc/';
include_once($relPath.'dpinit.inc');
include_once($relPath.'dpsql.inc');
include_once($relPath.'theme.inc');
$no_stats = 1;

$title = _("Task List");
theme($title, "header");

echo "<br><h2>$title</h2>\n";
echo _("All DP-CA code development is being done through DP-INT.  Please visit the DP-INT task list.")."<br><br>";

echo _("Canadian specific issues should be discussed in the DP-CA forums or PM DPCanada.  Thank you.")."<br><br>";

echo "<br>\n";
theme("","footer");
?>
