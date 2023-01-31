<?php

$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');

$User->IsLoggedIn()
    or die("Please log in.");

$User->IsAdmin()
    or die("Not authorized.");

$id = "tblcounts";

$page_number    = Arg("page_number", "1");
$page_ahead     = IsArg("page_ahead");
$page_back      = IsArg("page_back");
$archive_now    = IsArg("archive_now");

if($page_ahead) {
    $page_number++;
}
if($page_back && ($page_number > 0)) {
    $page_number--;
}

$month = new DateTime("first day of this month");
$dayofmonth = $month->format("d");
$month = $month->modify("-$page_number month");
$month = $month->format("Ym");
if ($page_number == 1)
    $next = 'disabled';
else
    $next = '';

theme("Archive Projects", "header");

if ($archive_now) {
    if ($page_number == 1 && $dayofmonth < 15)
        die("Invalid archive request");
    echo _("<b>Running archive....</b><br>\n");
    $rows = getProjectsToArchive($month);
    foreach ($rows as $row) {
        $projectid = $row['projectid'];
        $title = $row['nameofwork'];
        if (strpos($title, 'REVIEW') !== false)
            echo "Skipping, marked for review.<br>\n";
        else {
            $source = _ProjectPath($projectid);
            if (!file_exists($source) || !is_dir($source))
                echo "Project directory $source not there or not a directory, probably already archived.<br>\n";
            else {
                $target = _ProjectArchivePath($projectid);
                if (file_exists($target))
                    echo "Archive directory $target already exists!<br>\n";
                else {
                    echo "Rename $source->$target...";
                    if (rename($source, $target)) {
                        echo "Success.<br>\n";
                        file_put_contents($source, "archived\n");
                    } else
                        echo "FAILURE:" . error_get_last()['message'] . "<br>\n";
                }
            }
        }
    }
}

echo _("<h1 class='center'>Projects to Archive for $month (-$page_number)</h1>\n");

if ($page_number == 1 && $dayofmonth < 15) {
    echo _("<h2 class='center'>Do not archive until after the 15th!</h1>\n");
    $archivenow = "disabled";
} else
    $archivenow = "";

$tbl = new DpTable($id, "dptable w95 center");
$tbl->AddColumn("<Project ID", "projectid", "eprojectid");
$tbl->AddColumn("^Archived?", "projectid", "dirtest");
$tbl->AddColumn("<Date Transitioned to Posted", "Phase Changed");
$tbl->AddColumn("<Title", "nameofwork");

$rows = getProjectsToArchive($month);
$tbl->SetRows($rows);
$tbl->EchoTableNumbered();

echo "<form name='formcount' method='POST'>
<input type='hidden' name='page_number' value='$page_number'>
<div class='w95 center'>
<input class='lfloat' type='submit' name='page_back' $next value='Next Month'>
<input type='submit' name='archive_now' $archivenow value='Archive These Projects Now'>
<input class='rfloat' type='submit' name='page_ahead' value='Previous Month'>
</form>\n";

theme("", "footer");
exit;

// Override the ProjectPath&ProjectArchivePath in helpers, those versions call
// EnsureWritableDirectory which will create the directory
// if it doesn't exist!
function _ProjectPath($projectid) {
    global $projects_dir;
    $path = build_path($projects_dir, $projectid);
    return $path;
}
function _ProjectArchivePath($projectid) {
    global $projects_archive_dir;
    $path = build_path($projects_archive_dir, $projectid);
    return $path;
}

function dirtest($projectid, $row) {
    $path = _ProjectPath($projectid);
    $archive = _ProjectArchivePath($projectid);
    if (file_exists($path) && is_dir($path)) {
        $title = $row['nameofwork'];
        if (strpos($title, 'REVIEW') !== false)
            $msg = "Marked for review. Do not archive.";
        else {
            $msg = $path . " ==> " . $archive;
            if (file_exists($archive) && is_dir($archive))
                $msg .= " Archive directory already exists!";
        }
    } else
        $msg = "Already Archived.";
    return $msg;
}

function eprojectid($projectid, $row) {
    return link_to_project($projectid, $projectid);
}

function getProjectsToArchive($month) {
    global $dpdb;

    //LEFT JOIN (
    //    SELECT EXTRACT(YEAR_MONTH FROM DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) last_month
    //) T ON 1=1
    $rows = $dpdb->SqlRows("
        SELECT projectid,
            FROM_UNIXTIME(phase_change_date) 'Phase Changed',
            nameofwork
        FROM projects
        WHERE phase = 'POSTED'
          AND EXTRACT(YEAR_MONTH FROM FROM_UNIXTIME(phase_change_date)) = '$month'
        ORDER BY phase_change_date ASC
    ");

    return $rows;
}

