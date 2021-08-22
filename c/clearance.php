<?php

error_reporting(E_ALL);
$relPath = "./pinc/";
require_once $relPath."dpinit.php";
require_once $relPath."Spreadsheet.inc";

$User->IsLoggedIn()
    or RedirectToLogin();

$tbl = new DpTable("tblsearch", "dptable sortable w95");
$tbl->AddColumn("<Status", "status", null, "filter");
$tbl->AddColumn("<Type", "type", null, "filter");
$tbl->AddColumn("<DPC Clearance Code #", "clearance");
$tbl->AddColumn("<Posting Number at FP", "postednum");
$tbl->AddColumn("<ProjectID", "projectid", "eproject");
$tbl->AddColumn("<CP/PM", "username");
$tbl->AddColumn("<Published", "published");
$tbl->AddColumn("<Author", "author");
$tbl->AddColumn("<Title", "title");

$t1 = microtime(true);
list($PGCbypid, $pgcrows) = loadPGC();
$t2 = microtime(true);
list($FPbypid, $fprows) = loadFP();
$t3 = microtime(true);
list($CSbypid, $csrows, $byCC) = loadClearance();
$t4 = microtime(true);

theme("Clearance Spreadsheet", "header");

echo "<h2 class='center m50em'>Clearance Reconcilation</h2>\n";

$rows = merge($PGCbypid, $FPbypid, $CSbypid, $byCC, $fprows, $pgcrows, $csrows);
$t5 = microtime(true);

echo  "pg: " . (string)($t2 - $t1) . ", "
    . "fp:  " . (string)($t3 - $t2) . ", "
    . "cs:  " . (string)($t4 - $t3) . ", "
    . "Emit:  " . (string)($t5 - $t4) . "<br>\n";

function byAuthor($row1, $row2)
{
    $a1 = normalizeAuthor($row1['author']);
    $a2 = normalizeAuthor($row2['author']);
    if ($a1 == $a2) {
        $t1 = $row1['title'];
        $t2 = $row2['title'];
        // Should never have both author and title equal!
        return $t1 < $t2 ? -1 : 1;
    }
    return $a1 < $a2 ? -1 : 1;
}

// Remove anything past an open parenthesis.
// Normally birth-death dates; or perhaps a pseudonym?
function normalizeAuthor($a)
{
    $n = strpos($a, '(');
    if ($n)
        return trim(substr($a, 0, $n));
    return trim($a);
}

function eproject($p)
{
    return link_to_project($p, $p, true);
}

uasort($rows, "byAuthor");

$tbl->SetRowCount(count($rows));
$tbl->SetRows($rows);

echo "<div class='center'>\n";
$tbl->EchoTable();
echo "</div>";

echo "<br />\n";
theme("", "footer");
exit;


function eauthors($authors, $row) {
    $names = "";
    foreach ($authors as $author) {
        $name = $author['realname'];
        if (isset($author['pseudoname'])) {
            $pn = $author['pseudoname'];
            $name .= " as $pn";
        }
        if ($names != "")
            $names .= " & ";
        $names .= $name;
    }
    return $names;
}

function fpBookLinks($pids) {
    foreach ($pids as $pid) {
        if (isset($all))
            $all .= ",&#8203;" . fpBookLink($pid);
        else
            $all = fpBookLink($pid);
    }
    return $all;
}

function fpBookLink($pid) {
    return "<a href='https://fadedpage.com/showbook.php?pid=$pid' target='_blank'>$pid</a>";
}

function edit_link($row) {
	global $User;

	return ( $User->IsSiteManager()
	         || $row['pm'] == $User->Username()
	       || $row['pp'] == $User->Username())
		? link_to_edit_project($row['projectid'], "Edit", true)
		: "";
}

function loadPGC()
{
    global $dpdb;

    $sql = "
        SELECT nameofwork AS title, authorsname AS author,
            username, clearance, postednum, phase, projectid
        FROM projects
        WHERE phase != 'DELETED'
    ";
    $rows = $dpdb->SqlRows($sql);

    $bypid = array();
    foreach ($rows as &$row) {
        $pid = trim($row['postednum']);
        if ($pid != "" && $pid != "0") {
            #echo "$pid " . $row['title'] . "\n";
            $bypid[$pid] = &$row;
        }
    }
    return array(&$bypid, &$rows);
}

function loadFP()
{
    $post = array();
    $post['limit'] = 10000;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://www.fadedpage.com/csearc2.php");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    $result = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($result, $assoc = TRUE);
    // echo html_comment(print_r($result, TRUE));
    $rows = $result['rows'];

    $bypid = array();
    foreach ($rows as &$row) {
        $pid = $row['pid'];
        $bypid[$pid] = &$row;
    }

    return array(&$bypid, &$rows);
}

function loadClearance()
{
    $rows = loadClearanceSpreadsheet();

    $bypid = array();
    $byCC = array();
    foreach ($rows as &$row) {
        $cc = trim($row['clearance']);
        if ($cc != '') {
            if (isset($byCC[$cc])) {
                //echo "Duplicate Clearance: " . $cc . "<br>\n";
                // Merge the pids!
                if (!empty($byCC[$cc]['id']))
                    if (!empty($row['id']))
                        $row['id'] .= "," . $byCC[$cc]['id'];
                    else
                        $row['id'] = $byCC[$cc]['id'];
            }
            $byCC[$cc] = &$row;
        }

        $pid = $row['id'];
        if ($pid != '') {
            $pids = splitpids($pid);
            foreach ($pids as $pid)
                $bypid[$pid] = &$row;
        }
    }
    //print_r($rows[0]);
    //print_r($rows[1]);
    //print_r($rows[2]);
    //print_r($bypid);

    return array($bypid, $rows, $byCC);
}

function merge(&$PGC, &$FP, &$CS, &$byCC, &$fprows, &$pgcrows, &$csrows)
{
    //echo "<pre>";
    //print_r($PGC['20130346']);
    //print_r($FP['20130346']);
    //print_r($CS['20130346']);
    //echo "</pre>";
    echo '
        This table attempts to reconcile the three sources of information:
        <ul>
        <li>The clearance spreadsheet,
        <li>The project on pgdpcanada,
        <li>The book on fadedpage.
        </ul>
        <p>For a normal project, the posting number should be in all three
        sources.  The clearance code should be both in the clearance
        spreadsheet and the project.
        Type & status columns:
        <ul>
        <li>pre-crash: On fadedpage & in clearance spreadsheet, but no
        DPC project.
        <li>normal: Both on fadedpage and a DPC project exists
        <li>harvest: On fadedpage & in clearance spreadsheet, but
        no copyright clearance in the spreadsheet.
        <li>harvest †: On fadedpage, but not a project, and not in
        the clearance spreadsheet. Probable harvest, which was never
        added to the clearance spreadsheet.
        <li><i>phase</i>: Not on fadedpage, but a project exists without
        a posting number.
        <li><i>phase</i> *: project without a copyright clearance. Should
        always be in PREP.
        <li><i>phase</i> ‡: project with clearance, but no such clearance
        in the clearance spreadsheet.
        <li><i>posting number</i>§: Posting number is invalid.
        <li><i>fp posting number ≠ clearance posting number</i>:
        Project & Clearance spreadsheet clearance codes agreed;
        but the posting numbers differed.
        </ul>

        Does not handle:
        <ul>
        <li>Multiple posting numbers in the clearance spreadsheet.
        <li>Multiple projects cleared with the same clearance code.
        i.e. multiple rows in the clearance spreadsheet with the same
        clearance code; only the last will be used.
        </ul>
    ';
    $result = array();
    $resultbypid = array();
    foreach ($fprows as &$fprow) {
        $pid = $fprow['pid'];
        $row = array();
        $row['status'] = '';
        if (isset($PGC[$pid])) {
            // Normal, posted project. Both rows exist
            $pgcrow = $PGC[$pid];
            $row['type'] = "normal";
            $row['postednum'] = fpBookLink($pid);
            $row['title'] = $fprow['title'];
            $row['author'] = eauthors($fprow['authors'], $fprow);
            $row['username'] = $pgcrow['username'];
            $row['clearance'] = $pgcrow['clearance'];
            $row['projectid'] = $pgcrow['projectid'];
            $row['published'] = $fprow['first_publication'];
            $resultbypid[$pid] = &$row;
            $clearance = $pgcrow['clearance'];

            // See if a clearance row exists, if so, should have same pid
            if (isset($byCC[$clearance])) {
                $csrow = $byCC[$clearance];
                $pids = splitpids($csrow['id']);
                $found = false;
                foreach ($pids as $p)
                    if ($p == $pid) {
                        $found = true;
                        break;
                    }
                if (!$found) {
                    if (empty($csrow['id']))
                        $row['postednum'] .= "≠(missing)";
                    else
                        $row['postednum'] .= "≠" . fpBookLinks($pids);
                    $row['status'] .= '≠';
                }
            }

        } else if (isset($CS[$pid])) {
            // Posting number in the clearance spreadsheet.
            // Since the project doesn't exist, probably pre-crash
            $csrow = $CS[$pid];
            if ($csrow['clearance'] != '')
                $row['type'] = "pre-crash";
            else
                $row['type'] = "harvest";
            $row['postednum'] = fpBookLink($pid);
            $row['title'] = $csrow['title'];
            $row['author'] = $csrow['author'];
            $row['username'] = $csrow['pm'];
            $row['clearance'] = $csrow['clearance'];
            $row['projectid'] = '';
            $row['published'] = $csrow['published'];
            $resultbypid[$pid] = &$row;
        } else {
            // Not either a DP project, or in the clearance spreadsheet
            // Only other option is a harvest
            $row['type'] = "harvest";
            $row['status'] .= "†";
            $row['postednum'] = fpBookLink($pid);
            $row['title'] = $fprow['title'];
            $row['author'] = eauthors($fprow['authors'], $fprow);
            $row['username'] = "";
            $row['clearance'] = "";
            $row['projectid'] = '';
            $row['published'] = $fprow['first_publication'];
        }
        $result[] = $row;
    }

    // Process unresolved books, should be before POSTED
    // i.e. find all current projects which don't exist on FP
    foreach ($pgcrows as &$r) {
        $pid = $r['postednum'];
        if ($pid != "" && $pid != "0")
            if (array_key_exists($pid, $resultbypid))
                continue;
        $row = array();
        $row['type'] = $r['phase'];
        $row['status'] = '';

        if ($pid == '0' || $pid == '')
            $row['postednum'] = '';
        else if (!preg_match('/^[0-9][0-9][0-9][0-9][0-9][0-9][0-9A-Z][0-9]$/', $pid)) {
            $row['postednum'] = $pid;
            $row['status'] .= "§";
        } else
            $row['postednum'] = fpBookLink($pid);

        $row['title'] = $r['title'];
        $row['author'] = $r['author'];
        $row['username'] = $r['username'];
        $row['projectid'] = $r['projectid'];
        $clearance = trim($r['clearance']);
        $row['clearance'] = $clearance;
        $row['published'] = getPublishedYearFromPGC($r);
        if ($clearance == '') {
            $row['status'] .= '*';
        } else if (!array_key_exists($clearance, $byCC)) {
            $row['status'] .= '‡';
            //print_r($r);
            //echo "XX" . $clearance . "XX\n";
        } else {
            // If there wasn't a publication year on the end of the project's
            // title; get it from the clearance spreadsheet.
            $csrow = $byCC[$clearance];
            if (!preg_match('/^[0-9][0-9][0-9][0-9]$/', $row['published']))
                $row['published'] = $csrow['published'];
        }
        $result[] = $row;
    }

    // Add bad posting number indicator
    /*
    foreach ($result as &$row) {
        $pid = $row['postednum'];
        if ($pid == '0' || $pid == '')
            $row['postednum'] = '';
        else if (!preg_match('/^[0-9][0-9][0-9][0-9][0-9][0-9][0-9A-Z][0-9]$/', $pid))
            $row['postednum'] .= "§";
    }*/
    return $result;
}

// No explicit field on the project for the year, usually included
// in the title as (1922) at the end.
function getPublishedYearFromPGC($r)
{
    $title = $r['title'];
    if (preg_match("/\(([12][0-9][0-9][0-9])\)/", $title, $match))
        return end($match);
    return '?';
}

// The clearance spreadsheet may have multiple pids stored in the pid column
// delimited by comma space.
function splitpids($pidstr)
{
    $pids = array();
    foreach (explode(',', $pidstr) as $pid)
        $pids[] = trim($pid);
    return $pids;
}

// vim: sw=4 ts=4 expandtab
