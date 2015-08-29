<?PHP

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');

$User->IsSiteManager() || $User->IsProjectFacilitator() || $User->IsProjectManager()
    or die("Unauthorized");

$projectid = trim(Arg("projectid"));
$postednum = trim(Arg("postednum"));

if($projectid == "") {
    html_start();
    echo "
    <form target='' name='frmflow' method='POST'>
    <div>
    Project ID: <input type='text' name='projectid' value='$projectid' size='30'><input tabindex='1' type='submit'>
    </div>\n";
    html_end();
    exit;
}

$project = new DpProject($projectid);
$project->UserMayManage()
    or die("Not your project");

$isPPUploadFile = file_exists($project->PPUploadPath()) ? "Yes" : "No";
$isPPVUploadFile = file_exists($project->PPVUploadPath()) ? "Yes" : "No";
$p1_disabled = ($project->Phase() == "P1" ? "" : " disabled");
$p2_disabled = ($project->Phase() == "P2" ? "" : " disabled");
$p3_disabled = ($project->Phase() == "P3" ? "" : " disabled");
$f1_disabled = ($project->Phase() == "F1" ? "" : " disabled");
$f2_disabled = ($project->Phase() == "F2" ? "" : " disabled");
$pp_disabled = ($project->Phase() == "PP" ? "" : " disabled");
$ppv_disabled = ($project->Phase() == "PPV" ? "" : " disabled");
$posted_disabled = ($project->Phase() == "POSTED" ? "" : " disabled");
$submit_test_advance    = IsArg("submit_test_advance");
$submit_advance         = IsArg("submit_advance");
$submit_pp_uncheckout   = IsArg("submit_ppv_uncheckout");
$submit_PP_complete     = IsArg("submit_pp_complete");
$submit_ppv_uncheckout  = IsArg("submit_ppv_uncheckout");
$submit_ppv_checkout    = IsArg("submit_ppv_checkout");
$submit_ppv_complete    = IsArg("submit_ppv_complete");
$submit_ppv_post        = IsArg("submit_ppv_complete");
$submit_reverts         = ArgArray("submit_revert");
$submit_adjust_pages    = ArgArray("submit_adjust_pages");
$set_holds              = ArgArray("set_hold");
$release_holds          = ArgArray("release_hold");
$set_qc_hold            = IsArg("set_qc_hold");
$release_qc_hold        = IsArg("release_qc_hold");

$proj_link = link_to_project($projectid, "Back to project page");
$p1_link   = link_to_round("P1", "link");
$p2_link   = link_to_round("P2", "link");
$p3_link   = link_to_round("P3", "link");
$f1_link   = link_to_round("F1", "link");
$f2_link   = link_to_round("F2", "link");


// dump($_REQUEST);

// -­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­

if($submit_test_advance) {
    $msgs = $project->AdvanceValidateErrors();
}

if($submit_advance) {
    $msgs = $project->MaybeAdvanceRound();
}

if($set_qc_hold) {
    $project->SetQCHold();
}
if($release_qc_hold) {
    $project->ReleaseQCHold();
}
if(count($submit_reverts) > 0) {
    foreach($submit_reverts AS $key => $value) {
        assert($key == $project->Phase());
        $project->RevertPhase();
        break;
    }
}
if(count($set_holds) > 0) {
    foreach($set_holds AS $key => $value) {
        $project->SetUserHold($key);
    }
}
if(count($release_holds) > 0) {
    foreach($release_holds AS $key => $value) {
        $project->ReleaseHoldId($key);
    }
}
if($submit_PP_complete) {
    $project->PPSetComplete();
}
if($submit_pp_uncheckout) {
    $project->PPUncheckout();
}
if($submit_ppv_checkout) {
    $project->PPCheckout();
}

if($submit_ppv_uncheckout) {
    $project->PPVUncheckout();
}
if($submit_ppv_complete) {
    $project->PPVSetComplete();
}
if($submit_ppv_post) {
    $project->SetPosted($postednum);
}

if($submit_adjust_pages) {
    AdjustPhasePages($project);
}

$holds = $project->Holds();
$holdcount = count($holds);
$holdstr = "";
$nactiveholds = 0;
foreach($holds as $hold) {
    /** @var DpHold $hold */
    if($hold->Phase() == $project->Phase()) {
        $nactiveholds++;
    }
    /** @var DpHold $hold */
    $holdstr .= ("        " . $hold->ToString() . "\n");
}

$events = $project->History();
$historystr = "";
foreach($events as $event) {
    /** @var DpEvent $event */
    $historystr .= ("        " . $event->ToString() . "\n");
}

// -­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­
html_start();

// feedback from advance
if(isset($msgs)) {
    echo "<div class='warning'>\n";
    if(count($msgs) == 0) {
        echo "<h4>Project would advance to {$project->NextPhase()}.</h4>\n";
    }
    else {
        echo "<h4>Project would NOT advance to {$project->NextPhase()}.</h4>\n";
        echo "<pre>" . implode("<br />", $msgs) . "</pre>\n";
    }
    echo "</div>
    <hr />\n";
}

echo "
<form target='' name='frmflow' method='post'>
<div>
    Project ID: <input type='text' name='projectid' value='$projectid' size='30'><input tabindex='1' type='submit'>
</div>
<p>This page is only available to Adminstrators, and for Project Managers, only for their own projects. For the time being, PMs can view all projects - but be careful.</p>
<hr>\n";

$is_u_hold_PREP = ($project->UserPhaseHoldId("PREP") != 0);
$is_u_hold_P1   = ($project->UserPhaseHoldId("P1") != 0);
$is_u_hold_P2   = ($project->UserPhaseHoldId("P2") != 0);
$is_u_hold_P3   = ($project->UserPhaseHoldId("P3") != 0);
$is_u_hold_F1   = ($project->UserPhaseHoldId("F1") != 0);
$is_u_hold_F2   = ($project->UserPhaseHoldId("F2") != 0);
$is_u_hold_PP   = ($project->UserPhaseHoldId("PP") != 0);
$is_u_hold_PPV  = ($project->UserPhaseHoldId("PPV") != 0);

echo "
<pre>
    Project ID: {$project->ProjectId()}  $proj_link
    Title:      {$project->NameOfWork()}
    Author:     {$project->AuthorsName()}
    PM:         {$project->ProjectManager()}
    Phase:      {$project->Phase()}
    State:      {$project->State()}
    Holds:      $holdcount ($nactiveholds this phase)
    PPer:       {$project->PPer()}
    PPVer:      {$project->PPVer()}
    Posted #:   {$project->PostedNumber()}
</pre>\n";

$stats = $dpdb->SqlRows("
    SELECT state, COUNT(1) npages
    FROM $projectid
    GROUP BY state");

$tbl = new DpTable("tblstats", "w25 lfloat, dptable");
$tbl->SetRows($stats);
$tbl->EchoTable();


echo "
<pre>
    Test round advance: 
    <input type='submit' name='submit_test_advance' value='Test' />
    <input type='submit' name='submit_advance' value='Advance' />

    Holds:
$holdstr

    User Holds:\n";

    $tbl = new DpTable();
    $rows = array();
    $rows[] = array("Phase", "Is Set", "Switch");


    foreach(array("PREP", "P1", "P2", "P3", "F1", "F2", "PP", "PPV") as $ph) {
        $id = $project->UserPhaseHoldId($ph);
        $status = ($id == 0 ? "No"   : "Yes" );
        $ctl = ($id == 0 
                ? "<input type='submit' name='set_hold[$ph]' value='Set'/>"
                : "<input type='submit' name='release_hold[$id]' value='Release'/>");

        $rows[] = array($ph, $status, $ctl);
    }
    $tbl->SetRows($rows);
    $tbl->EchoTable();

echo "
</pre>

<hr>
<pre>
PREP   <a href='http://www.pgdpcanada.net/c/tools/prep.php'>link</a>\n";

if($project->Phase() == "PREP") {
    $qcholdid = $project->QCHoldId();
    echo "
Advance Requirements:
    Pages loaded: {$project->PageCount()}
    Clearance:    {$project->Clearance()}
    Project Mgr:  {$project->ProjectManager()}
    Language:     {$project->LanguageCode()}
    QC Hold Id:   {$qcholdid}\n";
    echo ($qcholdid == 0 ? "<input type='submit' name='set_qc_hold' value='Set QC Hold'/>\n"
               : "<input type='submit' name='release_qc_hold' value='Release QC Hold'/>\n");
}
echo "
</pre>
<hr>
<h4>".link_to_round('P1')."</h4>
<pre>\n";

if($project->Phase() == "P1") {
    echo "
<a href='http://www.pgdpcanada.net/c/tools/p1release.php'>P1 Queue Holds</a>\n";
}


echo "
<input type='submit' name='submit_revert[P1]' value='Revert to PREP' $p1_disabled>

</pre>
<hr>
<pre>
<h4>".link_to_round('P2')."</h4>
<input type='submit' name='submit_revert[P2]' value='Revert to P1' $p2_disabled>
</pre>
<hr>
<pre>
<h4>".link_to_round('P3')."</h4>
</pre>
<input type='submit' name='submit_revert[P3]' value='Revert to P2' $p3_disabled>
<hr>
<pre>
<h4>".link_to_round('F1')."</h4>
</pre>
<input type='submit' name='submit_revert[F1]' value='Revert to P3' $f1_disabled>
<hr>
<pre>
<h4>".link_to_round('F2')."</h4>
</pre>
<input type='submit' name='submit_revert[F2]' value='Revert to F1' $f2_disabled>
<hr>
<pre>
<h4>".link_to_phase('pp', 'PP')."</h4>
    PP upload file: {$project->PPUploadPath()}
    Exists?   $isPPUploadFile
</pre>
    <input type='submit' name='submit_revert[PP]' value='Revert to F2' $pp_disabled>
    <input type='submit' name='submit_pp_uncheckout' value='PP return' $pp_disabled>
    <input type='submit' name='submit_pp_complete' value='PP complete' $pp_disabled>
<hr>
<pre>
<h4>".link_to_phase('ppv', 'PPV')."</h4>
PPV   <a href='http://www.pgdpcanada.net/c/tools/ppv.php'>link</a>
    PPV upload file: {$project->PPVUploadPath()}
    Exists?   $isPPVUploadFile
</pre>
    <input type='submit' name='submit_revert[PPV]' value='Revert to PP' $ppv_disabled>
    <input type='submit' name='submit_ppv_uncheckout' value='PPV return' $ppv_disabled>
    <input type='submit' name='submit_ppv_checkout' value='PPV Check Out' $ppv_disabled>
    <input type='submit' name='submit_ppv_complete' value='PPV complete' $ppv_disabled>
    <input type='submit' name='submit_ppv_post' value='PPV Post' $ppv_disabled>  posted #:<input type='text'   name='postednum'       value='$postednum' $ppv_disabled>
<hr>
<pre>
POSTED
</pre>
<input type='submit' name='submit_revert[PPV]' value='Revert to PPV' $posted_disabled>
<hr>
<pre>
<h4>Miscellaneous hacks</h4>
<input type='submit' name='submit_adjust_pages' value='adjust page states' />
<hr>
History:
$historystr
</pre>
<hr>\n";

EchoPageStateIssues($project);
echo "
</form>\n";

html_end();
exit;


function html_start() {
?><!DOCTYPE HTML>
<html>
<head>
<title>Project Trace</title>
<link type='text/css' rel='Stylesheet' href='/c/css/dp.css'>
</head>
<body>
<?PHP
}

function html_end() {
?>
</body></html>
<?PHP
}

/** @var DpProject $project */
function EchoPageStateIssues($project) {
    /** @nfer
    var DpProject $project */

    switch($project->Phase()) {
        case "P1":
        case "P2":
        case "P3":
        case "F1":
        case "F2":
            break;
        default:
            return;
    }
    global $dpdb;
    $projectid = $project->ProjectId();
    $phase = $project->Phase();
    $pages = $dpdb->SqlRows("
        SELECT state, COUNT(1) page_count FROM $projectid
        WHERE NOT state LIKE '{$phase}\.page%'
        GROUP BY state
        ORDER BY fileid");
    if(count($pages) == 0) {
        return;
    }
    $tbl = new DpTable();
    $tbl->SetRows($pages);
    echo _("<h4>The following pages don't match the project Phase ($phase):</h4>\n");
    $tbl->EchoTableNumbered();
}

function AdjustPhasePages($project) {
    /** @var DpProject $project */

    global $dpdb;

    $projectid = $project->ProjectId();
    $phase = inferred_phase($project);
    $state = "{$phase}.proj_avail";
    echo("<p>It appears that the project should be working in $phase.</p>");
    if($project->Phase() != $phase) {
        $project->SetPhase($phase);
    }
    if($project->State() != $state) {
        $project->SetState($state);
    }

    switch($phase) {
        case "P1":
            $round = "round1_";
            break;
        case "P2":
            $round = "round2_";
            break;
        case "P3":
            $round = "round3_";
            break;
        case "F1":
            $round = "round4_";
            break;
        case "F2":
            $round = "round5_";
            break;
        default:
            return;
    } 

    $phase_user = $round . "user";
    $phase_time = $round . "time";
    $phase_text = $round . "text";

    $avail       = $phase . ".page_avail";
    $saved       = $phase . ".page_saved";
    $checked_out = $phase . ".page_out";
    $temp        = $phase . ".page_temp";

    // any empty field for current round means available.
    $n = $dpdb->SqlExecute("
        UPDATE $projectid pp
        SET state = '$avail'
        WHERE NULLIF(pp.{$phase_user}, '') IS NULL
           OR NULLIF(pp.{$phase_text}, '') IS NULL
           OR NULLIF(pp.{$phase_time}, '') IS NULL");

    echo("<p>Pages set to available: $n</p>");

    $n = $dpdb->SqlExecute("
        UPDATE $projectid pp
        SET state = '$saved'
        WHERE LENGTH(pp.{$phase_user}) > 0
           AND LENGTH(pp.{$phase_text}) > 0
           AND pp.{$phase_time} > 0");

    echo("<p>Pages set to saved:</p>$n");

    return;
    // if for any other round

    $projectid = $project->ProjectId();
    switch($project->Phase()) {
        case 'F2':
            // Set pages to this phase.available if this phase not comoplete
            $dpdb->SqlExecute("
            UPDATE $projectid pp
            JOIN projects p ON p.projectid = '$projectid'
            JOIN phases ph ON p.phase = ph.phase
            JOIN phases ph1 ON ph1.phase = 'F1'
            SET pp.state = 'F1.page_avail'
            WHERE ph.sequence >= ph1.sequence
                AND  ( NULLIF(pp.round4_text, '') IS NULL
                    OR NULLIF(pp.round4_user, '') IS NULL
                    OR NULLIF(pp.round4_time, 0) = 0)");

            // set project state to this phase.available when it's a later phase
            // and any pages are not complete
            $dpdb->SqlExecute("
                UPDATE  projects p
                SET p.state = 'F2.proj_avail',
                    p.phase = 'F2'
                WHERE EXISTS (
                     select 1 from $projectid
                     where state like 'F2%'
                         and state != 'F2.page_saved'");
            break;

        case 'F1':
            $dpdb->SqlExecute("
                UPDATE $projectid pp
                JOIN projects p ON p.projectid = 'projectid'
                JOIN phases ph ON p.phase = ph.phase
                JOIN phases ph1 ON ph1.phase = 'F1'
                SET pp.state = 'F1.page_avail'
                WHERE ph.sequence >= ph1.sequence
                    AND  ( NULLIF(pp.round4_text, '') IS NULL
                        OR NULLIF(pp.round4_user, '') IS NULL
                        OR NULLIF(pp.round4_time, 0) = 0)");

            $dpdb->SqlExecute("
                UPDATE  projects p
                SET p.state = 'F1.proj_avail',
                    p.phase = 'F1'
                WHERE EXISTS (
                     select 1 from $projectid
                     where state like 'F1%'
                         and state != 'F1.page_saved'");
            break;

        case 'P3':
            $dpdb->SqlExecute("
            UPDATE $projectid pp
            JOIN projects p ON p.projectid = '$projectid'
            JOIN phases ph ON p.phase = ph.phase
            JOIN phases ph1 ON ph1.phase = 'P3'
            SET pp.state = 'P3.page_avail'
            WHERE ph.sequence >= ph1.sequence
                AND  ( NULLIF(pp.round3_text, '') IS NULL
                       OR NULLIF(pp.round3_user, '') IS NULL
                       OR NULLIF(pp.round3_time, 0))");


            $dpdb->SqlExecute("
                UPDATE  projects p
                SET p.state = 'P3.proj_avail',
                      p.phase = 'P3'
                WHERE EXISTS (
                     select 1 from $projectid
                     where state like 'P3%'
                         and state != 'P3.page_saved'");
            break;

        case 'P2':
            $dpdb->SqlExecute("
            UPDATE $projectid pp
            JOIN projects p ON p.projectid = '$projectid'
            JOIN phases ph ON p.phase = ph.phase
            JOIN phases ph1 ON ph1.phase = 'P2'
            SET pp.state = 'P2.page_avail'
            WHERE ph.sequence >= ph1.sequence
                AND  ( NULLIF(pp.round2_text, '') IS NULL
                       OR NULLIF(pp.round2_user, '') IS NULL
                       OR NULLIF(pp.round2_time, 0))");

            $dpdb->SqlExecute("
                UPDATE  projects p
                SET p.state = 'P2.proj_avail',
                      p.phase = 'P2'
                WHERE EXISTS (
                     select 1 from $projectid
                     where state like 'P2%'
                         and state != 'P2.page_saved'");
            break;

        case 'P1':
            $dpdb->SqlExecute("
            UPDATE $projectid pp
            JOIN projects p ON p.projectid = '$projectid'
            JOIN phases ph ON p.phase = ph.phase
            JOIN phases ph1 ON ph1.phase = 'P1'
            SET pp.state = 'P1.page_avail'
            WHERE ph.sequence >= ph1.sequence
                AND  ( NULLIF(pp.round1_text, '') IS NULL
                       OR NULLIF(pp.round1_user, '') IS NULL
                       OR NULLIF(pp.round1_time, 0))");

            $dpdb->SqlExecute("
                UPDATE  projects p
                SET p.state = 'P1.proj_avail',
                      p.phase = 'P1'
                WHERE EXISTS (
                     select 1 from $projectid
                     where state like 'P1%'
                         and state != 'P1.page_saved'");
            break;
    }
    $oldphase = $project->Phase();
    $newphase = inferred_phase($project);
    if(phase_sequence($oldphase) < phase_sequence($newphase)) {
        $project->SetPhase($newphase);
        $project->LogProjectEvent(PJ_EVT_TRANSITION, "$oldphase to $newphase");
    }
}

function inferred_phase($project) {
    /** @var DpProject $project */
    global $dpdb;
    $projectid = $project->ProjectId();
    if($dpdb->SqlOneValue("
            SELECT COUNT(1) FROM $projectid
            WHERE IFNULL(round1_text, '') = ''") > 0) {
        return "P1";
    }
    if($dpdb->SqlOneValue("
            SELECT COUNT(1) FROM $projectid
            WHERE IFNULL(round2_text, '') = ''") > 0) {
        return "P2";
    }
    if($dpdb->SqlOneValue("
            SELECT COUNT(1) FROM $projectid
            WHERE IFNULL(round3_text, '') = ''") > 0) {
        return "P3";
    }
    if($dpdb->SqlOneValue("
            SELECT COUNT(1) FROM $projectid
            WHERE IFNULL(round4_text, '') = ''") > 0) {
        return "F1";
    }
    if($dpdb->SqlOneValue("
            SELECT COUNT(1) FROM $projectid
            WHERE IFNULL(round5_text, '') = ''") > 0) {
        return "F2";
    }
    return "PP";
}

function phase_sequence($phase) {
    switch($phase) {
        case "PREP":
            return 0;
        case "P1":
            return 1;
        case "P2":
            return 2;
        case "P3":
            return 3;
        case "F1":
            return 4;
        case "F2":
            return 5;
        case "PP":
            return 6;
        case "PPV":
            return 7;
        case "POSTED":
            return 8;
        default:
            return -1;
    }
}
