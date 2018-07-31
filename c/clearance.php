<?php

error_reporting(E_ALL);
$relPath = "./pinc/";
require_once $relPath."dpinit.php";

$User->IsLoggedIn()
    or RedirectToLogin();

$tbl = new DpTable("tblsearch", "dptable sortable w95");
$tbl->AddColumn("<Type", "type");
$tbl->AddColumn("<DPC Clearance Code #", "clearance");
$tbl->AddColumn("<Posting Number at FP", "postednum");
$tbl->AddColumn("<CP/PM", "username");
$tbl->AddColumn("<Author", "author");
$tbl->AddColumn("<Title", "title");

list($PGCbypid, $pgcrows) = loadPGC();
list($FPbypid, $fprows) = loadFP();

$rows = merge($PGCbypid, $FPbypid, $fprows, $pgcrows);

function byAuthor($row1, $row2)
{
    $a1 = $row1['author'];
    $a2 = $row2['author'];
    if ($a1 == $a2) {
        $t1 = $row1['title'];
        $t2 = $row2['title'];
        // Should never have both author and title equal!
        return $t1 < $t2 ? -1 : 1;
    }
    return $a1 < $a2 ? -1 : 1;
}

uasort($rows, "byAuthor");

theme("Clearance Spreadsheet", "header");

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
            if (preg_match('/^[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]$/', $pid))
                $bypid[$pid] = &$row;
            else
                echo "Bad posted number: " . print_r($row, true);
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

function merge(&$PGC, &$FP, &$fprows, &$pgcrows)
{
    //echo "<pre>";
    //print_r($PGC['20171208']);
    //print_r($FP['20171208']);
    //echo "</pre>";
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
            $resultbypid[$pid] = &$row;
        } else {
            $row['type'] = "harvest or pre-crash";
            $row['postednum'] = $pid;
            $row['title'] = $fprow['title'];
            $row['author'] = eauthors($fprow['authors'], $fprow);
            $row['username'] = "";
            $row['clearance'] = "";
        }
        $result[] = $row;
    }

    // Process unresolved books, should be before POSTED
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
        $row['clearance'] = $r['clearance'];
        $result[] = $row;
    }
    echo "</pre>";
    return $result;
}

// vim: sw=4 ts=4 expandtab
