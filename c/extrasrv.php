<?php

/*
 * Download the so-called extra files found under the project directory.
 * This is the target of the ``Download extra files'' link on the project
 * page. Currently it downloads only the files, no directories, and no
 * zip files.
 */
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
if (empty($zippaths)) {
    echo "No extra files found.<br>
        Download extra files only includes files directly under the project
        directory, excluding sub-directories and zip files.<br>";
    exit;
}
try {
    $Context->ZipSendFileArray($zipname, $zippaths);
} catch (Exception $e) {
    echo "Caught exception: ", $e->getMessage(), "\n";
}
