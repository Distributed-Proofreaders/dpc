<?
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once $relPath.'dpinit.php';

$phase = "P3";
$username = $User->Username();

// projects never proofed by user

// projects proofed by user

$user_proj = $dpdb->SqlRows("
    SELECT p.nameofwork,
           p.authorsname,
           qp.co_time,
           DATEDIFF(qp.co_time, CURRENT_DATE()) co_ago,
           qp.ci_time,
           qpp.state,
           SUM(CASE WHEN qpp.state LIKE 'C%' THEN 1 ELSE 0 END) completed_pages,
           count(1) page_count
    FROM projects p
    JOIN qprojects qp
      ON p.projectid = qp.projectid
      AND qp.phase = '$phase'
      AND qp.username = '$username'
      AND qp.status = 'O'
    JOIN qpages qpp
      ON qp.projectid = qpp.projectid
        AND qp.phase = qpp.phase
        AND qp.username = qpp.username
    GROUP BY qp.projectid, qp.phase, qp.username, qpp.state
    ORDER BY qp.projectid, p.nameofwork
");

$sql = "
    SELECT p.nameofwork,
           p.authorsname,
           qp.id,
           count(1) page_count
    FROM projects p
    JOIN qprojects qp
      ON p.projectid = qp.projectid
        AND qp.phase = '$phase'
        AND qp.username IS NULL
    LEFT JOIN qprojects qp1
      ON p.projectid = qp1.projectid
        AND qp1.phase = '$phase'
        AND qp1.username = '$username'
    JOIN qpages qpp
      ON qp.projectid = qpp.projectid
        AND qp.phase = qpp.phase
        AND qpp.username IS NULL
    WHERE qp1.id IS NULL
    GROUP BY p.projectid
    ORDER BY p.nameofwork";
$avail_proj = $dpdb->SqlRows($sql);


$tbl1 = new DpTable("tblq2", "dptable center w90", "Your $phase Qual Projects");
$tbl1->AddColumn("<Title", "nameofwork");
$tbl1->AddColumn("<Author", "authorsname");
$tbl1->AddColumn(">Pages", "page_count");
$tbl1->AddColumn(">Completed", "completed_pages", "eNumber");
$tbl1->AddColumn(">Checked Out", "co_time", "eCo");
$tbl1->SetRows($user_proj);

$tbl2 = new DpTable("tblq1", "dptable center w90", "$phase Qualification Projects Available");
$tbl2->AddColumn("<Title", "nameofwork");
$tbl2->AddColumn("<Author", "authorsname");
$tbl2->AddColumn(">Pages", "page_count");
$tbl2->AddColumn("^", "id", "eCheckout");
$tbl2->SetRows($avail_proj);



theme("$phase Quals Candidate Page", "header");

echo "<form name='frmquals' method='POST'>\n";

$tbl1->EchoTable();
$tbl2->EchoTable();

echo "</form>\n";


theme("", "footer");

exit;

function eNumber($val) {
    return number_format($val);
}

function eCheckout($id) {
    return "<input type='button' name='checkout_$id' value='Check out' />\n";
}

function eCo($cotime, $row) {
    $ago = $row["co_ago"];
    return "$cotime ($ago days ago)\n";
}
