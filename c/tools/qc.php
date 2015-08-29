<?PHP
/*  qc.php */

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'site_news.inc');

$User->MayReleaseHold("qc")
    or die("User not permitted here.");

$chk_uncleared  = IsArg("chk_uncleared", true);
$chk_pmhold     = IsArg("chk_pmhold", true);
$release        = ArgArray("release");
$reject         = ArgArray("release");

if(count($release) > 0) {
    foreach($release as $key => $value) {
        doRelease($key);
    }
}

if(count($reject) > 0) {
    foreach($reject as $key => $value) {
        doReject($key);
    }
}

$cl_checked = $chk_uncleared ? "checked" : "";
$ph_checked = $chk_pmhold ? "checked" : "";

// ----------------------------------------------------------------------------

// ordinarily, want projects not on PM hold and clearance not null.
// ordinarily, "where holdid is null" and "clearance is not null"
$where = "";

if(! $chk_uncleared) {
    $where .= "\nAND clearance IS NOT NULL";
}
if(! $chk_pmhold) {
    $where .="\nAND phpm.id IS NULL";
}
$projectids = $dpdb->SqlValues("
SELECT DISTINCT p.projectid ,
		phpm.id pmhold_id,
		p.clearance
FROM projects p
JOIN project_holds ph
ON p.projectid = ph.projectid
    AND ph.hold_code = 'qc'
    AND ph.phase = p.phase
LEFT JOIN project_holds phpm
ON p.projectid = phpm.projectid
    AND phpm.hold_code = 'pm'
    AND phpm.phase = p.phase
WHERE p.phase = 'PREP'
    $where
    ORDER BY projectid");

// -----------------------------------------------------------------------------

theme("QC Hold Release", "header");
?>

<h1 class='center'>Projects Waiting for QC Hold Release</h1>
<h2 class='center'>After PM Hold Is Released</h2>

<h4 class='center'>What happens in this stage:</h4>

<p>To leave PREP phase and proceed to P1, an project needs to:</p>
<ol>
    <li>nave no holds in effect,</li>
    <li>have pages loaded,</li>
    <li>have a Clearance Code.</li>

</ol>

<p>There are two Holds created for all new projects that must be cleared.
These are the <b>PM Hold</b>, indicating the Project Manager approves it for P1,
and the <b>QC Hold</b>, indicating the Quality Code inpector says it's ready.
Normally the QC Manager waits until the PM Hold is released and the other
requirements are met before inspecting the project.</p>

<p>This report serves is for the QC Manager to use to find projects to inspect.
Checkboxes are provided to indicate whether you want to see other PREP projects in the list.</p>

<?php

echo "
<div class='center'>
<form name='frmqc' method='POST' action=''>
Include projects:
     on PM Hold <input type='checkbox' name='chk_pmhold' onchange='frmqc.submit()' {$ph_checked}>
     not cleared yet <input type='checkbox' name='chk_uncleared' onchange='frmqc.submit()' {$cl_checked}>
</form>
</div>\n";

echo_qc_waiting_projects($chk_uncleared, $chk_pmhold);

theme("", "footer");
exit;

function echo_qc_waiting_projects($excl_clearance, $excl_pm) {
    global $dpdb, $User;

    $where = "WHERE p.phase = 'PREP'";
    if(! $excl_clearance) {
        $where .= "\nAND NOT p.clearance IS NULL";
    }
    if(! $excl_pm) {
        $where .= "\nAND phpm.id iS NULL";
    }

    $sql = "
            SELECT
                p.projectid,
                phqc.id qc_holdid,
                p.nameofwork,
                p.authorsname,
                p.genre,
                p.language,
                p.n_pages,
                p.username AS pm,
                LOWER(p.username) AS pmsort,
                phqc.id phqc_id,
                phpm.id phpm_id,
                DATE(FROM_UNIXTIME(phase_change_date)) AS moddate,
                DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(phase_change_date))
                AS days_in_phase
            FROM projects p
            JOIN project_holds phqc ON p.projectid = phqc.projectid
                AND phqc.phase = 'PREP'
                AND phqc.hold_code = 'qc'
            LEFT JOIN project_holds phpm ON p.projectid = phpm.projectid
                AND phpm.phase = 'PREP'
                AND phpm.hold_code = 'pm'
            $where
            ORDER BY p.phase_change_date, p.projectid";
    echo html_comment($sql);
    $rows = $dpdb->SqlRows($sql);

    /*
    foreach($projectids as $projectid) {
        $proj = new DpProject($projectid);
        $proj->RecalcPageCounts();
    }
    */


/*
    foreach($rows as $row) {
        $projectid = $row['projectid'];
        $p = new DpProject($projectid);
        $p->RecalcPageCounts();
    }
*/

    $tbl = new DpTable();
    $tbl->AddColumn("<Genre", "genre", null);
    $tbl->AddColumn("<Language", "language");
    $tbl->AddColumn("^Proj Mgr", "pm", "epm", "sortkey=pmsort");
    $tbl->AddColumn("^Mod Date", "moddate");
    $tbl->AddColumn("<Title", "nameofwork", "etitle");
    $tbl->AddColumn("<Clearance", "clearance", "eclearance");
    $tbl->AddColumn("<PM Hold?", "phpm_id", "epmhold");
    // $tbl->AddColumn("<User<br>Hold?", "phu_id", "ehold");
    if($User->MayReleaseHold("queue")) {
        $tbl->AddColumn("^Release", "projectid", "erelease");
        $tbl->AddColumn("^Needs Work", "projectid", "ereject");
    }
    $tbl->AddColumn("^Days", "days_in_phase", "edays");
    $tbl->SetRows($rows);

    $n = count($rows);
    echo "<p class='center'>Number of projects listed: $n</p>";

    echo "<form id='frmprop' action='' method='POST' name='frmprop'>\n";
    $tbl->EchoTableNumbered();
    echo "</form>\n";
}

function edays($n) {
    return $n ? $n : "0";
}

function doRelease($projectid) {
    $p = new DpProject($projectid);
    $p->ReleaseQCHold();
}

function doReject($projectid) {
    $p = new DpProject($projectid);
    $p->SetPMHold("PREP", "release to indicate project is ready for QC");
}

function etitle($title, $row) {
    $title = htmlspecialchars(maybe_convert($title));
    $projectid = $row['projectid'];
    return link_to_project($projectid, $title);
}

function eclearance($code) {
    return $code ? "Yes" : "";
}

function ehold($holdid) {
    return $holdid ? "Yes" : "";
}

function eauthor($authorsname) {
    return htmlspecialchars(maybe_convert($authorsname));
}

function epm($pm) {
    return $pm == ""
        ? "<span class='red'>--</span>\n"
        : link_to_pm($pm);
}

function epmhold($id) {
    return $id ? "Yes" : "";
}

function enpages($npages) {
    return $npages > 0
        ? $npages
        : "<span class='red'>0</span>\n";

}

function erelease($projectid) {
    return "<input name='release[$projectid]' type='submit' value='OK'>\n";

}

function ereject($projectid) {
    return "
        <input name='reject[$projectid]' type='submit' value='Return'>\n";

}

// vim: sw=4 ts=4 expandtab

