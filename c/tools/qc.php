<?PHP
/*  qc.php */

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'site_news.inc');

//$User->MayReleaseHold("qc")
//    or die("This user not permitted to release a QC Hold.");

$chk_uncleared  = Arg("chk_uncleared", false);
$chk_pmhold     = Arg("chk_pmhold", false);
$release        = ArgArray("release");
$reject         = ArgArray("reject");
$qc             = ArgArray("qc");
$qcupdate       = ArgArray("qcupdate");
$holdremark     = Arg("txtremark");

$errors = [];

if (count($qcupdate) > 0) {
    foreach ($qcupdate as $projectid => $value) {
        $e = doQCAssign($projectid, $qc[$projectid]);
        if ($e != '')
            $errors[] = $e;
    }
}

// If they failed assignment, don't do any release button they might have
// clicked on at the same time.
if (empty($errors)) {

    if(count($release) > 0) {
        foreach($release as $key => $value) {
            doRelease($key);
        }
    }

    if(count($reject) > 0) {
        foreach($reject as $key => $value) {
            doReject($key, $holdremark);
        }
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

// -----------------------------------------------------------------------------

$no_stats = 1;

theme("QC Hold Release", "header");

echo "<div class='center w90'>";

?>

<script type='text/javascript'>
window.addEventListener('keydown', function(e) {
    if (e.keyIdentifier == 'U+000A' || e.keyIdentifier == 'Enter' || e.keyCode == 13) {
        e.preventDefault();
        return false;
    }
}, true);
</script>

<h1 class='center'>Projects Waiting for QC Hold Release</h1>
<h2 class='center'>After PM Hold Is Released</h2>

<h4 class='center'>What happens in this stage:</h4>

<p>To leave PREP phase and proceed to P1, an project needs to:</p>
<ol>
    <li>have no holds in effect,</li>
    <li>have pages loaded,</li>
    <li>have a Clearance Code.</li>
</ol>

<p>In the normal course of events, there are two Holds created for all new projects
that must be cleared in order for it to leave the PREP phase.
These are the <b>PM Hold</b>, which should be cleared to indicate that the Project Manager approves it for P1,
and the <b>QC Hold</b>, indicating the QC Manager says it's ready.
Normally the QC Manager waits until the PM Hold is released and the other
requirements are met before inspecting the project.</p>

<p>This report is for the QC Manager to use to find projects to inspect.
Checkboxes are provided to indicate whether you also want to see other PREP projects.</p>

<?php
if(!$User->MayReleaseHold("qc")) {
    echo "
        <p>You do <b>not</b> have permissions to release QC holds.
        Showing the projects on QC hold, but not the release buttons.</p>
    ";
}

echo "
<div class='center'>
<form name='frmqc' method='POST'>
Include projects:
     also still on PM Hold <input type='checkbox' name='chk_pmhold' onchange='frmqc.submit()' {$ph_checked}>
     no clearance yet <input type='checkbox' name='chk_uncleared' onchange='frmqc.submit()' {$cl_checked}>
</form>
</div>\n";

if (!empty($errors)) {
    $msg = implode("<br>\n", $errors);
    echo "\n<h1 class='center'>ERRORS OCCURRED</h1>
        <p class='center bold red'>$msg</p>\n";
}

echo_qc_waiting_projects($chk_uncleared, $chk_pmhold);

echo "</div>";

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
                p.qc_assign,
                p.username AS pm,
                LOWER(p.username) AS pmsort,
                LOWER(p.qc_assign) AS qcsort,
                phqc.id phqc_id,
                phpm.id phpm_id,
                p.clearance,
                DATE(FROM_UNIXTIME(pe.event_time)) AS qcdate,
                DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(MAX(pe.event_time)))
                    AS days_in_qc
            FROM projects p
            JOIN project_holds phqc ON p.projectid = phqc.projectid
                AND phqc.phase = 'PREP'
                AND phqc.hold_code = 'qc'
            LEFT JOIN project_holds phpm ON p.projectid = phpm.projectid
                AND phpm.phase = 'PREP'
                AND phpm.hold_code = 'pm'
            LEFT JOIN project_events pe ON p.projectid = pe.projectid
                AND p.phase = pe.phase
                AND pe.event_type IN ('hold', 'set_hold', 'release_hold')
            $where
            GROUP BY p.projectid
            ORDER BY days_in_qc, p.projectid";
    echo html_comment($sql);
    $rows = $dpdb->SqlRows($sql);


    $tbl = new DpTable();
    $tbl->AddColumn("<Genre", "genre", null);
    $tbl->AddColumn("<Language", "language");
    $tbl->AddColumn("^Proj Mgr", "pm", "epm", "sortkey=pmsort");
    $tbl->AddColumn("^QC Assignment", "qc_assign", "eqc", "sortkey=qcsort");
    $tbl->AddColumn("^Mod Date", "qcdate");
    $tbl->AddColumn("<Title", "nameofwork", "etitle");
    $tbl->AddColumn("<Clearance", "clearance", "eclearance");
    $tbl->AddColumn("<PM Hold?", "phpm_id", "epmhold");

    if($User->MayReleaseHold("qc")) {
        $tbl->AddColumn("^Release", "projectid", "erelease");
        $tbl->AddColumn("^Reset PM Hold", "projectid", "ereject");
    }
    $tbl->AddColumn("^Days", "days_in_qc", "edays");
    $tbl->SetRows($rows);

    $n = count($rows);
    echo "<p class='center'>Number of projects listed: $n</p>";

    echo "<form id='frmprop' method='POST' name='frmprop'>\n";

    // A dummy, hidden submit.  This will allow enter when in a QC Assignment
    // text to submit the user.
    //echo "<input type='submit' style='height:0px;width:0px; border:none; padding:0px;' hidefocus='true'>\n";

    echo "<div class='rfloat'>Add remark when resetting PM hold: <input type='text' name='txtremark' id='txtremark' size='40'></div>\n";
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

function doReject($projectid, $holdremark) {
    $p = new DpProject($projectid);
    $phase = $p->Phase();
    $p->SetPMHold($phase, "Reset by QC " . $holdremark);
}

function doQCAssign($projectid, $u) {
    global $User;
    if (!$User->MayQC())
        return "No permissions to set QC Assignment!";
    if (!empty($u)) {
        global $Context;
        if (! $Context->UserExists($u)) {
            return "$u: User does not exist";
        }
    }

    // Note the way the form is setup, all entries will be submitted!
    // Meaning all projects will be loaded.
    // Horribly inefficient, but as long as QC is on the ball, there aren't
    // a lot of rows in the table!
    $p = new DpProject($projectid);
    if ($p->QCAssign() != $u)
        $p->SetQCAssign($u);
    return '';
}

function etitle($title, $row) {
    $title = htmlspecialchars($title);
    $projectid = $row['projectid'];
    return link_to_project($projectid, $title);
}

function eclearance($code) {
    return $code ? "Yes" : "No";
}

function ehold($holdid) {
    return $holdid ? "Yes" : "";
}

function eauthor($authorsname) {
    return htmlspecialchars($authorsname);
}

function epm($pm) {
    return $pm == ""
        ? "<span class='red'>--</span>\n"
        : link_to_pm($pm);
}

function eqc($qc, $row) {
    global $User;
    if (!$User->MayQC())
        return epm($qc);
    $name = htmlspecialchars($qc);
    $projectid = $row['projectid'];
    return "<input name='qc[$projectid]' type='text' title='Set QC Assignment' value='$name' maxlength='25'>
    <input name='qcupdate[$projectid]' type='submit' title='Update QC' value='Update'>\n";
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
    return "<input name='release[$projectid]' type='submit' title='QC OK - Release QC Hold' value='OK'>\n";

}

function ereject($projectid) {
    return "
        <input name='reject[$projectid]' type='submit' title='Reset PM Hold due to QC issues' value='Return'>\n";

}

// vim: sw=4 ts=4 expandtab

