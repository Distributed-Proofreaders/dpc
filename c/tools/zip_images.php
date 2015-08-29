<?php

// Download a generated-on-demand zip of the
// image files in a given project directory.

$relPath = '../pinc/';
require_once $relPath."dpinit.php";

$projectid = ArgProjectId();

$project = new DpProject($projectid);
$pages = $project->ProjectPages();

$zipstub = "{$projectid}_images";

$apaths = array();

foreach($pages as $page) {
    $apaths[] = $page->ImageFilePath();
}
$Context->ZipSendFileArray($zipstub, $apaths);

// vim: sw=4 ts=4 expandtab
?>
