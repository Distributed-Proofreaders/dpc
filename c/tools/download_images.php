<?php

// Download a generated-on-demand zip of the
// image files in a given project directory.

$relPath='../pinc/';
include_once $relPath . 'dpinit.php';

$User->IsLoggedIn()
    or redirect_to_home();

$projectid = ArgProjectid();

if (! ($projectid)) {
    echo "download_images.php: missing or empty 'projectid' parameter.";
    exit;
}

$project = new DpProject($projectid);

$zipname = "{$projectid}_images";
$zippaths = $project->ImagePaths();
$Context->ZipSendFileArray($zipname, $zippaths);

