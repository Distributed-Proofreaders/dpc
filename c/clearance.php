<?php

error_reporting(E_ALL);
$relPath = "./pinc/";
require_once $relPath."dpinit.php";
require_once $relPath."Spreadsheet.inc";

$User->IsLoggedIn()
    or RedirectToLogin();

$tbl = new DpTable("tblsearch", "dptable sortable w95");
$tbl->AddColumn("<Type", "type");
$tbl->AddColumn("<DPC Clearance Code #", "clearance");
$tbl->AddColumn("<Posting Number at FP", "postednum");
$tbl->AddColumn("<CP/PM", "username");
$tbl->AddColumn("<Published", "published");
$tbl->AddColumn("<Author", "author");
$tbl->AddColumn("<Title", "title");

list($PGCbypid, $pgcrows) = loadPGC();
list($FPbypid, $fprows) = loadFP();
list($CSbypid, $csrows, $byCC) = loadClearance();

theme("Clearance Spreadsheet", "header");

$rows = merge($PGCbypid, $FPbypid, $CSbypid, $byCC, $fprows, $pgcrows, $csrows);

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

uasort($rows, "byAuthor");

$tbl->SetRowCount(count($rows));
$tbl->SetRows($rows);

echo "<div class='center' onclick='eSetSort(event)'>\n";
$tbl->EchoTable();
echo "</div>";

echo "<br />\n";
theme("", "footer");
exit;


function is_available($row) {
	return preg_match("/_avail/", $row['phase'])
		? "avail"
		: "";
}

function elangname($langname, $row) {
	return $langname
	       .  isset($row['seclangname'])
		? "/". $row['seclangname']
		: "";
}

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

function fpBookLink($pid, $row) {
    return "<a onclick=\"document.getElementById('frame_div').style.display='block'\" class='click_iframe' href='https://fadedpage.com/showbook.php?pid=$pid' target='bookdetails'>$pid</a>";
}

function ephase($phase, $row) {
    return $phase . (($phase == 'P1' and $row['queued'] > 0) ? "/Queue" : "");
}

function title_link($title, $row) {
	global $code_url;
	$url = "$code_url/project.php"
	       ."?id={$row['projectid']}'";
	return "<a href='{$url}>{$title}</a>";
}

function page_counts($row) {
	$pgpg = sprintf("%d/%d", $row['pages_available'], $row['pages_total']);
	return link_to_page_detail($row['projectid'], $pgpg);
}

function pmlink($pm) {
	return link_to_pm($pm, $pm);
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
            username, clearance, postednum, phase
        FROM projects
        WHERE phase != 'DELETED'
    ";
    $rows = $dpdb->SqlRows($sql);

    echo "<pre>";
    $bypid = array();
    foreach ($rows as &$row) {
        $pid = trim($row['postednum']);
        if ($pid != "" && $pid != "0") {
            #echo "$pid " . $row['title'] . "\n";
            $bypid[$pid] = &$row;
        }
    }
    echo "</pre>";
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
    echo html_comment(print_r($result, TRUE));
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
        $pid = $row['id'];
        if ($pid != '') {
            $pids = splitpids($pid);
            foreach ($pids as $pid)
                $bypid[$pid] = &$row;
        }
        $cc = trim($row['clearance']);
        if ($cc != '')
            $byCC[$cc] = &$row;
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
        <li><i>posting number</i>&sect;: Posting number is invalid.
        </ul>
    ';
    $result = array();
    $resultbypid = array();
    foreach ($fprows as &$fprow) {
        $pid = $fprow['pid'];
        $row = array();
        if (array_key_exists($pid, $PGC)) {
            // Normal, posted project. Both rows exist
            $pgcrow = $PGC[$pid];
            $row['type'] = "normal";
            $row['postednum'] = $pid;
            $row['title'] = $fprow['title'];
            $row['author'] = eauthors($fprow['authors'], $fprow);
            $row['username'] = $pgcrow['username'];
            $row['clearance'] = $pgcrow['clearance'];
            $row['published'] = $fprow['first_publication'];
            $resultbypid[$pid] = &$row;
            $clearance = $pgcrow['clearance'];

            // See if a clearance row exists, if so, should have same pid
            if (array_key_exists($clearance, $byCC)) {
                $csrow = $byCC[$clearance];
                $pids = splitpids($csrow['id']);
                $found = false;
                foreach ($pids as $p)
                    if ($p == $pid) {
                        $found = true;
                        break;
                    }
                if (!$found)
                    $row['postednum'] .= " != " . $csrow['id'];
            }

        } else if (array_key_exists($pid, $CS)) {
            // Posting number in the clearance spreadsheet.
            // Since the project doesn't exist, probably pre-crash
            $csrow = $CS[$pid];
            if ($csrow['clearance'] != '')
                $row['type'] = "pre-crash";
            else
                $row['type'] = "harvest";
            $row['postednum'] = $pid;
            $row['title'] = $csrow['title'];
            $row['author'] = $csrow['author'];
            $row['username'] = $csrow['pm'];
            $row['clearance'] = $csrow['clearance'];
            $row['published'] = $csrow['published'];
            $resultbypid[$pid] = &$row;
        } else {
            // Not either a DP project, or in the clearance spreadsheet
            // Only other option is a harvest
            $row['type'] = "harvest †";
            $row['postednum'] = $pid;
            $row['title'] = $fprow['title'];
            $row['author'] = eauthors($fprow['authors'], $fprow);
            $row['username'] = "";
            $row['clearance'] = "";
            $row['published'] = $fprow['first_publication'];
        }
        $result[] = $row;
    }

    // Process unresolved books, should be before POSTED
    // i.e. find all current projects which don't exist on FP
    echo "<pre>";
    foreach ($pgcrows as &$r) {
        $pid = $r['postednum'];
        if ($pid != "" && $pid != "0")
            if (array_key_exists($pid, $resultbypid))
                continue;
        $row = array();
        $row['type'] = $r['phase'];
        $row['postednum'] = $pid;
        $row['title'] = $r['title'];
        $row['author'] = $r['author'];
        $row['username'] = $r['username'];
        $clearance = trim($r['clearance']);
        $row['clearance'] = $clearance;
        $row['published'] = getPublishedYearFromPGC($r);
        if ($clearance == '')
            $row['type'] .= '*';
        else if (!array_key_exists($clearance, $byCC)) {
            $row['type'] .= '‡';
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
    echo "</pre>";

    // Add bad posting number indicator
    foreach ($result as &$row) {
        $pid = $row['postednum'];
        if ($pid == '0' || $pid == '')
            $row['postednum'] = '';
        else if (!preg_match('/^[0-9][0-9][0-9][0-9][0-9][0-9][0-9A-Z][0-9]$/', $pid))
            $row['postednum'] .= "&sect;";
    }
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
