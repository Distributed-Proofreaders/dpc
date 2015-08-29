<?php
$relPath = "./pinc/";
require_once $relPath."dpinit.php";

// get variables passed into page
if($User->IsProjectFacilitator() || $User->IsSiteManager() || $User->MayMentor()) {
    $username       = Arg("username", $User->Username());
}
else {
    $username = $User->Username();
}

$pagenum        = Arg("pagenum", "1");
$rowsperpage    = Arg("rowsperpage", "100");
$roundid1       = Arg("roundid", "P1");
$roundid2       = RoundIdAfter($roundid1);
$cmdPgUp        = IsArg("cmdPgUp");
$cmdPgDn        = IsArg("cmdPgDn");
$cmdquery       = IsArg("cmdquery");

if($cmdquery) {
    $pagenum = "1";
}

$title = "DPC User Pages Proofed With Diffs";
$subtitle = "Round $roundid1 Pages for $username";

$title = "DPC User Pages Proofed With Diffs";
if($roundid1 != "" && $username != "") {
    $subtitle = "Round $roundid1 Pages for $username";
    switch($roundid1) {
        case "P1":
            $roundid2 = "P2";
            break;
        case "P2":
            $roundid2 = "P3";
            break;
        case "P3":
            $roundid2 = "F1";
            break;
        case "F1":
            $roundid2 = "F2";
            break;
        default:
            die("roundid must be P1, P2, P3, or F1.");
    }

    $sql = "
        SELECT
            p.projectid,
            p.nameofwork,
            FROM_UNIXTIME(pe1.timestamp) time1,
            FROM_UNIXTIME(pe2.timestamp) time2,
            pe1.image,
            pe1.round_id AS roundid1,
            pe2.round_id AS roundid2,
            pe1.username as user1,
            LOWER(pe1.username) AS u1sort,
            pe2.username as user2,
            LOWER(pe2.username) AS u2sort,
            0 as isdiff
        FROM projects p
        JOIN page_events pe1 ON p.projectid = pe1.projectid
        JOIN page_events pe2
        ON pe1.projectid = pe2.projectid
            AND pe1.image = pe2.image
            AND pe1.round_id = '$roundid1'
            AND pe1.event_type = 'saveAsDone'
            AND pe2.round_id = '$roundid2'
            AND pe2.event_type = 'saveAsDone'
        LEFT JOIN page_events pe3
        ON pe3.projectid = pe1.projectid
            AND pe3.image = pe1.image
            AND pe3.round_id = pe1.round_id
            AND pe3.timestamp > pe1.timestamp
        LEFT JOIN page_events pe4
        ON pe4.projectid = pe2.projectid
            AND pe4.image = pe2.image
            AND pe4.round_id = pe2.round_id
            AND pe4.timestamp > pe2.timestamp
        WHERE pe1.username = '$username'
            AND pe3.event_id IS NULL
            AND pe4.event_id IS NULL
        ORDER BY pe2.timestamp DESC";
    $rows = $dpdb->SqlRows($sql);

    if($cmdPgUp) {
        $pagenum = max($pagenum - 1, 1);
    }
    if($cmdPgDn) {
        $pagenum = min($pagenum + 1, ceil(count($rows) / $rowsperpage));
    }



    $tbl = new DpTable("tblDiffs", "dptable sortable w95 right em90");
    $tbl->AddColumn("<Title", "projectid", "eprojectid", "w40");
    $tbl->AddColumn("<Page", "image", "epage");
    $tbl->AddColumn("^{$roundid1}", "time1", null, "w40");
    $tbl->AddColumn("^{$roundid1} proofer", "user1", "euser", "sortkey=u1sort");
    $tbl->AddColumn("^{$roundid2}", "time2", null, "w40");
    $tbl->AddColumn("^{$roundid2} proofer", "user2", "euser", "sortkey=u2sort");
    $tbl->AddColumn("^Diff", "image", "ediff");

    $tbl->SetRowCount(count($rows));

    $tbl->SetPaging($pagenum, $rowsperpage);
//    dump($reprows);
//    die();
    $tbl->SetRows($rows);
}
else {
    $subtitle = "Select username and round to view list of pages.";
}
$no_stats = 1;
theme($title, "header");

echo "<form id='frmpages' name='frmpages' action='' method='POST'>
<input type='hidden' name='rowsperpage' value='$rowsperpage'>
<input type='hidden' name='pagenum' value='$pagenum'>
\n";
if($User->IsProjectFacilitator() || $User->IsSiteManager()) {
    echo "
    <div class='lfloat padded margined'>
     Username:
    <input type='text' name='username' id='username' value='$username'/>
    </div>\n";
}
echo "
    <div class='lfloat padded margined bordered'>
        Round
        <input type='radio' name='roundid' value='P1'"
     .($roundid1 == "P1" ? " checked" : "") .">
        P1
        <input type='radio' name='roundid' value='P2'"
     .($roundid1 == "P2" ? " checked" : "") .">
        P2
        <input type='radio' name='roundid' value='P3'"
     .($roundid1 == "P3" ? " checked" : "") .">
        P3
        <input type='radio' name='roundid' value='F1'"
     .($roundid1 == "F1" ? " checked" : "") .">
        F1
    </div>
    <div class='lfloat padded margined'>
        <input type='submit' id='cmdquery' name='cmdquery' class='lfloat'>
    </div>\n";
echo "<h1 class='clear center'>$title</h1>\n";
echo "<h2 class='center'>$subtitle</h2>\n";


if(isset($sql)) {
	echo html_comment($sql);
}

if(isset($tbl)) {
    $tbl->EchoTableNumbered();
}

echo "</form>\n";

theme("", "footer");
exit;

function epage($image, $row) {
    $projectid = $row['projectid'];
    $pagename = imagefile_to_pagename($image);
    return link_to_view_image($projectid, $pagename);
}

function eprojectid($projectid, $row) {
    $title = $row['nameofwork'];
    return link_to_project($projectid, $title);
}

function euser($username) {
    return link_to_pm($username);
}

function ediff($imagename, $row) {
	global $dpdb;

    if(! $dpdb->IsTable($row["projectid"])) {
        return "--";
    }
    $image      = $row["image"];
    $round1     = $row["roundid1"];
    $round2     = $row["roundid2"];
    $projectid  = $row["projectid"];
    $textfield1 = TextFieldForRoundId($round1);
    $textfield2 = TextFieldForRoundId($round2);
    $sql = "SELECT BINARY TRIM($textfield1) != BINARY TRIM($textfield2) AS isdiff
            FROM $projectid
            WHERE image = '{$image}'";
    if($dpdb->SqlOneValue($sql)) {
        $pagename = imagefile_to_pagename($imagename);
        return link_to_diff($projectid, $pagename, $round2);
    }
//    global $roundid2;
//    if($isdiff) {
//    }
    else {
        return "--";
    }
}

//function report_rows($pagenum, $rowsperpage, $rows) {
//    if ( $pagenum > 0 && $rowsperpage > 0 ) {
//        $rowcount    = count($rows);
//        $r1          = ($pagenum - 1) * $rowsperpage;
//        $r1          = min($r1, $rowcount);
//        $r2          = $rowsperpage;
//        $r2          = min($r2, $rowcount - $r1);
//        return array_slice( $rows, $r1, $r2 );
//    } else {
//        return $rows;
//    }
//}

// vim: sw=4 ts=4 expandtab
