<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'site_news.inc');

// -----------------------------------------------------------------------------

$no_stats = 1;
theme("PREP: Project Preparation", "header");
?>

<div class="w95">
<h2 class='center'>Phase PREP: Project Preparation</h2>

<h4 class='center'>What happens in this stage:</h4>

<p>OCR text and images are loaded, and projects are prepared for release
to P1, the first proofing round.</p>

<p>To advance to P1, projects need to:</p>
<pre>
1. Have a Clearance code assigned.
2. Pages (images and text) need to be loaded.
2. A Project Manager needs to be assigned.
</pre>
<p>They then advance to P1 with a Queueing Hold, which is released by a Queue Manager,
    who considers several factors, such as active projects per author, projects per
    Project Manager, the total number of projects and pages in round, etc.</p>


<p>The following projects are being preprocessed in phase PREP.</p>

<?php
echo_preprocessing_projects();

echo "
</div>";

theme("", "footer");
exit;

function echo_preprocessing_projects() {
    global $dpdb;

    $rows = $dpdb->SqlRows("
        SELECT
            p.projectid,
            phqc.id qc_holdid,
            phpm.id pm_holdid,
            p.nameofwork,
            p.authorsname,
            p.clearance,
            p.genre,
            p.language,
            (SELECT COUNT(1) FROM pages WHERE projectid = p.projectid) n_pages,
            p.username AS pm,
            LOWER(p.username) AS pmsort,
            phqc.id phqc_id,
            phpm.id phpm_id,
            DATE(FROM_UNIXTIME(phase_change_date)) AS moddate,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(createtime)) AS days_in_phase

            FROM projects p

            LEFT JOIN project_holds phqc 
                ON p.projectid = phqc.projectid
                AND phqc.phase = 'PREP'
                AND phqc.hold_code = 'qc'

            LEFT JOIN project_holds phpm 
            ON p.projectid = phpm.projectid
                AND phpm.phase = 'PREP'
                AND phpm.hold_code = 'pm'

            WHERE p.phase = 'PREP'

            ORDER BY p.phase_change_date, p.projectid");


    $tbl = new DpTable("tblprep w100 center");
    $tbl->AddColumn("<Title", "nameofwork", "etitle");
    $tbl->AddColumn("<Author", "authorsname");
    $tbl->AddColumn("^Pages", "n_pages", "enpages");
    $tbl->AddColumn("^Proj Mgr", "pm", "epm", "sortkey=pmsort");
    $tbl->AddColumn("^Clearance", "clearance", "eclearance");
    $tbl->AddColumn("^Days", "days_in_phase");
    $tbl->AddColumn("^PM<br>Hold", "phpm_id", "ehold");
    $tbl->AddColumn("^QC<br>Hold", "phqc_id", "ehold");
//    $tbl->AddColumn("^User<br>Hold", "phu_id", "ehold");
    $tbl->SetRows($rows);

    echo "<form id='frmprop' method='POST' name='frmprop'>\n";
    $tbl->EchoTable();
    echo "</form>\n";
}


function etitle($title, $row) {
    $projectid = $row['projectid'];
    return link_to_project($projectid, $title);
}

function epm($pm) {
    return $pm == ""
        ? "<span class='red'>--</span>\n"
        : link_to_pm($pm);
}

function eclearance($clearance) {
    return $clearance == ""
        ? "<span class='red'>UNCLEARED</span>\n"
        : $clearance;
}

function ehold($is_hold) {
    return $is_hold ? "Yes" : "";
}

function enpages($npages) {
    return $npages > 0
        ? $npages
        : "<span class='red'>0</span>\n";

}

function estatus($is_qc_hold, $row) {
    return
      ($row['clearance'] == ""
        ? "<span class='red'>1</span>"
        : "<span class='green'>o</span>")
      . ($row['n_pages'] > 0
            ? "<span class='green'>o</span>"
            : "<span class='red'>2</span>")
      . ($row['pm'] == ""
            ? "<span class='red'>3</span>"
            : "<span class='green'>o</span>")
      . ($is_qc_hold
            ? "<span class='red'>4</span>"
            : "<span class='green'>o</span>");
}

// vim: sw=4 ts=4 expandtab

