<?php
$relPath='../pinc/';
include_once($relPath.'dpinit.php');
include_once($relPath.'project_states.inc');

// TODO: move this to dp.css
echo "
<head>
<style>
table.dptable th.b-right, table.dptable tr.even td.b-right, table.dptable tr.odd td.b-right {
    border-right: black solid 2px;
}
table.dptable th.b-left, table.dptable tr.even td.b-left, table.dptable tr.odd td.b-left {
    border-left: black solid 2px;
}
</style>
</head>
";

$title = _("Project Manager Statistics");
$psd = get_project_status_descriptor('created');
$psd = get_project_status_descriptor('posted');

$rows = $dpdb->SqlRows("
    SELECT username PM, 
           COUNT(1) nproj,
           SUM(CASE WHEN p.phase = 'PREP' THEN 1 ELSE 0 END) as nPREP,
           SUM(CASE WHEN p.phase = 'P1' AND queued = 'Q' THEN 1 ELSE 0 END) as nQueued,
           SUM(CASE WHEN p.phase = 'P1' AND queued IS NULL THEN 1 ELSE 0 END) as nP1,
           SUM(CASE WHEN p.phase = 'P2' THEN 1 ELSE 0 END) as nP2,
           SUM(CASE WHEN p.phase = 'P3' THEN 1 ELSE 0 END) as nP3,
           SUM(CASE WHEN p.phase = 'F1' THEN 1 ELSE 0 END) as nF1,
           SUM(CASE WHEN p.phase = 'F2' THEN 1 ELSE 0 END) as nF2,
           SUM(CASE WHEN p.phase = 'PP' THEN 1 ELSE 0 END) as nPP,
           SUM(CASE WHEN p.phase = 'PPV' THEN 1 ELSE 0 END) as nPPV,
           SUM(CASE WHEN p.phase = 'POSTED' THEN 1 ELSE 0 END) as nPOSTED,
           SUM(CASE WHEN p.phase IN ('P1','P2','P3','F1','F2') and queued IS NULL THEN 1 ELSE 0 END) as nRounds
    FROM projects p
    LEFT JOIN (
        SELECT projectid, 'Q' queued FROM project_holds WHERE phase = 'P1' and hold_code='queue'
    ) T on p.projectid = T.projectid
    WHERE phase != 'DELETED'
    GROUP BY username
    ORDER BY COUNT(1) DESC");

$npms = count($rows);

$tbl = new DpTable("tblmanaged", "dptable bordered w70 sortable");
$tbl->AddColumn("<Name", "PM");
$tbl->AddColumn(">Projects Managed", "nproj");
$tbl->AddColumn(">PREP", "nPREP");
$tbl->AddColumn(">Queued", "nQueued", null, "b-right");
$tbl->AddColumn(">P1", "nP1");
$tbl->AddColumn(">P2", "nP2");
$tbl->AddColumn(">P3", "nP3");
$tbl->AddColumn(">F1", "nF1");
$tbl->AddColumn(">F2", "nF2");
$tbl->AddColumn(">In Rounds", "nRounds", null, "b-left");
$tbl->AddColumn(">PP", "nPP");
$tbl->AddColumn(">PPV", "nPPV");
$tbl->AddColumn(">Posted", "nPOSTED");
$tbl->SetRows($rows);

theme($title,'header');

echo _("
    <h2 class='center'>$title</h2>
    <p class='center'>There are $npms Distinct Project Managers</p>
");

$tbl->EchoTableNumbered();

theme("","footer");
?>
