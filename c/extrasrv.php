<?php
$relPath = "./pinc/";
require_once "pinc/dpinit.php";

$User->IsLoggedIn()
    or redirect_to_home();

$projectid = ArgProjectid();

if (! ($projectid)) {
    echo "extrasrv.php: missing or empty 'projectid' parameter.";
    exit;
}

$project = new DpProject($projectid);
$zipname = "{$projectid}_extras";
$zippaths = $project->ExtraFilePaths();
$Context->ZipSendFileArray($zipname, $zippaths);
