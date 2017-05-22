<?php
$relPath='../pinc/';
include_once($relPath.'dpinit.php');

$user_can_see_queue_settings = $User->IsAdmin();

$round_id = Arg("round_id");

if(! $round_id) {
	$title = _("Release Queues");
	theme($title, 'header');
	echo "<br><h2>$title</h2>";

	echo _("<p>The old DP Round Queuing system is gone. For now, only P1
            has a queue (fed manually); projects are immediately
            available in other rounds.</p>
            
    <h2>Projects in the P1 Queue</h2>\n");

    $rows = $dpdb->SqlRows("
        SELECT p.projectid, 
               p.nameofwork, 
               p.authorsname, 
               p.genre,
               p.difficulty,
               p.language,
               FROM_UNIXTIME(p.modifieddate) modifieddate,
               DATEDIFF(CURRENT_DATE, FROM_UNIXTIME(p.phase_change_date)) days_ago,
               p.username,
               COUNT(1) holdcount
        FROM projects p
        JOIN project_holds ph ON p.projectid = ph.projectid AND ph.phase = 'P1'
        WHERE p.phase = 'P1'
        GROUP BY p.projectid
        ORDER BY p.phase_change_date ASC");

    $tbl = new DpTable();
    $tbl->AddColumn("<Title", "nameofwork", "etitle");
    $tbl->AddColumn("<Author", "authorsname");
    $tbl->AddColumn("<Genre", "genre");
    $tbl->AddColumn("<Language", "language");
    $tbl->AddColumn("<PM", "username");
    // $tbl->AddColumn(">Holds", "holdcount");
    $tbl->AddColumn(">Days", "days_ago");
    $tbl->SetRows($rows);
    $tbl->EchoTableNumbered();

	theme("", "footer");
	return;
}

echo "<br>\n";
theme("", "footer");
exit;

function etitle($title, $row) {
    $projectid = $row['projectid'];
    return link_to_project($projectid, $title);
}
?>
