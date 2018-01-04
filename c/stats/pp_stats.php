<?php
$relPath='../pinc/';
include_once($relPath.'dpinit.php');

$stats = $dpdb->SqlOneObject("
    SELECT COUNT(1) nprojects,
           COUNT(DISTINCT postproofer) nproofers
    FROM projects
    WHERE phase IN ('POSTED', 'PPV')");

$rows1 = array(array("nprojects" => $stats->nprojects));
$tbl1 = new DpTable("tbl1", "dptable minitab");
$tbl1->SetRows($rows1);
$tbl1->AddColumn("^Total Projects Post-Processed", "nprojects");

$rows2 = array(array("nproofers" => $stats->nproofers));
$tbl2 = new DpTable("tbl2", "dptable minitab");
$tbl2->SetRows($rows2);
$tbl2->AddColumn("^Number of Post-Processors", "nproofers");

$rows = $dpdb->SqlRows("
    SELECT (CASE WHEN LENGTH(postproofer) > 0 THEN postproofer ELSE 'Unassigned' END) AS PP, 
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
           SUM(CASE WHEN p.phase IN ('P1','P2','P3','F1','F2') and queued IS NULL THEN 1 ELSE 0 END) as nRounds,
           SUM(CASE WHEN p.phase IN ('PPV','POSTED') and queued IS NULL THEN 1 ELSE 0 END) as nDone
    FROM projects p
    LEFT JOIN (
        SELECT projectid, 'Q' queued FROM project_holds WHERE phase = 'P1' and hold_code='queue'
    ) T on p.projectid = T.projectid
    WHERE phase != 'DELETED'
    GROUP BY postproofer
    ORDER BY COUNT(1) DESC");

$npms = count($rows);

$tbl = new DpTable("tblmanaged", "dptable bordered w70 sortable");
$tbl->AddColumn("<Name", "PP");
$tbl->AddColumn(">Total Projects", "nproj");
$tbl->AddColumn(">PREP", "nPREP");
$tbl->AddColumn(">Queued", "nQueued", null, "b-right");
$tbl->AddColumn(">P1", "nP1");
$tbl->AddColumn(">P2", "nP2");
$tbl->AddColumn(">P3", "nP3");
$tbl->AddColumn(">F1", "nF1");
$tbl->AddColumn(">F2", "nF2");
$tbl->AddColumn(">In Rounds", "nRounds", null, "b-left");
$tbl->AddColumn(">PP", "nPP", null, "b-right");
$tbl->AddColumn(">PPV", "nPPV");
$tbl->AddColumn(">Posted", "nPOSTED");
$tbl->AddColumn(">PPV+Posted", "nDone", null, "b-left");
$tbl->SetRows($rows);


$title = "Post-Processing Statistics";
theme($title,'header');

echo "
    <h2 class='center'>$title</h2>
    <h3 class='center'>" . _("Total Projects Post-Processed Since Statistics were Kept") . "</h3>\n";

$tbl1->EchoTable();
$tbl2->EchoTable();

echo _("
    <h2 class='center'>$title</h2>
    <p class='center'>There are $npms Distinct Post-Processors</p>
");

$tbl->EchoTable();

theme("","footer");

// vim: sw=4 ts=4 expandtab
?>
