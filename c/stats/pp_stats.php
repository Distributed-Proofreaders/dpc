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

$rows3 = $dpdb->SqlRows("
    SELECT p.postproofer,
    		u.u_privacy,
           COUNT(1) nprojects
    FROM projects p
    JOIN users u ON p.postproofer = u.username
    WHERE p.phase IN ('POSTED', 'PPV')
    GROUP BY p.postproofer
    ORDER BY COUNT(1) DESC");
$tbl3 = new DpTable("tbl3", "dptable minitab", "Post-Processor Project Counts");
$tbl3->AddColumn("<PPer", "postproofer", "eUsername");
$tbl3->AddColumn("^Projects", "nprojects");
$tbl3->SetRows($rows3);

$rows4 = $dpdb->SqlRows("
    SELECT 	p.postproofer,
    		u.u_privacy,
    		COUNT(1) nprojects
    FROM projects p
    JOIN users u ON p.postproofer = u.username
    WHERE phase = 'POSTED'
    GROUP BY p.postproofer
    ORDER BY COUNT(1) DESC");
$tbl4 = new DpTable("tbl4", "dptable minitab", "Post-Processor Project Counts");
$tbl4->AddColumn("<PPer", "postproofer", "eUsername");
$tbl4->AddColumn(">Projects", "nprojects");
$tbl4->SetRows($rows4);

$title = "Post-Processing Statistics";
theme($title,'header');

echo "
    <h2 class='center'>$title</h2>
    ".link_to_pper_charts()."

    <h3 class='center'>" . _("Total Projects Post-Processed Since Statistics were Kept") . "</h3>\n";
    $tbl1->EchoTable();

    $tbl2->EchoTable();

echo "
    <h3 class='center'>" . _("Most Prolific Post-Processors") . "</h3>
    <h4 class='center'>" . _("(Number of Projects Finished PPing)") . "</h4>\n";

$tbl3->EchoTable();


echo "
    <h3 class='center'>" . _("Most Prolific Post-Processors") . "</h3>
    <h4 class='center'>" . _("(Number of Projects Posted)") . "</h4>\n";

$tbl4->EchoTable();

theme("","footer");
exit;

function link_to_pper_charts() {
    return "";
}

function eUsername($name, $row) {
	global $User;
	return ($row['u_privacy'] > 0)
						? ($User->IsAdmin() ? "{$name}*" : "anonymous")
						: $name;
}
?>
