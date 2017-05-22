<?php
$relPath='../pinc/';
include_once($relPath.'dpinit.php');
include_once($relPath.'project_states.inc');

$title = _("Project Manager Statistics");
$psd = get_project_status_descriptor('created');
$psd = get_project_status_descriptor('posted');

$rows = $dpdb->SqlRows("
    SELECT username PM, 
           COUNT(1) nproj 
    FROM projects
    WHERE phase != 'DELETED'
    GROUP BY username
    ORDER BY COUNT(1) DESC");

$npms = count($rows);

$tbl = new DpTable("tblmanaged", "dptable w50");
$tbl->AddColumn("<Name", "PM");
$tbl->AddColumn(">Projects Managed", "nproj");
$tbl->SetRows($rows);

$rows2 = $dpdb->SqlRows("
    SELECT username PM, 
           COUNT(1) nproj 
    FROM projects
    WHERE phase = 'POSTED'
    GROUP BY username
    ORDER BY COUNT(1) DESC");

$tbl2 = new DpTable("tblposted", "dptable w50");
$tbl2->AddColumn("<Name", "PM");
$tbl2->AddColumn(">Projects Posted", "nproj");
$tbl2->SetRows($rows2);




theme($title,'header');

echo _(
"<h2 class='center'>$title</h2>
<p class='center'>There are $npms Distinct Project Managers</p>
<h3 class='center'>Most Prolific Project Managers</h3>
<h4 class='center'>(Number of Projects Created)</h4>\n");

$tbl->EchoTableNumbered();

echo _("
    <h3 class='center'>Most Prolific Project Managers</h3>
    <h4 class='center'>(Number of Projects Posted)</h4>\n");

$tbl2->EchoTableNumbered();

theme("","footer");
?>
