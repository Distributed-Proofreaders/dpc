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
$phase          = Arg("phase", $roundid1);
$cmdPgUp        = IsArg("cmdPgUp");
$cmdPgDn        = IsArg("cmdPgDn");
$cmdquery       = IsArg("cmdquery");

if($cmdquery) {
    $pagenum = "1";
}

$title = "DPC User Pages Proofed With Diffs";
$subtitle = "Round $phase Pages for $username";

$title = "DPC User Pages Proofed With Diffs";
if($phase != "" && $username != "") {
    $subtitle = "Round $phase Pages for $username";

	$sql = "
		SELECT  p.projectid,
				p.nameofwork,
				pv1.version version1,
				pv2.version version2,
				pv1.textlen textlen1,
				pv2.textlen textlen2,
				FROM_UNIXTIME(pv1.version_time) time1,
				FROM_UNIXTIME(pv2.version_time) time2,
				pv1.pagename,
				pv1.phase as phase,
				pv2.phase as phase2,
				pv1.username as user1,
				pv2.username as user2,
				LOWER(pv1.username) as u1sort,
				LOWER(pv2.username) as u2sort,
				pv1.crc32 != pv2.crc32 AS isdiff
		FROM projects p
		JOIN page_versions pv1
			ON p.projectid = pv1.projectid
		JOIN page_versions pv2
			ON p.projectid = pv2.projectid
				AND pv1.pagename = pv2.pagename
				AND pv2.version = pv1.version + 1
		WHERE pv1.username = ?
			AND pv1.phase = ?
			AND pv1.state = 'C'
			AND pv2.state = 'C'
			AND pv1.crc32 != pv2.crc32
        ORDER BY pv1.version_time DESC
        LIMIT 2000";

    $args = [ &$username, &$phase ];
	$rows = $dpdb->SqlRowsPS($sql, $args);
    if(count($rows) > 0) {
        $phase2 = $rows[0]['phase2'];

        if($cmdPgUp) {
            $pagenum = max($pagenum - 1, 1);
        }
        if($cmdPgDn) {
            $pagenum = min($pagenum + 1, ceil(count($rows) / $rowsperpage));
        }



        $tbl = new DpTable("tblDiffs", "dptable sortable w95 right em90");
        $tbl->AddColumn("<Title", "projectid", "eprojectid", "w40");
        $tbl->AddColumn("<Page", "image", "epage");
        $tbl->AddColumn("^{$phase}", "version1", "eversion1");
        $tbl->AddColumn("^{$phase}", "time1", null, "w40");
        $tbl->AddColumn("^{$phase} proofer", "user1", "euser", "sortkey=u1sort");
        $tbl->AddColumn("^{$phase}", "version2", "eversion2");
        $tbl->AddColumn("^{$phase2}", "time2", null, "w40");
        $tbl->AddColumn("^{$phase2} proofer", "user2", "euser", "sortkey=u2sort");
        $tbl->AddColumn("^Diff", "image", "ediff");

        $tbl->SetRowCount(count($rows));

        // REMOVED paging. Paging means sorting doesn't work.
        // Added arbitrary limit of 2000 rows instead.
        //$tbl->SetPaging($pagenum, $rowsperpage);
//    dump($reprows);
//    die();
        $tbl->SetRows($rows);
    }
    else {
        $pagenum = 1;
        $rowsperpage = 1;
    }
}
else {
    $subtitle = "Select username and round to view list of pages.";
    $phase2 = "";
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
     .($phase == "P1" ? " checked" : "") .">
        P1
        <input type='radio' name='roundid' value='P2'"
     .($phase == "P2" ? " checked" : "") .">
        P2
        <input type='radio' name='roundid' value='P3'"
     .($phase == "P3" ? " checked" : "") .">
        P3
        <input type='radio' name='roundid' value='F1'"
     .($phase == "F1" ? " checked" : "") .">
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

function eversion1($version, $row) {
	$projectid = $row['projectid'];
	$pagename = $row['pagename'];
	$len = $row['textlen1'];
	return link_to_version_text($projectid, $pagename, $version, $len, true);
}
function eversion2($version, $row) {
	$projectid = $row['projectid'];
	$pagename = $row['pagename'];
	$len = $row['textlen2'];
	return link_to_version_text($projectid, $pagename, $version, $len, true);
}

function epage($image, $row) {
    $projectid = $row['projectid'];
    $pagename = $row['pagename'];
    return link_to_view_image($projectid, $pagename);
}

function eprojectid($projectid, $row) {
    $title = $row['nameofwork'];
    return link_to_project($projectid, $title);
}

function euser($username) {
    return link_to_pm($username);
}

function ediff($isdiff, $row) {
	if(! $isdiff) {
		return "";
	}
	$projectid = $row['projectid'];
	$pagename = $row['pagename'];
	$phase   = $row['phase'];
//	$version1  = $row['version1'];
	return  link_to_diff($projectid, $pagename, $phase, "Diff", "2", true);
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
