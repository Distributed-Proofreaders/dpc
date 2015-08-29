<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'site_news.inc');

$release = ArgArray("release");

//if(count($release) > 0) {
//    foreach($release as $key => $value) {
//        $project = new DpProject($key);
//        $project->AdvanceFromPrep();
//    }
//}

// -----------------------------------------------------------------------------

$no_stats = 1;
theme("PREP: Project Preparation", "header");
?>

<h2 class='red center'>Warning: Under Construction</h2>

<h4 class='center'>What happens in this stage:</h4>

<p>After OCR text and images have been loaded, projects are prepared for release
to P1, the first proofing round.</p>

<?php
show_news_for_page("PREP");
?>

<p>To advance to P1, projects need to:</p>
<pre>
1. Have a Clearance code assigned by Simon.
2. Have pages loaded.
2. Have a PM assigned.
4. Clear DAvid's QC Inspection hold.
</pre>
<p>They then advance to P1 with a queueing hold, which is released when
they qualify for release (i.e. projects per author, projects per PM, 
total projects and pages in round, etc.</p>


<h2>Books Being Preprocessed Before P1</h2>

<?php
echo_preprocessing_projects();

theme("", "footer");
exit;

function echo_preprocessing_projects() {
    global $dpdb;

    $rows = $dpdb->SqlRows("
        SELECT
            p.projectid,
            phqc.id qc_holdid,
            phc.id c_holdid,
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
            phc.id phc_id,
            DATE(FROM_UNIXTIME(phase_change_date)) AS moddate,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(phase_change_date)) AS days_in_phase
            FROM projects p
            LEFT JOIN project_holds phqc 
            ON p.projectid = phqc.projectid
                AND phqc.phase = 'PREP'
                AND phqc.hold_code = 'qc'
            LEFT JOIN project_holds phpm 
            ON p.projectid = phpm.projectid
                AND phpm.phase = 'PREP'
                AND phpm.hold_code = 'pm'
            LEFT JOIN project_holds phc 
            ON p.projectid = phc.projectid
                AND phc.phase = 'PREP'
                AND phc.hold_code = 'clearance'
            WHERE p.phase = 'PREP'
            ORDER BY p.phase_change_date, p.projectid");


    $tbl = new DpTable("tblprep");
    $tbl->AddColumn("<Title", "nameofwork", "etitle");
    $tbl->AddColumn("<Author", "authorsname");
    $tbl->AddColumn("^Pages", "n_pages", "enpages");
    $tbl->AddColumn("^Proj Mgr", "pm", "epm", "sortkey=pmsort");
    $tbl->AddColumn("^Clearance", "clearance", "eclearance");
    $tbl->AddColumn("^Days", "days_in_phase");
    $tbl->AddColumn("^Clearance<br>Hold", "phc_id", "ehold");
    $tbl->AddColumn("^PM<br>Hold", "phpm_id", "ehold");
    $tbl->AddColumn("^QC<br>Hold", "phqc_id", "ehold");
//    $tbl->AddColumn("^User<br>Hold", "phu_id", "ehold");
    $tbl->SetRows($rows);

    echo "<form id='frmprop' method='POST' name='frmprop'>\n";
    $tbl->EchoTable();
    echo "</form>\n";
}


function etitle($title, $row) {
    // $title = maybe_convert($title);
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
        ? "<span class='red'>--</span>\n"
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

