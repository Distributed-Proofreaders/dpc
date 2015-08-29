<?PHP

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'gettext_setup.inc');
include_once($relPath.'theme.inc');

$months = Arg("months", "0");

$username   = $User->Username();

$rows = $dpdb->SqlRows("
    SELECT  DATE(FROM_UNIXTIME(pe.timestamp)) posttime,
            DATE(FROM_UNIXTIME(p.modifieddate)) modtime,
            pe.projectid,
            p.nameofwork title
    FROM project_events pe
    JOIN projects p ON pe.projectid = p.projectid
    WHERE pe.event_type = 'post'
    ORDER BY posttime DESC");

$tbl = new DpTable();
$tbl->SetRows($rows);


$no_stats = 1;
theme("Posted Projects", "header");
echo "<div class='lfloat w75'>\n";
$tbl->EchoTableNumbered();
echo "
</div>

<div class='lfloat w25'>\n";
show_completed_projects();

echo "
</div>\n";

theme("", "footer");
exit;

